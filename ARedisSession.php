<?php
/**
 * A simple redis based session handler
 * @author Charles Pick
 * @package packages.redis
 */
class ARedisSession extends CHttpSession
{
	/**
	 * The prefix to use when storing and retrieving sessions
	 * @var string
	 */
	public $keyPrefix = "Yii.ARedisSession.";

	/**
	 * The suffix to use when storing and retrieving sessions
	 * @var string
	 */
	public $keySuffix = '';
	/**
	 * Holds the redis connection
	 * @var ARedisConnection
	 */
	protected $_connection;

	/**
	 * Initializes the application component.
	 * This method overrides the parent implementation by checking if redis is available.
	 */
	public function init()
	{
		$this->getConnection();
		parent::init();
	}

	/**
	 * Returns a value indicating whether to use custom session storage.
	 * @return boolean whether to use custom storage.
	 */
	public function getUseCustomStorage()
	{
		return true;
	}

	/**
	 * Session read handler.
	 * Do not call this method directly.
	 * @param string $id session ID
	 * @return string the session data
	 */
	public function readSession($id)
	{
		$data=$this->_connection->getClient()->get($this->calculateKey($id));
		return $data===false?'':$data;
	}

	/**
	 * Session write handler.
	 * Do not call this method directly.
	 * @param string $id session ID
	 * @param string $data session data
	 * @return boolean whether session write is successful
	 */
	public function writeSession($id, $data)
	{
		$key = $this->calculateKey($id);
		$this->_connection->getClient()->set($key,$data);
		$this->_connection->getClient()->expire($key,$this->getTimeout());
		return true;
	}

	/**
	 * Session destroy handler.
	 * Do not call this method directly.
	 * @param string $id session ID
	 * @return boolean whether session is destroyed successfully
	 */
	public function destroySession($id)
	{
		$this->_connection->getClient()->delete($this->calculateKey($id));
		return true;
	}

	/**
	 * Sets the redis connection to use for this session handler
	 * @param ARedisConnection|string $connection the redis connection, if a string is provided, it is presumed to be a the name of an applciation component
	 */
	public function setConnection($connection)
	{
		if (is_string($connection))
			$connection = Yii::app()->{$connection};
		$this->_connection = $connection;
	}

	/**
	 * Gets the redis connection to use for this session handler
	 * @return ARedisConnection
	 */
	public function getConnection()
	{
		if ($this->_connection === null) {
			if (!isset(Yii::app()->redis))
				throw new CException(get_class($this)." expects a 'redis' application component");
			$this->_connection = Yii::app()->redis;
		}
		return $this->_connection;
	}
	/**
	 * Generates a unique key used for storing session data in cache.
	 * @param string $id session variable name
	 * @return string a safe cache key associated with the session variable name
	 */
	protected function calculateKey($id)
	{
		return $this->keyPrefix.$id.$this->keySuffix;
	}
}
