<?php
require_once("common.php");
/**
 * Tests for the {@link ARedisRecord} class
 * @author Charles Pick
 * @package packages.redis.tests
 */
class ARedisRecordTest extends CTestCase {
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
		$model = new ExampleRedisRecord();
		$model->name = "Test";
		$this->assertTrue($model->save());
		$this->assertGreaterThan(0,$model->id);
		$model2 = new ExampleRedisRecord();
		$model2->name = "Test 2";
		$this->assertTrue($model2->save());
		$this->assertGreaterThan(0,$model2->id);
		$this->assertTrue(is_object(ExampleRedisRecord::model()->findByPk($model->id)));
		$this->assertTrue(is_object(ExampleRedisRecord::model()->findByPk($model2->id)));
		$this->assertGreaterThan(1,$model->count());
		$this->assertTrue($model->delete());
		$this->assertTrue($model2->delete());
		$startTime = microtime(true);
		$ids = array();
		for($i = 0; $i < 1000; $i++) {

			$model = new ExampleRedisRecord();
			$model->name = "Bulk Test Item ".$i;
			$model->address = "Some test address ".$i;

			$this->assertTrue($model->save());
			$ids[] = $model->id;
		}
		$totalTime = (microtime(true) - $startTime);
		echo "Inserted 1000 redis records in ".$totalTime." seconds\n";

		$startTime = microtime(true);
		$n = 0;
		foreach($model->getRedisSet() as $key) {
			$n++;
		}
		$this->assertGreaterThanOrEqual(999,$n);
		$totalTime = (microtime(true) - $startTime);
		echo "Traversed 1000 redis record keys in ".$totalTime." seconds\n";
		$startTime = microtime(true);
		$models = ExampleRedisRecord::model()->findAllByPk($ids);

		echo "Found 1000 redis records in ".(microtime(true) - $startTime)." seconds\n";
		$this->assertEquals(1000,count($models));
		foreach($models as $model) {
			$this->assertTrue($model->delete());
		}
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

/**
 * An example of a redis record
 * @author Charles Pick
 * @package packages.redis.tests
 */
class ExampleRedisRecord extends ARedisRecord {
	/**
	 * The primary key
	 * @var integer
	 */
	public $id;
	/**
	 * The name attribute
	 * @var string
	 */
	public $name;
	/**
	 * The address attribute, with a default value
	 * @var string
	 */
	public $address = "Not Yet Added";

	/**
	 * Gets the static model
	 * @param string $className the model class to instantiate
	 * @return ExampleRedisRecord the model
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
}