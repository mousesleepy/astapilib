#!/usr/bin/env php
<?php
declare (ticks=10);
$TF = basename(__FILE__);
$EXT = strrchr($TF, '.');
if ($EXT === FALSE)
{
    $CFGFILENAME = $TF.'.ini';
}
else
{
    $CFGFILENAME = strstr($TF,$EXT,TRUE).'.ini';
}
unset($EXT,$TF);
$INI = @parse_ini_file($CFGFILENAME);
if($INI === FALSE)
{
    echo 'Config file ', $CFGFILENAME,' not found!',PHP_EOL;
    exit();
}
if(!isset($INI['watchdog_name']) || !isset($INI['observable_pidfile']) || !isset($INI['restart_cmd']) || !isset($INI['check_interval']) || !isset($INI['logfile']))
{
    echo 'Config file ', $CFGFILENAME,' is broken!',PHP_EOL;
    exit();
}
$PROCPIDFIE = $INI['observable_pidfile'];
$RESTART_EXEC = $INI['restart_cmd'];
$WD_NAME = $INI['watchdog_name'];
$CHECK_INTERVAL = (int)$INI['check_interval'];
$LOGFILE = $INI['logfile'];
unset($INI);

function LogToFile($msg)
{
    global $LOGFILE, $WD_NAME;
    $data = date('M d H:i:s').' watchdog '.$WD_NAME.' '.$msg.PHP_EOL;
    return file_put_contents($LOGFILE, $data, FILE_APPEND);
}
require_once 'mplib.php';
$PROCRUN = TRUE;
$MYPIDFILE = "/var/run/wd_".$WD_NAME;
if (posix_geteuid() != 0)
{
    echo 'You need run this program as root! Aborted...',PHP_EOL;
    exit();
}
$mode = FALSE;
if (isset($argv[1]))
{
    if ($argv[1] == 'start' || $argv[1] == 'stop')
    {
	$mode = $argv[1];
    }
}
if (!$mode)
{
    echo 'Usage: ',  basename(__FILE__),' [start | stop]'.PHP_EOL;
    exit();
}
if ($mode == 'stop')
{
    $DPID = @file_get_contents($MYPIDFILE);
    if (!$DPID)
    {
	echo 'PID file not found...',PHP_EOL;
	exit();
    }
    if (!posix_kill(rtrim($DPID), SIGTERM))
    {
	echo 'Nothing to stop...',PHP_EOL;
    }
    else
    {
	sleep(2);
	if(posix_getpgid (rtrim($DPID)) === FALSE)
	{
	    echo 'Stopped!',PHP_EOL;
	    LogToFile('watchdog stoped');
	}
	else
	{
	    echo 'Not stopped!',PHP_EOL;
	    var_dump(posix_getpgid (rtrim($DPID)));
	}
    }
    exit();
}

$D = new Daemon();
if(!$D->is_unique($MYPIDFILE))
{    
    echo 'Watchdog already started!',PHP_EOL;
    exit();    
}
else
{
    echo 'Watchdog started! Named: ',$WD_NAME,PHP_EOL;
    LogToFile('watchdog started');
}

$D->no_unique($MYPIDFILE);
$D->daemonize();
$D->do_unique($MYPIDFILE);
while (true)
{
    $PROCPID = @file_get_contents($PROCPIDFIE);
    if ($PROCPID === FALSE)
    {
	$PROCRUN = FALSE;
    }
    else
    {
	$PROCRUN = posix_getpgid (rtrim($PROCPID));	
    }

    if($PROCRUN === FALSE)
    {
	LogToFile('observable process is down! Try to respawn.');
	exec($RESTART_EXEC,$output,$statuscode);
	if ($statuscode != 0)
	{
	    LogToFile('observable process was spawned unsuccessful, statuscode is '.$statuscode);
	}
	else
	{
	    LogToFile('observable process was spawned successful');
	}
    }    
    sleep($CHECK_INTERVAL);
}

