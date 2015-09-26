<?php
/**
 * Handling database connection
 *
 */
class Database {
	private static $connection = NULL;
	
	/**
	 * private constructor
	 */
	private function __construct() { }

	/**
	 * Establishing database connection
	 * @return database connection handler
	 */
	public static function getInstance() {
		$dbData = Config::load('database');
		
		if(self::$connection === NULL)
		{
			try {
				self::$connection = new PDO("mysql:host=".$dbData['DB_HOST'].";dbname=".$dbData['DB_NAME'].";charset=utf8", $dbData['DB_USERNAME'], $dbData['DB_PASSWORD']);
				self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch(PDOException $e) {
				die("Failed to connect to MySQL: " . $e->getMessage());
			}
		}

		return self::$connection;
	}
}