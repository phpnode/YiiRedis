<?php
require_once("common.php");
/**
 * Tests for the {@link ARedisCounter} class
 * @author Charles Pick
 * @package packages.redis.tests
 */
class ARedisCounterTest extends CTestCase {
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
		$counter = new ARedisCounter("TestCounter:".uniqid(),$redis);
		$this->assertEquals(0,$counter->getValue());
		$this->assertEquals(1,$counter->increment());
		$this->assertEquals(11,$counter->increment(10));
		$this->assertEquals(11,$counter->getValue());
		$this->assertEquals(10,$counter->decrement());
		$this->assertEquals(5,$counter->decrement(5));
		$this->assertEquals(0,$counter->decrement(5));

		$counter->clear();
		$this->assertFalse($redis->getClient()->exists($counter->name));
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
