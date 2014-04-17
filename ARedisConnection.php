<?php
/**
 * Represents a redis connection.
 *
 * @author Charles Pick
 * @package packages.redis
 */
class ARedisConnection extends CApplicationComponent {
	/**
	 * The redis client
	 * @var Redis
	 */
	protected $_client;

	/**
	 * The redis server name
	 * @var string
	 */
	public $hostname = "localhost";

	/**
	 * Redis default prefix
	 * @var string
	 */
	public $prefix = "Yii.redis.";

	/**
	 * The redis server port
	 * @var integer
	 */
	public $port=6379;

	/**
	 * The database to use, defaults to 1
	 * @var integer
	 */
	public $database=1;

	/**
	 * The redis server password
	 * @var password
	 */
	public $password=null;

	/**
	 * The redis socket timeout, defaults to 0 (unlimited)
	 * @var timeout
	 */
	public $timeout=0;

	/**
	 * Sets the redis client to use with this connection
	 * @param Redis $client the redis client instance
	 */
	public function setClient(Redis $client)
	{
		$this->_client = $client;
	}

	/**
	 * Gets the redis client
	 * @return Redis the redis client
	 */
	public function getClient($reconnect = false)
	{
		if ($this->_client === null || $reconnect) {
			$this->_client = new Redis;
			$this->_client->connect($this->hostname, $this->port, $this->timeout);
			if (isset($this->password)) {
				if ($this->_client->auth($this->password) === false) {
					throw new CException('Redis authentication failed!');
				}
			}
			$this->_client->setOption(Redis::OPT_PREFIX, $this->prefix);
			$this->_client->select($this->database);
		}
		return $this->_client;
	}

	/**
	 * Returns a property value based on its name.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using the following syntax to read a property
	 * <pre>
	 * $value=$component->propertyName;
	 * </pre>
	 * @param string $name the property name
	 * @return mixed the property value
	 * @throws CException if the property is not defined
	 * @see __set
	 */
	public function __get($name) {
		$getter='get'.$name;
		if (property_exists($this->getClient(),$name)) {
			return $this->getClient()->{$name};
		}
		elseif(method_exists($this->getClient(),$getter)) {
			return $this->$getter();
		}
		return parent::__get($name);
	}

	/**
	 * Sets value of a component property.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using the following syntax to set a property
	 * <pre>
	 * $this->propertyName=$value;
	 * </pre>
	 * @param string $name the property name
	 * @param mixed $value the property value
	 * @return mixed
	 * @throws CException if the property is not defined or the property is read only.
	 * @see __get
	 */
	public function __set($name,$value)
	{
		$setter='set'.$name;
		if (property_exists($this->getClient(),$name)) {
			return $this->getClient()->{$name} = $value;
		}
		elseif(method_exists($this->getClient(),$setter)) {
			return $this->getClient()->{$setter}($value);
		}
		return parent::__set($name,$value);
	}

	/**
	 * Checks if a property value is null.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using isset() to detect if a component property is set or not.
	 * @param string $name the property name
	 * @return boolean
	 */
	public function __isset($name)
	{
		$getter='get'.$name;
		if (property_exists($this->getClient(),$name)) {
			return true;
		}
		elseif (method_exists($this->getClient(),$getter)) {
			return true;
		}
		return parent::__isset($name);
	}

	/**
	 * Sets a component property to be null.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using unset() to set a component property to be null.
	 * @param string $name the property name or the event name
	 * @throws CException if the property is read only.
	 * @return mixed
	 */
	public function __unset($name)
	{
		$setter='set'.$name;
		if (property_exists($this->getClient(),$name)) {
			$this->getClient()->{$name} = null;
		}
		elseif(method_exists($this,$setter)) {
			$this->$setter(null);
		}
		else {
			parent::__unset($name);
		}
	}
	/**
	 * Calls a method on the redis client with the given name.
	 * Do not call this method. This is a PHP magic method that we override to
	 * allow a facade in front of the redis object.
	 * @param string $name the name of the method to call
	 * @param array $parameters the parameters to pass to the method
	 * @return mixed the response from the redis client
	 */
	public function __call($name, $parameters) {
		return call_user_func_array(array($this->getClient(),$name),$parameters);
	}
}
