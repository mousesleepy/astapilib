<?php

class LOG
{
    private static $mode='console';
    private static $verbose = 0;
    public function __construct()
    {
	
    }

    private static function console($message)
    {
	echo date("M d H:i:s"),  substr(microtime(),1,6),' ',$message,PHP_EOL;
    }

    public static function set_verbose($level)
    {
	self::$verbose = (int)$level;
    }
    public static function get_verbose()
    {
	return self::$verbose;
    }

    public static function set_mode($mode)
    {
	self::$mode = $mode;
    }
    public static function get_mode()
    {
	return self::$mode;
    }

    public static function log($message, $level = 9)
    {
	if (self::$verbose < $level)
	{
	    return;
	}
	switch (self::$mode)
	{
	    case 'console':
		self::console($message);
		break;
	    default:
		
	}
    }
}