<?php
require_once("common.php");
/**
 * Tests for the {@link ARedisSet} class
 * @author Charles Pick
 * @package packages.redis.tests
 */
class ARedisSetTest extends CTestCase {
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
		$set = new ARedisSet("TestSet:".uniqid(),$redis);
		$this->assertTrue($set->add("fish"));
		$this->assertTrue($set->add("chips"));
		$this->assertEquals(2, $set->getCount());
		$this->assertTrue($set->contains("fish"));
		$this->assertTrue($set->contains("chips"));
		$this->assertTrue($set->remove("fish"));
		$this->assertTrue($set->remove("chips"));
		$this->assertFalse($set->contains("fish"));
		$this->assertFalse($set->contains("chips"));
		$this->assertTrue($set->add("fish"));
		$this->assertTrue($set->add("chips"));
		$this->assertTrue(in_array($set->random(),array("fish", "chips")));
		$this->assertTrue(in_array($set->pop(),array("fish", "chips")));
		$this->assertTrue(in_array($set->pop(),array("fish", "chips")));
		$this->assertFalse($set->contains("fish"));
		$this->assertFalse($set->contains("chips"));
	}


	public function testDiff() {
		$redis = $this->getConnection();
		$set1 = new ARedisSet("TestSet1:".uniqid(),$redis);
		$set2 = new ARedisSet("TestSet2:".uniqid(),$redis);

		$this->assertTrue($set1->add("1"));
		$this->assertTrue($set2->add("1"));
		$this->assertTrue($set1->add("5"));
		$this->assertTrue($set2->add("10"));
		$this->assertTrue($set1->add("20"));
		$this->assertTrue($set2->add("20"));
		$this->assertEquals(array(5),$set1->diff($set2->name));
		$this->assertEquals(array(10),$set2->diff($set1->name));
		$newSet = $set1->diffStore("TestSet3:".uniqid(),$set2);
		$this->assertEquals(array(5),$newSet->getData());
		$newSet->clear();
		$set1->clear();
		$set2->clear();
	}

	public function testInter() {
		$redis = $this->getConnection();
		$set1 = new ARedisSet("TestSet1:".uniqid(),$redis);
		$set2 = new ARedisSet("TestSet2:".uniqid(),$redis);

		$this->assertTrue($set1->add("1"));
		$this->assertTrue($set2->add("1"));
		$this->assertTrue($set1->add("5"));
		$this->assertTrue($set2->add("10"));
		$this->assertTrue($set1->add("20"));
		$this->assertTrue($set2->add("20"));
		$this->assertEquals(array(1, 20),$set1->inter($set2->name));
		$this->assertEquals(array(1, 20),$set2->inter($set1->name));
		$newSet = $set1->interStore("TestSet3:".uniqid(),$set2);
		$this->assertEquals(array(1, 20),$newSet->getData());
		$newSet->clear();
		$set1->clear();
		$set2->clear();
	}

	public function testUnion() {
		$redis = $this->getConnection();
		$set1 = new ARedisSet("TestSet1:".uniqid(),$redis);
		$set2 = new ARedisSet("TestSet2:".uniqid(),$redis);

		$this->assertTrue($set1->add("1"));
		$this->assertTrue($set2->add("1"));
		$this->assertTrue($set1->add("5"));
		$this->assertTrue($set2->add("10"));
		$this->assertTrue($set1->add("20"));
		$this->assertTrue($set2->add("20"));
		$sorted = $set1->union($set2);
		sort($sorted);
		$this->assertEquals(array(1, 5, 10, 20),$sorted);
		$newSet = $set1->unionStore("TestSet3:".uniqid(),$set2);
		$sorted = $newSet->getData();
		sort($sorted);
		$this->assertEquals(array(1, 5, 10, 20),$sorted);
		$newSet->clear();
		$set1->clear();
		$set2->clear();
	}

	public function testMove() {
		$redis = $this->getConnection();
		$set1 = new ARedisSet("TestSet1:".uniqid(),$redis);
		$set2 = new ARedisSet("TestSet2:".uniqid(),$redis);

		$this->assertTrue($set1->add("1"));
		$this->assertTrue($set2->add("1"));
		$this->assertTrue($set1->add("5"));
		$this->assertTrue($set1->add("10"));

		$this->assertTrue($set1->move($set2,"1"));
		$this->assertFalse($set1->move($set2,"1"));
		$this->assertTrue($set1->move($set2,"5"));
		$this->assertTrue($set1->move($set2,"10"));
		$this->assertEquals(0, $set1->getCount());
		$this->assertEquals(3, $set2->getCount());
		$set1->clear();
		$set2->clear();
	}


	public function testInterfaces() {
		$redis = $this->getConnection();
		$set = new ARedisSet("TestSet:".uniqid(),$redis);

		$this->assertEquals(0,count($set));
		$set[] = "test";
		$set[] = "test2";
		$this->assertEquals(2,count($set));
		foreach($set as $item) {
			$this->assertTrue($item == "test" || $item == "test2");
		}
		unset($set[0]);
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