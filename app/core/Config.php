<?php

class Config {
	
	private function __construct() {}
	
	public static function load($name)
	{
		if(file_exists(APP_ROOT . "/config/" . $name . ".php"))
		{
			require(APP_ROOT . "/config/" . $name . ".php");
			if(isset($config) &&
				is_array($config))
			{
				return $config;
			} else {
				throw new RuntimeException("Bad format of config.");
			}
		}
		
		throw new RuntimeException("Config doesn't exist.");
	}
	
}