<?php
/*
 * The singleton pattern for Redis connection
 */
class RedisSingle
{
	private static $REDIS;

	private function __construct()
	{
		self::$REDIS = new Redis();
		self::$REDIS->connect('127.0.0.1', '6379');
	}

	public static function getRedis()
	{
		if (empty(self::$REDIS)) {
			new self();
		}

		return self::$REDIS;
	}

	public static function closeRedis()
	{
		empty(self::$REDIS) || self::$REDIS->close();
	}
}