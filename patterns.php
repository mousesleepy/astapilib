<?php

abstract class Singleton {
	protected static $instance;

	protected function __construct(){}
	private function __clone(){}
	private function __wakeup(){}

	public static function getInstance()
	{
		$class = get_called_class();
		if(!isset(self::$instance[$class]))
			self::$instance[$class] = new static();
		return self::$instance[$class];
	}
}

interface Observer {
	public function notify($obj);
}

interface Observable {
	public function registerObserver(Observer $obj);
	public function unregisterObserver(Observer $obj);
	public function notifyObservers();
}

