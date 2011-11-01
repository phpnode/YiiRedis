<?php
require_once("common.php");
/**
 * Tests for the {@link ARedisList} class
 * @author Charles Pick
 * @package packages.redis.tests
 */
class ARedisListTest extends CTestCase {
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
		$list = new ARedisList("TestSet:".uniqid(),$redis);

		$list[] = "Hello World";
		$this->assertEquals("Hello World",$list->pop());
		$this->assertEquals(0,$list->getCount());
		$this->assertFalse($redis->exists($list->name));

		$testData = array();
		for($i = 0; $i < 100; $i++) {
			$testData[] = "Test Item ".$i;
		}
		$list = new ARedisList("Test_List".uniqid(), $redis);
		$list->copyFrom($testData);
		$this->assertEquals(100,count($list));
		foreach($list as $i => $item) {
			$this->assertEquals($testData[$i],$item);
		}
		$list->clear();
		$this->assertFalse($redis->exists($list->name));
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
										"database" => REDIS_DATABASE
									));
		}
		return $this->_connection;
	}
}