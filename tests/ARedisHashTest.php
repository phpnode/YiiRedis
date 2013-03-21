<?php
require_once("common.php");
/**
 * Tests for the {@link ARedisHash} class
 * @author Charles Pick
 * @package packages.redis.tests
 */
class ARedisHashTest extends CTestCase {
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
		$set = new ARedisHash("TestHash:".uniqid(),$redis);
		$this->assertTrue($set->add("oranges", 2.40));
		$this->assertTrue($set->add("apples", 1.40));
		$this->assertTrue($set->add("strawberries", 3));
		$this->assertEquals(3, $set->getCount());
		$this->assertTrue($set->add("carrots",0.4));
		$this->assertEquals(4, $set->getCount());
		$this->assertTrue($set->remove("carrots"));
		$this->assertFalse($set->remove("carrots"));
		$this->assertEquals(3, $set->getCount());
		$set->clear();
		$this->assertEquals(0, $set->getCount());
	}

	public function testBehaviors() {
		$component = new CComponent;
		$hash = new ARedisHash("testAttribute".uniqid(),$this->getConnection());
		$component->attachBehavior("testAttribute",$hash);
		$this->assertTrue(isset($component->testAttribute));
		$this->assertTrue($component->testAttribute->add("test",true));
		$this->assertTrue((bool) $component->testAttribute['test']);
		$component->testAttribute->clear();
	}
	public function testInterfaces() {
		$redis = $this->getConnection();
		$set = new ARedisHash("TestHash:".uniqid(),$redis);

		$this->assertEquals(0,count($set));
		$set["test"] = 24;
		$set["test2"] = 12;
		$this->assertEquals(2,count($set));
		foreach($set as $item => $value) {
			$this->assertTrue($item == "test" || $item == "test2");
		}
		unset($set["test"]);
		$this->assertEquals(1, count($set));
		$set->clear();
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