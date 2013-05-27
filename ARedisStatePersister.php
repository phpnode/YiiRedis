<?php
/**
 * ARedisStatePersister implements a redis-based persistent data storage.
 *
 * It can be used to keep data available through multiple requests and sessions.
 *
 * By default, ARedisStatePersister stores data through the 'redis' application component.
 * You may change used connection by setting the {@link connection} property.
 *
 * To retrieve the data from CStatePersister, call {@link load()}. To save the data,
 * call {@link save()}.
 *
 * Comparison among state persister, session and cache is as follows:
 * <ul>
 * <li>session: data persisting within a single user session.</li>
 * <li>state persister: data persisting through all requests/sessions (e.g. hit counter).</li>
 * <li>cache: volatile and fast storage. It may be used as storage medium for session or state persister.</li>
 * </ul>
 *
 * @author Vasily Gudoshnikov <vgoodvin@gmail.com>
 * @package packages.redis
 */
class ARedisStatePersister extends CApplicationComponent implements IStatePersister
{
    /**
     * Holds the redis connection
     * @var ARedisConnection
     */
    protected $_connection;

    /**
     * @var string
     * This value will be used as key in a Redis database and in a caching storage.
     */
    public $key = 'Yii.ARedisStatePersister.key';

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
     * Sets the redis connection to use for this session handler
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
     * Gets the redis connection to use for this session handler
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
     * Loads state data from persistent storage.
     * @return mixed state data. Null if no state data available.
     */
    public function load()
    {
        $content = $this->_connection->client->get($this->key);
        return ($content !== false) ? unserialize($content) : null;
    }

    /**
     * Saves application state in persistent storage.
     * @param mixed $state state data (must be serializable).
     */
    public function save($state)
    {
        $this->_connection->client->set($this->key, serialize($state));
    }
}
