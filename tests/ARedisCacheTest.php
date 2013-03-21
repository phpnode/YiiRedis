<?php
require_once("common.php");
/**
 * Tests for the {@link ARedisCache} class
 * @author Charles Pick
 * @package packages.redis.tests
 */
class ARedisCacheTest extends CTestCase {
	/**
	 * Holds the redis cache component
	 * @var ARedisConnection
	 */
	protected $_connection;
	/**
	 * Tests the basic functionality
	 */
	public function testBasics() {
		$cache = new ARedisCache();
		$cache->setConnection($this->getConnection());
		$cacheKey = "TEST_KEY:".uniqid();
		$this->assertEquals(false,$cache->get($cacheKey));
		$cache->set($cacheKey,"test");
		$this->assertEquals("test",$cache->get($cacheKey));
		$cache->delete($cacheKey);
		$this->assertEquals(false,$cache->get($cacheKey));

		// test expire
		$cache->set($cacheKey,"test",1);
		$this->assertEquals("test",$cache->get($cacheKey));
		sleep(2);
		$this->assertEquals(false,$cache->get($cacheKey));
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