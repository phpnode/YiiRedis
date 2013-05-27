<?php
require_once("common.php");
/**
 * Tests for the {@link ARedisStatePersisterTest} class
 * @author Vasily Gudoshnikov <vgoodvin@gmail.com>
 * @package packages.redis.tests
 */
class ARedisStatePersisterTest extends CTestCase {
    /**
     * Holds the redis connection component
     * @var ARedisConnection
     */
    protected $_connection;
    /**
     * Tests the basic functionality
     */
    public function testBasics() {
        $persister = new ARedisStatePersister();
        $persister->setConnection($this->getConnection());
        $persister->key = 'Yii.ARedisStatePersister.test';
        $this->getConnection()->getClient()->del($persister->key);
        $this->assertEquals($persister->load(), null);
        $persister->save(array('testKey' => 'testValue'));
        $this->assertEquals($persister->load(), array('testKey' => 'testValue'));
        $this->getConnection()->getClient()->del($persister->key);
    }

    /**
     * Sets the redis connection to use with this test
     * @param ARedisConnection $connection the connection
     */
    public function setConnection($connection)
    {
        $this->_connection = $connection;
    }

    /**
     * Gets the redis connection to use with this test
     * @return ARedisConnection the redis connection
     */
    public function getConnection()
    {
        if ($this->_connection === null) {
            $this->_connection = Yii::createComponent(
                array(
                    "class" => "packages.redis.ARedisConnection",
                    "hostname" => REDIS_HOSTNAME,
                    "port" => REDIS_PORT,
                    "database" => REDIS_DATABASE,
                    "password" => REDIS_PASSWORD
                ));
        }
        return $this->_connection;
    }
}