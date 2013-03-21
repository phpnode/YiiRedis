<?php
require_once("common.php");
/**
 * Tests for the {@link ARedisChannel} class
 * @author Charles Pick
 * @package packages.redis.tests
 */
class ARedisChannelTest extends CTestCase {
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
		$channel1 = new ARedisChannel("TestSet:".uniqid(),$redis);
		$this->assertEquals(0, $channel1->publish("a test message"));
		$this->assertEquals("a test message",$channel1[0]);
		// todo: implement threading so that we can properly test subscribe / unsubscribe
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