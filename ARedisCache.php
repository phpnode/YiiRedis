<?php
/**
 * A cache component that allows items to be cached using redis.
 * @author Charles Pick
 * @package packages.redis
 */
class ARedisCache extends CCache {
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
				throw new CException("ARedisCache expects a 'redis' application component");
			}
			$this->_connection = Yii::app()->redis;
		}
		return $this->_connection;
	}

	/**
	 * Retrieves a value from cache with a specified key.
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key a unique key identifying the cached value
	 * @return string the value stored in cache, false if the value is not in the cache or expired.
	 */
	protected function getValue($key) {
		return $this->getConnection()->getClient()->get($key);
	}

	/**
	 * Retrieves multiple values from cache with the specified keys.
	 * @param array $keys a list of keys identifying the cached values
	 * @return array a list of cached values indexed by the keys
	 */
	protected function getValues($keys)	{
		return $this->getConnection()->getClient()->mget($keys);
	}

	/**
	 * Stores a value identified by a key in cache.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	protected function setValue($key,$value,$expire = 0) {

		$this->getConnection()->getClient()->set($key,$value);
		if ($expire) {
			$this->getConnection()->getClient()->expire($key,$expire);
		}
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	protected function addValue($key,$value,$expire) {
		if($expire>0)
			$expire+=time();
		else
			$expire=0;

		if (!$this->getConnection()->getClient()->setnx($key,$value)) {
			return false;
		}
		if ($expire) {
			$this->getConnection()->getClient()->expire($key,$expire);
		}
		return true;
	}

	/**
	 * Deletes a value with the specified key from cache
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	protected function deleteValue($key) {
		return $this->getConnection()->getClient()->delete($key);
	}

	/**
	 * Deletes all values from cache.
	 * Be careful of performing this operation if the cache is shared by multiple applications.
	 * @return boolean whether flushing was successful or not
	 */
	public function flush() {
		return (bool) $this->getConnection()->getClient()->flushDb();
	}
}
