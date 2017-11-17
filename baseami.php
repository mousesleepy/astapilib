<?php

class baseAMI
{
    protected $conn_handle = FALSE;
    protected $inbound_stream_buffer = [];
    protected $events = [];
    protected $responses = [];
    protected $event_handlers = [];
    protected $refresh_lock = FALSE;
    
    //конструктор, настройка по умолчанию 
    public function __construct($config = [])
    {
	//default parameters
	if (!isset($config['keepalive'])){$config['keepalive'] = TRUE;}
	//parse parameters
	foreach ($config as $opt => $val)
	{
	    if ($opt == 'autorefresh' and $val === TRUE) {new Timer(0.5, array(&$this,'refresh'), TRUE);}
	    if ($opt == 'logverbose') {LOG::set_verbose($val);}
	    if ($opt == 'keepalive' and $val === TRUE) {new Timer(60, array(&$this,'ping'), TRUE);}
	}
    }
    
    public function __destruct()
    {
	$this->disconnect();
    }
    
    //подключение к серверу, инициализация
    public function connect($host,$login,$password)
    {
	//add default ami port
	if(count(explode(':', $host)) != 2)
	{
	    $host .= ':5038';
	}
	//tcp connect
        $this->conn_handle = @stream_socket_client("tcp://".$host, $errno, $errstr, 30);
	if ($errno !== 0)
	{
	    LOG::log('Could not connect to tcp socket. Reason: '.$errstr,2);
	    return FALSE;
	}
	if (!is_resource($this->conn_handle))
	{
	    LOG::log('Socket not created! Check host and port options. Value: '.$host,2);
	    return FALSE;
	}
	stream_set_blocking($this->conn_handle,0);
	LOG::log('Socket connected',4);
	LOG::log('Server greeting phrase: '.stream_get_line ($this->conn_handle , 1500, "\r\n"),4);
	$loginstatus =  $this->login($login, $password);
	if ($loginstatus === FALSE)
	{
	    stream_socket_shutdown($this->conn_handle,STREAM_SHUT_RDWR);
	    $this->conn_handle = FALSE;
	    return FALSE;
	}
	else
	{    
	    return TRUE;
	}    
    }
    
    //отключение от сервера
    public function disconnect()
    {
	if(!is_resource($this->conn_handle)){LOG::log('Call method '.__METHOD__.' failure. TCP connection is not established.',1);return FALSE;}
	$this->logout();
	stream_socket_shutdown($this->conn_handle,STREAM_SHUT_RDWR);
	$this->conn_handle = FALSE;
	LOG::log('Socket disconnected',4);
    }
    
    //посылает запрос ping для реализации механизма keepalive
    public function ping()
    {
	$response = $this->get_response($this->send_action('Ping'));
	LOG::log('PING? PONG!: '.date("H:i:s", floatval($response["Timestamp"])),5);
	return $response["Timestamp"];
    }
    
    //авторизация на сервере
    protected function login($login, $password)
    {	
	if(!is_resource($this->conn_handle)){LOG::log('Call method '.__METHOD__.' failure. TCP connection is not established.',1);return FALSE;}
	$resp = $this->get_response($this->send_action('login', array('Username' => $login, 'Secret' => $password)));
	if ($resp['Response'] == 'Success')
	{
	    LOG::log('Authentication accepted',4);
	    return TRUE;
	}
	else
	{
	    LOG::log('Authentication failed',2);
	    return FALSE;
	}
	
    }
    
    //завершение сессии на сервере
    protected function logout()
    {
	if(!is_resource($this->conn_handle)){LOG::log('Call method '.__METHOD__.' failure. TCP connection is not established.',1);return FALSE;}
	$resp = $this->get_response($this->send_action('Logoff'));
	LOG::log('Logout... Server goodbye phrase: '.$resp['Message'],4);
    }

    //низкоуровневое получение ответов по ID запроса
    protected function get_response($ActId)
    {	
	$retval = FALSE;
	for($cnt=0;$cnt<500;$cnt++)
	{
	    usleep(10000);
	    if (isset($this->responses[$ActId]))
	    {
		$retval = $this->responses[$ActId];
		unset($this->responses[$ActId]);
		break;
	    }
	    $this->refresh();
	}
	return $retval;
    }
    
    //добавление обработчика событий
    public function add_event_handler($event, $callback)
    {
	$event = strtolower($event);
	if (is_array($callback))
	{
	    $callbackname = get_class($callback[0])."->".$callback[1];
	}
	else
	{
	    $callbackname = $callback;
	}
	if(!is_callable($callback))
	{
	    LOG::log("${callbackname} does not exist! Nothing to add as event handler...",3);
	    return FALSE;
	}
	if (!isset($this->event_handlers[$event]))
	{
	    $this->event_handlers[$event] = $callback;
	    LOG::log('Event handler for events type "'.$event.'" was added as callable "'.$callbackname.'"',4);
	    return TRUE;
	}
	else
	{
	    LOG::log('Event handler for events type "'.$event.'" already exist as callable "'.$this->event_handlers[$event].'"',3);
	    return FALSE;
	}   
    }
    
    //удаление обработчика событий
    public function remove_event_handler($event)
    {
	$event = strtolower($event);
	if (isset($this->event_handlers[$event]))
	{
	    unset($this->event_handlers[$event]);   
	    LOG::log('Event handler for events type "'.$event.'" was removed',4);
	    return TRUE;
	}
	else
	{
	    LOG::log('Event handler for events type "'.$event.'" not exist',3);
	    return FALSE;
	}
    }
    
    //получение callback обработчика
    public function get_event_handler($event)
    {
	$event = strtolower($event);
	if (isset($this->event_handlers[$event]))
	{
	    return $this->event_handlers[$event];
	}
	else
	{
	    return FALSE;
	}
    }


    //подписка на события ami
    public function enable_events($toggle = FALSE)
    {
	if(!is_resource($this->conn_handle)){LOG::log('Call method '.__METHOD__.' failure. TCP connection is not established.',1);return FALSE;}
	if($toggle === TRUE)
	{
	    $eventlist = 'on';
	}
	else
	{
	    $eventlist = 'off';
	}
	    	$ActId = $this->send_action('Events', array('Eventmask' => $eventlist));
	
	$res=$this->get_response($ActId);
	if (isset($res['Events']))
	{
	    if ($res['Events'] == 'On')
	    {
		LOG::log('Events enabled',4);
		return TRUE;
	    }
	}
	LOG::log('Events disabled',4);
	return FALSE;
    }
    
    //обработчик событий
    protected function event_poller()
    {
	foreach ($this->events as $index => $event)
	{
	    
	    $event_name = strtolower($event['Event']);
	    if (isset($this->event_handlers[$event_name]))
	    {
		$run_handler = $this->event_handlers[$event_name];
	    }
	    elseif (isset($this->event_handlers['*']))
	    {
		$run_handler = $this->event_handlers['*'];
	    }
	    else
	    {
		$run_handler = FALSE;
	    }
	    if(is_array($run_handler))
	    {
		$run_handler_name = get_class($run_handler[0])."->".$run_handler[1];
	    }
	    else
	    {
		$run_handler_name = $run_handler;
	    }
	    if ($run_handler === FALSE)
	    {
		LOG::log("Got event '${event_name}', but no handler for processing it.",6);
	    }
	    else
	    {
		LOG::log("Got event '${event_name}', runing '${run_handler_name}' handler for processing it.",5);
		$ret_h_data = call_user_func($run_handler, $event_name, $event);
	    }
	    unset($this->events[$index]);
	}
    }

    //низкоуровневая отправка запросов
    protected function send_action($action,$params = [])
    {	
	if(!is_resource($this->conn_handle)){LOG::log('Call method '.__METHOD__.' failure. TCP connection is not established.',1);return FALSE;}
	if (!is_string($action)){return FALSE;}
	if (!is_array($params)){return FALSE;}
	if (!isset($params['ActionID'])){$params['ActionID'] = uniqid();}
	$packet = 'Action: '.$action."\r\n";
	foreach ($params as $param => $param_value)
	{
	    $packet .= $param.': '.$param_value."\r\n";
	}
	$packet .= "\r\n";
	stream_socket_sendto ($this->conn_handle, $packet);
	$this->refresh();
	return $params['ActionID'];
    }
    
    //обновление данных от сервера
    public function refresh()
    {
	if ($this->refresh_lock){return;}
	$this->refresh_lock = TRUE;
	if(!is_resource($this->conn_handle)){LOG::log('Call method '.__METHOD__.' failure. TCP connection is not established.',1);return FALSE;}
	
	while($this->update_inbound_stream())
	{
	    $this->parse_inbound_stream_buffer();
	}
	$this->event_poller();
	$this->refresh_lock = FALSE;
    }
    //получение одной пачки данных из входящего потока от сервера в буфер пачек
    protected function update_inbound_stream()
    {
	if(!is_resource($this->conn_handle)){LOG::log('Call method '.__METHOD__.' failure. TCP connection is not established.',1);return FALSE;}
	while(TRUE)
	{
	    $raw_data = stream_get_line ($this->conn_handle , 1500, "\r\n"); //получение сырых данных с парсингом по переводу строк
	    if ($raw_data === ''){break;} //пустая строка означает конец пакета
	    if ($raw_data === FALSE){return FALSE;} //false означает осутствие данных
	    $inbound_packet[] = $raw_data;  //формирование пакета для помещения во входной буфер
	}
	if (isset($inbound_packet)) //если пакет сформирован (а бывает и наоборот), то помещаем в буфер, иначе считаем что данных нет
	{
	    $this->inbound_stream_buffer[] = $inbound_packet; 
	    return TRUE;    
	}
	else
	{
	    return FALSE;
	}
    }
    
    //парсер пачек извлекаемых из буфера и помещаемых в буферы ответов и очередей
    protected function parse_inbound_stream_buffer()
    {
	foreach ($this->inbound_stream_buffer as $index => $inbound_packet)
	{
	    foreach ($inbound_packet as $line)
	    {
		$parse_result = preg_match('/(^.[^ ]*): (.*)/', $line, $parsed_line);
		if ($parse_result === 1)
		{
		    $pack[$parsed_line[1]] = $parsed_line[2];
		}
		else
		{
		    $pack['RAW'] = $line;
		}
	    }
	    if (isset($pack['Response']))
	    {
		if ($pack['Response'] == "Error")
		{
		    LOG::log('ERROR RESPONSE: '.$pack["Message"],3);
		}
		$this->responses[$pack['ActionID']] = $pack;
	    }
	    if (isset($pack['Event']))
	    {
		$this->events[] = $pack;
	    }
	    unset($pack);
	    unset($this->inbound_stream_buffer[$index]);	    
	}
    }
}