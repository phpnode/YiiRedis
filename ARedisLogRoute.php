<?php
/**
 * A log route that allows log items to be stored or broadcast by redis.
 * @author Charles Pick
 * @package packages.redis
 */
class ARedisLogRoute extends CLogRoute {
	/**
	 * The name of the redis key to use when storing logs
	 * @var string
	 */
	public $redisKey;

	/**
	 * Whether to broadcast log messages via pub/sub instead of saving them
	 * @var boolean
	 */
	public $useChannel = false;

	/**
	 * Holds the redis connection
	 * @var ARedisConnection
	 */
	protected $_connection;


	/**
	 * Sets the redis connection to use for caching
	 * @param ARedisConnection|string $connection the redis connection, if a string is provided, it is presumed to be a the name of an applciation component
	 */
	public function setConnection($connection)
	{
		if (is_string($connection)) {
			$connection = Yii::app()->{$connection};
		}
		$this->_connection = $connection;
	}

	/**
	 * Gets the redis connection to use for caching
	 * @return ARedisConnection
	 */
	public function getConnection()
	{
		if ($this->_connection === null) {
			if (!isset(Yii::app()->redis)) {
				throw new CException(get_class($this)." expects a 'redis' application component");
			}
			$this->_connection = Yii::app()->redis;
		}
		return $this->_connection;
	}

	/**
	 * Stores or broadcasts log messages via redis.
	 * @param array $logs list of log messages
	 */
	protected function processLogs($logs)
	{
		$redis = $this->getConnection()->getClient();
		if (function_exists("json_encode")) {
			$useCJSON = false;
		}
		else {
			$useCJSON = true;
		}
		foreach($logs as $log) {
			$item = array(
				"level" => $log[1],
				"category" => $log[2],
				"time" => $log[3],
				"message" => $log[0],
			);
			if ($useCJSON) {
				$json = CJSON::encode($item);
			}
			else {
				$json = json_encode($item);
			}
			if ($this->useChannel) {
				$redis->publish($this->redisKey, $json);
			}
			else {
				$redis->zAdd($this->redisKey,$log[3],$json);
			}
		}
	}
}