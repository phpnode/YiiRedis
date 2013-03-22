<?php
require_once("common.php");
/**
 * Tests for the {@link ARedisConnection} class
 * @author Charles Pick
 * @package packages.redis.tests
 */
class ARedisConnectionTest extends CTestCase {
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
		$keyId = "TEST_KEY:".uniqid();
		$this->assertEquals(null,$redis->get($keyId));
		$redis->set($keyId,"TEST");
		$this->assertEquals("TEST",$redis->get($keyId));
		$redis->delete($keyId);
		$this->assertEquals(null,$redis->get($keyId));
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