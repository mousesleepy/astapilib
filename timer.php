<?php
register_tick_function(array('Timer','poll'));

class Timer
{
    private $id; //метка
    private $time; //таймер
    private $IsStop; //флаг остановленности true/false
    private $UserCall; //задача
    private $UserCallArg; //параметр к задаче
    private $StartTime; //момент запуска таймера
    private $ltime; //оставшееся время
    private $type; //тип true регенерируемый, false не регенерируемый
    private static $timers = []; 

    public function __construct($time, $callable,  $type = false) 
    {
        $this->id = uniqid();
        $this->time = $time;
        $this->UserCall = $callable;
        $this->type = $type;
        $this->start();
        self::$timers[$this->id] = &$this;		
    }

    public function __destruct() 
    {
	unset(self::$timers[$this->id]);
    }

    private function getId()
    {
	return $this->id;
    }

    public function getTimeLeft()
    {
	    return $this->ltime;
    }

    public function setTimeLeft($time)
    {
	    $this->time = $time;
    }


    public function reset()
    {
	$this->StartTime = microtime(true);
	$this->IsStop = FALSE;
    }

    public function start() 
    {
	$this->reset();
	$this->IsStop = FALSE;
    }

    public function stop() 
    {
	$this->IsStop = TRUE;
    }

    public static function poll()
    {
	foreach (self::$timers as $timer)
	{
	    if ($timer->IsStop == FALSE)
	    {
		$timer->update();
	    }
	}
    }

    private function update()
    {
	$this->ltime = $this->StartTime - microtime(true) + $this->time;
	if ($this->ltime <= 0)
	{
	    $this->ltime = 0;
	    $this->task();
	    if ($this->type == FALSE)
	    {
		$this->stop();
	    }
	    else
	    {
		$this->reset();
	    }
	}
    }

    private function task() 
    {
	call_user_func_array ($this->UserCall, $this->UserCallArg = []);
    }
}
