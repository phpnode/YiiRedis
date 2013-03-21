<?php
require_once("common.php");
/**
 * Tests for the {@link ARedisSortedSet} class
 * @author Charles Pick
 * @package packages.redis.tests
 */
class ARedisSortedSetTest extends CTestCase {
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
		$set = new ARedisSortedSet("TestSet:".uniqid(),$redis);
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


	public function testInterfaces() {
		$redis = $this->getConnection();
		$set = new ARedisSortedSet("TestSet:".uniqid(),$redis);

		$this->assertEquals(0,count($set));
		$set["test"] = 24;
		$set["test2"] = 12;
		$this->assertEquals(2,count($set));
		$previous = 0;
		foreach($set as $item => $value) {
			$this->assertTrue($item == "test" || $item == "test2");
			$this->assertGreaterThan($previous, $value);
			$previous = $value;
		}
		unset($set["test"]);
		$this->assertEquals(1, count($set));
		$set->clear();
	}

	public function testInterStore() {
		$redis = $this->getConnection();
		$set1 = new ARedisSortedSet("TestSet1:".uniqid(),$redis);
		$set2 = new ARedisSortedSet("TestSet2:".uniqid(),$redis);
		$this->assertTrue($set1->add("test 1", 1));
		$this->assertTrue($set2->add("test 1", 1));
		$this->assertTrue($set1->add("test 2", 2));
		$this->assertTrue($set2->add("test 3", 3));
		$this->assertTrue($set1->add("test 4", 4));
		$this->assertTrue($set2->add("test 4", 5));
		$newSet = $set1->interStore("TestSet3:".uniqid(),$set2);
		$this->assertEquals(array("test 1" => 2, "test 4" => 9),$newSet->getData());

		$newSet->clear();
		$set1->clear();
		$set2->clear();
	}

	public function testUnionStore() {
		$redis = $this->getConnection();
		$set1 = new ARedisSortedSet("TestSet1:".uniqid(),$redis);
		$set2 = new ARedisSortedSet("TestSet2:".uniqid(),$redis);
		$this->assertTrue($set1->add("test 1", 1));
		$this->assertTrue($set2->add("test 1", 1));
		$this->assertTrue($set1->add("test 2", 2));
		$this->assertTrue($set2->add("test 3", 3));
		$this->assertTrue($set1->add("test 4", 4));
		$this->assertTrue($set2->add("test 4", 5));
		$newSet = $set1->unionStore("TestSet3:".uniqid(),$set2);
		$this->assertEquals(array(
								 "test 1" => 2,
								 "test 2" => 2,
								 "test 3" => 3,
								 "test 4" => 9),$newSet->getData());

		$newSet->clear();
		$set1->clear();
		$set2->clear();
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