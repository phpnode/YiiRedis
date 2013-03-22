<?php
require_once("common.php");
/**
 * Tests for the {@link ARedisMutex} class
 * @author Charles Pick
 * @package packages.redis.tests
 */
class ARedisMutexTest extends CTestCase {
	/**
	 * Holds the redis connection
	 * @var ARedisConnection
	 */
	protected $_connection;
	/**
	 * Tests the basic functionality
	 */
	public function testBasics() {
		$redis = $this->getConnection();
		$mutex1 = new ARedisMutex("TestMutex2:".uniqid(),$redis);
		$mutex2 = new ARedisMutex($mutex1->name,$redis);
		$mutex1->expiresAfter = 2;
		$mutex2->expiresAfter = 2;
		$this->assertTrue($mutex1->lock());
		$this->assertFalse($mutex2->lock());
		$this->assertFalse($mutex2->unlock());
		$this->assertTrue($mutex1->unlock());
		$this->assertTrue($mutex2->lock());
		sleep(3);
		$this->assertTrue($mutex1->lock());
		$this->assertTrue($mutex1->unlock());
	}


	/**
	 * Tests the blocking functionality
	 */
	public function testBlock() {
		$redis = $this->getConnection();
		$mutex1 = new ARedisMutex("TestMutex2:".uniqid(),$redis);
		$mutex2 = new ARedisMutex($mutex1->name,$redis);
		$mutex1->expiresAfter = 2;
		$mutex2->expiresAfter = 2;
		$this->assertTrue($mutex1->lock());
		$count = 0;
		$mutex2->onAfterLock = function(CEvent $event) use (&$count) {
			$count++;
			$event->sender->unlock();
		};
		$mutex2->block();
		$this->assertEquals(1, $count);
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
