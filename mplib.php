<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Daemon
{
    private $unique_flag = FALSE;
    private $pidfilename;

    public function __construct()
    {
    }
    
    public function __destruct()
    {
	if ($this->unique_flag)
	{
	    !unlink($this->pidfilename);
	}
	
    }

    public function daemonize()
    {
	$pid = pcntl_fork();
	if ($pid == -1)
        {
                return FALSE;
        }
        elseif ($pid > 0)
        {
            exit;
        }
        umask(0);
        chdir('/');
        if (posix_setsid() == -1)
        {
	    return FALSE;
        }
	
	fclose(STDIN);
	fclose(STDOUT);
	fclose(STDERR);
	$GLOBALS['STDIN'] = fopen('/dev/null', 'r');
	$GLOBALS['STDOUT']= fopen('/dev/null', 'w');
	$GLOBALS['STDERR'] = fopen('/dev/null', 'w');
	
	return TRUE;
    }
    
    public function is_unique($pidfilename)
    {
	$mypid = posix_getpid();
	if (is_readable($pidfilename))
	{
	    $pid = (int)  rtrim(file_get_contents($pidfilename));
	    if ($pid == $mypid)
	    {
		return TRUE;
	    }
	    if ($pid > 0 && posix_kill($pid, 0))
	    {
		return FALSE;
	    }
	}
	return TRUE;

    }
    
    public function no_unique($pidfilename)
    {
	if (!unlink($pidfilename))
	{
	    return FALSE;
	}
    }

    public function do_unique($pidfilename)
    {
	if (!$this->is_unique($pidfilename))
	{
	    return FALSE;
	}

	$mypid = posix_getpid();
	if (!file_put_contents($pidfilename, $mypid.PHP_EOL))
	{
	    return FALSE;
	}
	return TRUE;
    }
}    

class Children 
{
    protected $PIPE;
    protected $ROLE;
    protected static $CHILDREN;
    protected $CHILD;
    protected $PARENT;


    public function __construct()
    {
	self::set_sig_handlers();
	$this->create_child();
	switch ($this->ROLE)
	{
	    case -1:
		return FALSE;
	    case 0:
		return TRUE;
	    case 1:
		$this->ChildBody();
		exit();
	}
	
    }
    
    public function __destruct()
    {
	if ($this->ROLE == 0)
	{
	    posix_kill($this->CHILD, SIGTERM);
	}
    }

    protected function ChildBody()
    {
	
    }

    static function set_sig_handlers()
    {
	pcntl_signal(SIGCHLD, [__CLASS__, 'handler_sigchld']);
    }
    
    public function SendUnixSignal($sig)
    {
	return posix_kill($this->CHILD, $sig);
    }
      
    public function IsAlive()
    {
	if ($this->ROLE == 1)
	{
	    return TRUE;
	}
	if (isset(self::$CHILDREN[$this->CHILD]))
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }

    public static function handler_sigchld()
    {	
	$pid = pcntl_waitpid(0, $status, WNOHANG);
	if($pid > 0)
	{
	    unset(self::$CHILDREN[$pid]);
	}
    }

    protected function create_child()
    {
	$sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
	stream_set_blocking ($sockets[0] , 0);
	stream_set_blocking ($sockets[1] , 0);
	$pid = pcntl_fork();
	if ($pid == -1)
	{
	    $this->ROLE = -1;
	    return FALSE;
	}
	elseif ($pid > 0)
	{
	    // код для родителя
	    $this->CHILD = $pid;
	    self::$CHILDREN[$pid] = $pid;
	    $this->PARENT = FALSE;
	    $this->ROLE = 0;
	    $this->PIPE = &$sockets[0];
	    unset($sockets);
	}
	elseif ($pid == 0)
	{
	    // код для ребёнка
	    $this->CHILD = FALSE;
	    $this->PARENT = posix_getppid();
	    $this->ROLE = 1;
	    $this->PIPE = &$sockets[1];
	    unset($sockets);
	}
	return TRUE;
    }
    
    public function SendEvent($eventname, $data)
    {
	if (!is_resource($this->PIPE))
	{
	    return FALSE;
	}
	$eventpack = base64_encode($eventname).chr(255).chr(0).chr(255).base64_encode(serialize($data)).chr(0).chr(15).chr(240).chr(255);
	
	$res = @stream_socket_sendto($this->PIPE, $eventpack);	
	if ($res == -1)
	{
	    return TRUE;
	}
	else
	{
	    return FALSE;
	}
    }
    
    public function WaitEvent($wait = TRUE)
    {
	if (!is_resource($this->PIPE))
	{
	    return FALSE;
	}
	
	$r = [$this->PIPE];
	if ($wait)
	{
	    while (stream_select($r, $w, $x, 0) == 0)
	    {
		usleep(10000);
		$r = [$this->PIPE];
	    }
	}
	elseif (stream_select($r, $w, $x, 0) == 0)
	{
	    return NULL;
	}
	$ipc_msg = '';
	while (substr($ipc_msg , -4) != chr(0).chr(15).chr(240).chr(255))
	{
	    $rcv_buf = stream_socket_recvfrom($this->PIPE, 1500);
	    var_dump($rcv_buf);
	    if ($rcv_buf == '')
	    {
		return NULL;
	    }
	    $ipc_msg .= $rcv_buf;
	}
	$ipc_msg = substr($ipc_msg,0,-4);
	$ipc_msg = explode(chr(255).chr(0).chr(255), $ipc_msg);
	$retval['eventname'] = base64_decode($ipc_msg[0]);
	$retval['data'] = unserialize(base64_decode($ipc_msg[1]));
	
	return $retval;
	
    }
}
