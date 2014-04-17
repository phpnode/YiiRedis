<?php
/**
 * A base class for redis entities.
 * Extends CBehavior to allow entities to be attached to models and components.
 * <pre>
 * $user = User::model()->findByPk(1);
 * $counter = new ARedisCounter("totalLogins");
 * $user->attachBehavior("totalLogins", $counter);
 * echo $user->totalLogins."\n"; // 0
 * $user->totalLogins->increment();
 * echo $user->totalLogins."\n"; // 1
 *
 * $friends = new ARedisSet("friendIds");
 * $user->attachBehavior("friendIds",$friends);
 * foreach($user->friendIds as $id) {
 *  echo "User ".$user->id." is friends with user ".$id."\n";
 * }
 *
 * </pre>
 * @package packages.redis
 * @author Charles Pick
 */
abstract class ARedisEntity extends CBehavior {
	/**
	 * The name of the redis entity (key)
	 * @var string
	 */
	public $name;

	/**
	 * Holds the redis connection
	 * @var ARedisConnection
	 */
	protected $_connection;

	/**
	 * The old name of this entity
	 * @var string
	 */
	protected $_oldName;
	/**
	 * Constructor
	 * @param string $name the name of the entity
	 * @param ARedisConnection|string $connection the redis connection to use with this entity
	 */
	public function __construct($name = null, $connection = null) {
		if ($name !== null) {
			$this->name = $name;
		}
		if ($connection !== null) {
			$this->setConnection($connection);
		}
	}
	/**
	 * Attaches the entity to a component
	 * @throws CException if no name is set
	 * @param CComponent $owner the owner component
	 */
	public function attach($owner) {
		parent::attach($owner);
		if ($this->name === null) {
			throw new CException("No name specified for ".get_class($this));
		}
		if (method_exists($owner, "getPrimaryKey")) {
			$this->_oldName = $this->name;

			$pk = $owner->getPrimaryKey();
			if (is_array($pk)) {
				foreach($pk as $key => $value) {
					$pk[$key] = $key.":".$value;
				}
				$pk = implode(":",$pk);
			}
			$this->name = get_class($owner).":".$pk.":".$this->name;
		}
	}
	/**
	 * Detaches the entity from a component
	 * @param CComponent $owner the owner component
	 */
	public function detach($owner) {
		parent::detach($owner);
		if (method_exists($owner, "getPrimaryKey")) {
			$this->name = $this->_oldName;
		}
	}

	/**
	 * Sets the redis connection to use for this entity
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
	 * Gets the redis connection to use for this entity
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
	 * Sets the expiration time in seconds to this entity 
	 *  @param integer number of expiration for this entity in seconds
	 */
	public function expire($seconds)
	{
		return $this->getConnection()->expire($this->name, $seconds);
	}

}