<?php
/*


*/

require_once(__DIR__.DIRECTORY_SEPARATOR.'common.php');
require_once(__DIR__.DIRECTORY_SEPARATOR.'baseami.php');
require_once(__DIR__.DIRECTORY_SEPARATOR.'timer.php');

class AMI extends baseAMI
{
    protected $semaphores = [];
    protected $TMP = [];
     
    //конструктор установка параметров
    public function __construct($config = [])
    {
	parent::__construct($config);
	//default parameters
	//if (!isset($config['keepalive']){$config['keepalive'] = TRUE;})
	//parse parameters
	foreach ($config as $opt => $val)
	{
	    if ($opt == 'use_dev_state_list' and $val == TRUE)
	    {

	    }
		
	}
    }
    
    //генерирует MD5 challenge для аутентификации (не понятно где применять)
    public function Challenge($authtype)
    {
	$response = $this->get_response($this->send_action('Challenge',array('AuthType' => $authtype)));
	if($response["Response"] == "Success")
	{
	    return $response["Challenge"];
	}
	else
	{
	    return FALSE;
	}
    }

    //возвращает список всех каналов с состояниями DEVSTATE
    public function DeviceStateList()
    {
	$id = $this->Listcmd_CommonConstructor('DeviceStateChange', 'DeviceStateListComplete', 'DeviceStateList');
	if ($id === FALSE)
	{
	    return FALSE;
	}
	$retval = [];
	foreach ($this->TMP[$id] as $val)
	{
	    $retval[$val['Device']] = $val['State']; 
	}
	unset($this->TMP[$id]);
	return $retval;
    }
    
    //возвращает список всех presense
    public function PresenceStateList()
    {
	$id = $this->Listcmd_CommonConstructor('PresenceStateChange', 'PresenceStateListComplete', 'PresenceStateList');
	if ($id === FALSE)
	{
	    return FALSE;
	}
	$retval = [];
	foreach ($this->TMP[$id] as $val)
	{
	    $retval[$val['Presentity']]["Status"] = $val["Status"]; 
	    $retval[$val['Presentity']]["Subtype"] = $val["Subtype"]; 
	    $retval[$val['Presentity']]["Message"] = $val["Message"]; 
	}
	unset($this->TMP[$id]);
	return $retval;
    }
    
    //возвращает все ExtensionState
    public function ExtensionStateList()
    {
	$id = $this->Listcmd_CommonConstructor('ExtensionStatus', 'ExtensionStateListComplete', 'ExtensionStateList');
	if ($id === FALSE)
	{
	    return FALSE;
	}
	$retval = [];
	foreach ($this->TMP[$id] as $val)
	{
	    $retval[$val["Context"]][$val["Exten"]]["Hint"] = $val["Hint"];
    	    $retval[$val["Context"]][$val["Exten"]]["Status"] = $val["Status"]; 
	    $retval[$val["Context"]][$val["Exten"]]["StatusText"] = $val["StatusText"]; 

	}
	unset($this->TMP[$id]);
	return $retval;
    }
    
    //возвращает extensionstate
    public function ExtensionState($Context, $Exten)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('ExtensionState', $params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    unset($response['Response'], $response['ActionID']);
	    return $response;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //возвращает presensestate
    public function PresenceState($Provider)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('PresenceState', $params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    unset($response['Response'], $response['ActionID']);
	    return $response;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //Набор методов для работы со встроенной БД asterisk
    public function DBPut($Family, $Key, $Value=NULL)
    {	
	$params = $this->make_params(get_defined_vars());
	$ActId = $this->send_action('DBPut', $params);
	$response = $this->get_response($ActId);
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }

    //реализация DBGet
    public function DBGet($Family, $Key)
    {	
	$params = $this->make_params(get_defined_vars());
	$id = $this->Listcmd_CommonConstructor('dbgetresponse', 'dbgetcomplete', 'DBGet',$params);
	if ($id === FALSE)
	{
	    return FALSE;
	}
	$retval = $this->TMP[$id][0]['Val'];
	unset($this->TMP[$id]);
	return $retval;
    }
    
    //реализация DBDelTree
    public function DBDelTree($Family, $Key = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('DBDelTree', $params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //реализация DBDel
    public function DBDel($Family, $Key)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('DBDel', $params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //получение переменной
    public function Getvar($Variable, $Channel = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('Getvar', $params));
	if ($response['Response'] == 'Success')
	{
	    return $response['Value'];
	}
	else
	{
	    return FALSE;
	}
    }
    
    //установка переменной
    public function Setvar($Value, $Variable, $Channel = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('Setvar', $params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //возвращает dialplan или его отдельные части
    public function ShowDialPlan($Context = NULL, $Extension = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$id = $this->Listcmd_CommonConstructor('listdialplan', 'showdialplancomplete', 'ShowDialPlan',$params);
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;
    }
    
    //Hangup на канале
    public function Hangup($Channel, $Cause = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$id = $this->Listcmd_CommonConstructor('channelhungup', 'channelshunguplistcomplete', 'Hangup',$params);
	if ($id === FALSE)
	{
	    return FALSE;
	}
	$retval = [];
	foreach ($this->TMP[$id] as $val)
	{
	    $retval[] = $val['Channel'];
	}
	unset($this->TMP[$id]);
	return $retval;
    }
    
    //отправка сообщения
    public function MessageSend($To, $From = NULL, $Body = NULL, $Variable = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	if(isset($params['Body']))
	{
	    if(strpos($params['Body'], "\n") !== FALSE)
	    {
		$params['Base64Body'] = base64_encode($params['Body']);
		unset($params['Body']);
	    }
	}
	if(isset($params['Variable']))
	{
	    $var = $params['Variable'];
	    $params['Variable'] = '';
	    foreach ($var as $varname => $varval)
	    {
		$params['Variable'] .= $varname.'='.$varval.',';
	    }
	    $params['Variable'] = substr($params['Variable'], 0, -1);	    
	}
	$response = $this->get_response($this->send_action('MessageSend', $params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //отправка текста в канал во время звонка
    public function SendText($Channel, $Message)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('SendText', $params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    
    //отправка CLI комманды
    public function Command($cmd)
    {
	$response = $this->get_response($this->send_action('Command', ['Command' => $cmd]));
	return $response['RAW'];
    }
    
    //получение списка комманд
    public function ListCommands()
    {
	$response = $this->get_response($this->send_action('ListCommands'));
	unset($response['Response'], $response['ActionID']);
	return $response;
    }
    
    //реализация originate
    public function Originate($Channel, $Context = NULL, $Exten = NULL, $Priority = NULL, $Application = NULL, $Data = NULL, $Timeout = NULL, $CallerID = NULL, $Variable = NULL, $Account = NULL, $EarlyMedia = NULL, $Codecs = NULL, $ChannelId = NULL, $OtherChannelId = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$params['Async'] = 'true';
	if (isset($params['Context']) or isset($params['Exten']) or isset($params['Priority']))
	{
	    if (!isset($params['Context']) or !isset($params['Exten']) or !isset($params['Priority']))
	    {
		LOG::log(__METHOD__.' CONTEXT, EXTENSION and PRIORITY must be setted if you use one of them.', 3);
		return FALSE;
	    }
	}
	if ((isset($params['Application'])) and (isset($params['Context']) or isset($params['Exten']) or isset($params['Priority'])))
	{
	    LOG::log(__METHOD__.' CONTEXT, EXTENSION and PRIORITY must not be setted if you use Application.', 3);
	    return FALSE;
	}
	if (!isset($params['Application']) and isset($params['Data']))
	{
	    LOG::log(__METHOD__.' DATA must not be setted if you not use Application.', 3);
	    return FALSE;
	}
	if (isset($params['Timeout']))
	{
	    $params['Timeout'] = $params['Timeout'] * 1000;
	}
	if (isset($params['Variable']))
	{
	    if (!is_array($params['Variable']))
	    {
		LOG::log(__METHOD__.' VARIABLE must be an array.', 3);
		return FALSE;
	    }
	    $variable = $params['Variable'];
	    $params['Variable'] = '';
	    foreach ($variable as $varname => $varvalue)
	    {
		$params['Variable'] .= $varname.'='.$varvalue.',';
	    }
	    $params['Variable'] = substr($params['Variable'], 0, -1);
	}

	if (isset($params['Codecs']))
	{
	    if (!is_array($params['Codecs']))
	    {
		LOG::log(__METHOD__.' CODECS must be an array.', 3);
		return FALSE;
	    }
	    $codecs = $params['Codecs'];
	    $params['Codecs'] = '';
	    foreach ($codecs as $varvalue)
	    {
		$params['Codecs'] .= $varvalue.',';
	    }
	    $params['Codecs'] = substr($params['Codecs'], 0, -1);
	}
	
	$response = $this->get_response($this->send_action('Originate',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return $response['ActionID'];
	}
	else
	{
	    return FALSE;
	}
    }
    
    //возвращает колличество сообщений в голосовой почте
    public function MailboxCount($Mailbox)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('MailboxCount',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    unset($response['Response'], $response['ActionID'],$response['Message']);
	    return $response;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //возвращает колличество сообщений в голосовой почте
    public function MailboxStatus($Mailbox)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('MailboxStatus',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    unset($response['Response'], $response['ActionID'],$response['Message']);
	    return $response;
	}
	else
	{
	    return FALSE;
	}
    }

    //установка абсолютного таймаута на канале
    public function AbsoluteTimeout($Channel, $Timeout)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('AbsoluteTimeout', $params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //Optimize away a local channel when possible.
    public function LocalOptimizeAway($chan)
    {
	$response = $this->get_response($this->send_action('LocalOptimizeAway',['Channel' => $chan]));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //Send an event to manager sessions.
    public function UserEvent($event, $eventpack)
    {
	$eventpack['UserEvent'] = $event;
	$response = $this->get_response($this->send_action('UserEvent',$eventpack));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //Show PBX core status variables.
    public function CoreStatus()
    {
	$response = $this->get_response($this->send_action('CoreStatus'));
	unset($response['Response'], $response['ActionID']);
	return $response;
    }
    
    //Show PBX core settings (version etc).
    public function CoreSettings()
    {
	$response = $this->get_response($this->send_action('CoreSettings'));
	unset($response['Response'], $response['ActionID']);
	return $response;
    }
    
    //Send a reload event.
    public function Reload($Module = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('Reload',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }

        //Send a reload event.
    public function LoggerRotate()
    {
	$response = $this->get_response($this->send_action('Reload'));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }

    //Attended transfer.
    public function Atxfer($Channel, $Exten, $Context = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('Atxfer',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
  
    //Blind transfer channel(s) to the given destination
    public function BlindTransfer($Channel, $Exten, $Context = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('BlindTransfer',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }

    //Redirect (transfer) a call.
    public function Redirect($Channel, $Exten, $Context, $Priority)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('Redirect',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    public function CoreShowChannels()
    {
	$id = $this->Listcmd_CommonConstructor('CoreShowChannel', 'CoreShowChannelsComplete', 'CoreShowChannels');
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }
    
    //Play DTMF signal on a specific channel.
    public function PlayDTMF($Channel, $digits, $Duration = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	unset($params['digits']);
	if (strlen($digits) == 0)
	{
	    LOG::log(__METHOD__.' '.'Parameter DIGITS is empty', 5);
	    return FALSE;
	}
	for ($c = 0; $c < strlen($digits); $c++)
	{
	    $params['Digit'] = $digits{$c};
	    $response = $this->get_response($this->send_action('PlayDTMF',$params));
	    usleep(70000);
	}
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //Bridge two channels already in the PBX.
    public function Bridge($Channel1, $Channel2, $Tone = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('Bridge',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //List available bridging technologies and their statuses.
    public function BridgeTechnologyList()
    {
	$id = $this->Listcmd_CommonConstructor('bridgetechnologylistitem', 'bridgetechnologylistcomplete', 'BridgeTechnologyList');
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }
    
    //Suspend a bridging technology.
    public function BridgeTechnologySuspend($BridgeTechnology)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('BridgeTechnologySuspend',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //Unsuspend a bridging technology.
    public function BridgeTechnologyUnsuspend($BridgeTechnology)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('BridgeTechnologyUnsuspend',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //Get a list of bridges in the system.
    public function BridgeList($BridgeType = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$id = $this->Listcmd_CommonConstructor('bridgelistitem', 'bridgelistcomplete', 'BridgeList',$params);
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }
    
    //Get information about a bridge.
    public function BridgeInfo($BridgeUniqueid)
    {
	$params = $this->make_params(get_defined_vars());  
	$id = $this->Listcmd_CommonConstructor('BridgeInfoChannel', 'BridgeInfoComplete', 'BridgeInfo',$params);
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }
    
    //Destroy a bridge.
    public function BridgeDestroy($BridgeUniqueid)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('BridgeDestroy',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }

    //Kick a channel from a bridge.
    public function BridgeKick($Channel, $BridgeUniqueid = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('BridgeKick',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }    


    //Tell Asterisk to poll mailboxes for a change
    public function VoicemailRefresh($Context = NULL, $Mailbox = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('VoicemailRefresh',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }    
    
    //Get information about a bridge.
    public function VoicemailUsersList()
    {
	$id = $this->Listcmd_CommonConstructor('voicemailuserentry', 'voicemailuserentrycomplete', 'VoicemailUsersList');
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }
    
    //Tell Asterisk to poll mailboxes for a change
    public function MuteAudio($Channel, $Direction, $State)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('MuteAudio',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }    
    
    //Control the playback of a file being played to a channel
    public function ControlPlayback($Channel, $Control)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('ControlPlayback',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }    
    
    //Check the status of one or more queues.
    public function QueueStatus($Queue = NULL, $Member = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$id = $this->Listcmd_CommonConstructor('queueparams', 'queuestatuscomplete', 'QueueStatus', $params);
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$queueparams = $this->TMP[$id];
	unset($this->TMP[$id]);
		
	$id = $this->Listcmd_CommonConstructor('queuemember', 'queuestatuscomplete', 'QueueStatus', $params);
	
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$queuemember = $this->TMP[$id];
	unset($this->TMP[$id]);
	$retval['queueparams'] = $queueparams;
	$retval['queuemember'] = $queuemember;
	return $retval;	
    }
    
    
    //Request the manager to send a QueueSummary event.
    public function QueueSummary($Queue = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$id = $this->Listcmd_CommonConstructor('queuesummary', 'queuesummarycomplete', 'QueueSummary',$params);
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }
    
    //Add interface to queue.
    public function QueueAdd($Queue, $Interface, $Penalty = NULL, $Paused = NULL, $MemberName = NULL, $StateInterface = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('QueueAdd',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }    

    //Remove interface from queue.
    public function QueueRemove($Queue, $Interface)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('QueueRemove',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }    
 
    //Makes a queue member temporarily unavailable.
    public function QueuePause($Interface, $Paused, $Queue = NULL, $Reason = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('QueuePause',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }    

    //Adds custom entry in queue_log.
    public function QueueLog($Queue, $Event, $Reason, $Message = NULL, $Interface = NULL, $Uniqueid = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('QueueLog',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }    

    //Set the penalty for a queue member.
    public function QueuePenalty($Interface, $Penalty, $Queue = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('QueuePenalty',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }    

    //Set the ringinuse value for a queue member.
    public function QueueMemberRingInUse($Interface, $RingInUse, $Queue = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('QueueMemberRingInUse',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }    
    
    //List queue rules defined in queuerules.conf
    public function QueueRule ($Rule = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('QueueRule',$params));
	if ($response['Response'] != 'Success')
	{
	    return FALSE;
	}
	unset($response['Response'], $response['ActionID']);
	$retval = $response;
	return $retval;	
    }
    
    //Reload a queue, queues, or any sub-section of a queue or queues.
    public function QueueReload ($Queue = NULL, $Members = NULL, $Rules = NULL, $Parameters = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('QueueReload',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }    
    
    //Reset queue statistics.
    public function QueueReset ($Queue = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('QueueReset',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }    

    //Record a call and mix the audio during the recording.
    public function MixMonitor ($Channel, $File = NULL, $options = NULL, $Command = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('MixMonitor',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}
    }    
    
    //Stop recording a call through MixMonitor, and free the recording's file handle.
    public function StopMixMonitor ($Channel, $MixMonitorID = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('StopMixMonitor',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}
    }    
    
    //Mute / unMute a Mixmonitor recording.
    public function MixMonitorMute ($Channel, $Direction = NULL, $State = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('MixMonitorMute',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}
    }    
    
    //Monitor a channel.
    public function Monitor ($Channel, $File = NULL, $Format = NULL, $Mix = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('Monitor',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}
    }    
    
    //Stop monitoring a channel.
    public function StopMonitor ($Channel)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('StopMonitor',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}
    }    
    
    //Change monitoring filename of a channel.
    public function ChangeMonitor ($Channel, $File)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('StopMonitor',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}
    }    
    
    //Pause monitoring of a channel.
    public function PauseMonitor ($Channel)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('PauseMonitor',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}
    }    
    
    //Unpause monitoring of a channel.
    public function UnpauseMonitor ($Channel)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('UnpauseMonitor',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}
    }    
    
    //Lists active FAX sessions
    public function FAXSessions()
    {
	$id = $this->Listcmd_CommonConstructor('faxsessionsentry', 'FAXSessionsComplete', 'FAXSessions');
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }

    //Responds with fax statistics
    public function FAXStats()
    {
	$id = $this->EventAsVal('faxstats', 'FAXStats');
	if ($id === FALSE)
	{
	    return FALSE;
	}	
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	unset($retval['Event']);
	unset($retval['ActionID']);
	return $retval;	
    }

    //Responds with a detailed description of a single FAX session
    public function FAXSession ($SessionNumber)
    {
	$params = $this->make_params(get_defined_vars());
	$id = $this->EventAsVal('faxsession', 'FAXSession',$params);
	if ($id === FALSE)
	{
	    return FALSE;
	}	
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	unset($retval['Event']);
	unset($retval['ActionID']);
	return $retval;	
    }    
    
    //Lists agents and their status.
    public function Agents()
    {
	$id = $this->Listcmd_CommonConstructor('Agents', 'AgentsComplete', 'Agents');
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }
    
    //Sets an agent as no longer logged in.
    public function AgentLogoff ($Agent, $Soft = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('AgentLogoff',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}
    }    
    
    //Park a channel.
    public function Park ($Channel, $TimeoutChannel = NULL, $AnnounceChannel = NULL, $Timeout = NULL, $Parkinglot = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('Park',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }    
    
    //List parked calls.
    public function ParkedCalls ($ParkingLot = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$id = $this->Listcmd_CommonConstructor('parkedcall', 'parkedcallscomplete', 'ParkedCalls',$params);
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }
    
    //Get a list of parking lots
    public function Parkinglots ()
    {
	$id = $this->Listcmd_CommonConstructor('parkinglot', 'parkinglotscomplete', 'Parkinglots');
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }
    
    //Module management
    public function ModuleLoad ($LoadType, $Module = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('ModuleLoad',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}	
    }
    
    //Check if module is loaded
    public function ModuleCheck ($Module)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('ModuleCheck',$params));
	if ($response['Response'] == 'Success')
	{
	    return $response['Version'];
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}	
    }

    //List channel status
    public function Status ($Channel = NULL, $Variables = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$id = $this->Listcmd_CommonConstructor('Status', 'StatusComplete', 'Status',$params);
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }
    
    //Add an extension to the dialplan
    public function DialplanExtensionAdd ($Context, $Extension, $Priority, $Application, $ApplicationData = NULL, $Replace = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('DialplanExtensionAdd',$params));
	if ($response['Response'] == 'Success')
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}	
    }
    
    //Remove an extension from the dialplan
    public function DialplanExtensionRemove ($Context, $Extension, $Priority = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('DialplanExtensionRemove',$params));
	if ($response['Response'] == 'Success')
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}	
    }
        
    //List SIP peers (text format).
    public function SIPpeers ()
    {
	$params = $this->make_params(get_defined_vars());
	$id = $this->Listcmd_CommonConstructor('peerentry', 'PeerlistComplete', 'SIPpeers',$params);	
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }

    //show SIP peer (text format).
    public function SIPshowpeer ($Peer)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('SIPshowpeer',$params));
	if ($response['Response'] == 'Success')
	{
	    unset($response['Response']);
	    unset($response['ActionID']);
	    return $response;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}	
    }

    
    //Qualify SIP peers.
    public function SIPqualifypeer ($Peer)
    {
	$params = $this->make_params(get_defined_vars());
	//$response = $this->get_response($this->send_action('SIPqualifypeer',$params));
	$id  = $this->EventAsVal('sipqualifypeerdone', 'SIPqualifypeer',$params);
	if ($id === FALSE)
	{
	    return FALSE;
	}	
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	unset($retval['Event']);
	unset($retval['ActionID']);
	return $retval;
    }
    
    //Show SIP registrations (text format).
    public function SIPshowregistry ()
    {
	$params = $this->make_params(get_defined_vars());
	$id = $this->Listcmd_CommonConstructor('registryentry', 'registrationscomplete', 'SIPshowregistry',$params);	
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }
    
     //Send a SIP notify.
    public function SIPnotify ($Channel, $Variable)
    {
	$params = $this->make_params(get_defined_vars());
	if(isset($params['Variable']))
	{
	    if (!is_array($params['Variable']))
	    {
		LOG::log(__METHOD__.' Argument "Variable" must be an array!' , 5);
		return FALSE;
	    }
	    $var = $params['Variable'];
	    $params['Variable'] = '';
	    foreach ($var as $varname => $varval)
	    {
		$params['Variable'] .= $varname.'='.$varval.',';
	    }
	    $params['Variable'] = substr($params['Variable'], 0, -1);	    
	}
	$response = $this->get_response($this->send_action('SIPnotify',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}	
    }

    //Show the status of one or all of the sip peers.
    public function SIPpeerstatus ($Peer = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$id = $this->Listcmd_CommonConstructor('peerstatus', 'sippeerstatuscomplete', 'SIPpeerstatus',$params);	
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }

    //Set the file used for PRI debug message output
    public function PRIDebugFileSet ($File)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('PRIDebugFileSet',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}	
    }

    //Disables file output for PRI debug messages
    public function PRIDebugFileUnset ()
    {
	$response = $this->get_response($this->send_action('PRIDebugFileUnset'));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}	
    }


    //Set PRI debug levels for a span
    public function PRIDebugSet ($Span, $Level)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('PRIDebugSet',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}	
    }

    //Show status of PRI spans.
    public function PRIShowSpans ($Span = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$id = $this->Listcmd_CommonConstructor('prishowspans', 'prishowspanscomplete', 'PRIShowSpans',$params);	
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }

    //Show status of DAHDI channels.
    public function DAHDIShowChannels ($DAHDIChannel = NULL)
    {
	$params = $this->make_params(get_defined_vars());
	$id = $this->Listcmd_CommonConstructor('dahdishowchannels', 'dahdishowchannelscomplete', 'DAHDIShowChannels',$params);	
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }
    
    //Toggle DAHDI channel Do Not Disturb status ON.
    public function DAHDIDNDon ($DAHDIChannel)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('DAHDIDNDon',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}	
    }

    //Toggle DAHDI channel Do Not Disturb status OFF.
    public function DAHDIDNDoff ($DAHDIChannel)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('DAHDIDNDoff',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}	
    }
    
    //Dial over DAHDI channel while offhook.
    public function DAHDIDialOffhook ($DAHDIChannel, $Number)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('DAHDIDialOffhook',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}	
    }

    //Hangup DAHDI Channel.
    public function DAHDIHangup ($DAHDIChannel)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('DAHDIHangup',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}	
    }

    //Transfer DAHDI Channel.
    public function DAHDITransfer ($DAHDIChannel)
    {
	$params = $this->make_params(get_defined_vars());
	$response = $this->get_response($this->send_action('DAHDITransfer',$params));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}	
    }

    //Fully Restart DAHDI channels (terminates calls).
    public function DAHDIRestart ()
    {
	$response = $this->get_response($this->send_action('DAHDIRestart'));
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    LOG::log(__METHOD__.' '.$response['Message'], 5);
	    return FALSE;
	}	
    }

    //List participants in a conference.
    public function ConfbridgeList ($Conference)
    {
	$params = $this->make_params(get_defined_vars());
	$id = $this->Listcmd_CommonConstructor('ConfbridgeList', 'ConfbridgeListComplete', 'ConfbridgeList',$params);	
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }


    //List active conferences.
    public function ConfbridgeListRooms ()
    {
	$params = $this->make_params(get_defined_vars());
	$id = $this->Listcmd_CommonConstructor('ConfbridgeListRooms', 'ConfbridgeListRoomsComplete', 'ConfbridgeListRooms',$params);	
	if ($id === FALSE)
	{
	    return FALSE;
	}
	array_walk($this->TMP[$id], function (&$a1){unset($a1['Event']);unset($a1['ActionID']);});
	$retval = $this->TMP[$id];
	unset($this->TMP[$id]);
	return $retval;	
    }
 
    //Mute a Confbridge user.
    public function ConfbridgeMute ($Conference, $Channel)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('ConfbridgeMute',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }

    //Unmute a Confbridge user.
    public function ConfbridgeUnmute ($Conference, $Channel)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('ConfbridgeUnmute',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //Kick a Confbridge user.
    public function ConfbridgeKick ($Conference, $Channel)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('ConfbridgeKick',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }

    //Lock a Confbridge conference.
    public function ConfbridgeLock ($Conference)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('ConfbridgeLock',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }

    //Unlock a Confbridge conference.
    public function ConfbridgeUnlock ($Conference)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('ConfbridgeUnlock',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }

    //Start recording a Confbridge conference.
    public function ConfbridgeStartRecord ($Conference, $RecordFile = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('ConfbridgeStartRecord',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }

    //Stop recording a Confbridge conference.
    public function ConfbridgeStopRecord ($Conference)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('ConfbridgeStopRecord',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //Set a conference user as the single video source distributed to all other participants.
    public function ConfbridgeSetSingleVideoSrc ($Conference, $Channel)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('ConfbridgeSetSingleVideoSrc',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    //Creates an empty file in the configuration directory.
    public function CreateConfig ($Filename)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('CreateConfig',$params));
	LOG::log(__METHOD__.' '.$response['Message'], 5);
	if ($response['Response'] == 'Success')
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }

    //Retrieve configuration.
    public function GetConfig ($Filename, $Category = NULL, $Filter = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('GetConfig',$params));
	if ($response['Response'] != 'Success')
	{
	    return FALSE;
	}
	unset($response['Response'], $response['ActionID']);
	$retval = $response;
	return $retval;	
    }
    
    //Retrieve configuration (JSON format).
    public function GetConfigJSON ($Filename, $Category = NULL, $Filter = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('GetConfigJSON',$params));
	if ($response['Response'] != 'Success')
	{
	    return FALSE;
	}
	unset($response['Response'], $response['ActionID']);
	$retval = $response;
	return $retval;	
    }    

    //List categories in configuration file.
    public function ListCategories ($Filename)
    {
	$params = $this->make_params(get_defined_vars());	
	$response = $this->get_response($this->send_action('ListCategories',$params));
	if ($response['Response'] != 'Success')
	{
	    return FALSE;
	}
	unset($response['Response'], $response['ActionID']);
	$retval = $response;
	return $retval;	
    }    

    //Update basic configuration.
    public function UpdateConfig ($SrcFilename, $DstFilename, $Reload = NULL, $Action = NULL, $Cat = NULL, $Var = NULL, $Value = NULL, $Match = NULL, $Line = NULL, $Options = NULL)
    {
	$params = $this->make_params(get_defined_vars());	
	foreach ($params as $key => $value)
	{
	    if ($key != 'SrcFilename' && $key != 'DstFilename' && $key != 'Reload')
	    {
		$params[$key.'-000000'] = $value;
		unset($params[$key]);
	    }
	}
	$response = $this->get_response($this->send_action('UpdateConfig',$params));
	if ($response['Response'] != 'Success')
	{
	    return FALSE;
	}
	else
	{
	    return TRUE;
	}	
    }    
    
  
    ////////////////////////////////////////////////////////////////////////////
    
    //конструктор запросов и сборщик множественных событий
    protected function Listcmd_CommonConstructor ($unit_event, $complete_event, $init_action, $params = [])
    {
	$old_ev_hdl = $this->get_event_handler($unit_event);
	if ($old_ev_hdl !== FALSE)
	{
	    $this->remove_event_handler($unit_event);
	}
	$this->add_event_handler($unit_event, array(&$this,'grouped_events_hdl'));	
	$this->add_event_handler($complete_event, array(&$this,'ListComplete_hdl'));
	$this->refresh_lock = TRUE;
	$id = $this->send_action($init_action,$params);
	$this->TMP[$id] = [];
	$this->set_semaphore($id, FALSE);
	$this->refresh_lock = FALSE;
	$response = $this->get_response($id);	
	if ($response['Response'] != 'Success')
	{
	    $this->set_semaphore($id, TRUE);
	}
	$this->wait_semaphore($id);
	$this->remove_event_handler($unit_event);
	$this->remove_event_handler($complete_event);
	if ($old_ev_hdl !== FALSE)
	{
	    $this->add_event_handler($unit_event, $old_ev_hdl);
	}
	if ($response['Response'] == 'Success')
	{
	    return $id;
	}
	else
	{
	    unset($this->TMP[$id]);
	    return FALSE;
	}
    }
    
    //обработчик групированных событий
    protected function grouped_events_hdl( $event_name, $event)
    {	
	if (isset($event["ActionID"]))
	{
	    $this->TMP[$event["ActionID"]][] = $event;
	}
    }
    
    //обработчик конца списка группированных событий
    protected function ListComplete_hdl( $event_name, $event)
    {
	$this->set_semaphore($event["ActionID"], TRUE);
	
    }
        
    //Ожидание события, возврат события
    protected function EventAsVal($unit_event, $init_action, $params = [])
    {
	$old_ev_hdl = $this->get_event_handler($unit_event);
	if ($old_ev_hdl !== FALSE)
	{
	    $this->remove_event_handler($unit_event);
	}
	$this->add_event_handler($unit_event, array(&$this,'one_events_hdl'));
	$this->refresh_lock = TRUE;
	$id = $this->send_action($init_action, $params);
	$this->TMP[$id] = [];
	$this->set_semaphore($id, FALSE);
	$this->refresh_lock = FALSE;
	$response = $this->get_response($id);	
	if ($response['Response'] != 'Success')
	{
	    $this->set_semaphore($id, TRUE);
	}
	$this->wait_semaphore($id);
	$this->remove_event_handler($unit_event);
	if ($old_ev_hdl !== FALSE)
	{
	    $this->add_event_handler($unit_event, $old_ev_hdl);
	}
	if ($response['Response'] == 'Success')
	{
	    return $id;
	}
	else
	{
	    unset($this->TMP[$id]);
	    return FALSE;
	}	
    }

    //обработчик получения одиночного события
    protected function one_events_hdl( $event_name, $event)
    {	
	if (isset($event["ActionID"]))
	{
	    $this->TMP[$event["ActionID"]] = $event;
	    $this->set_semaphore($event["ActionID"], TRUE);
	}
	
    }

    
    //установка семафора
    protected function set_semaphore($sem,$val)
    {
        $this->semaphores[$sem] = $val;
    }
    
    //ожидание разрешающего семафора
    protected function wait_semaphore($sem)
    {
	if(!isset($this->semaphores[$sem]))
	{
	    return FALSE;
	}
	while (!$this->semaphores[$sem])
	{
	    usleep(10000);
	}
	unset($this->semaphores[$sem]);
	return TRUE;
    }
    
    //подготовка параметров для передачи
    protected function make_params($inparams)
    {
	$retval = [];
	foreach ($inparams as $pname => $pval)
	{
	    if (!is_null($pval))
	    {
		$retval[$pname] = $pval;
	    }
	}
	return $retval;
    }
}