<?php
/**
 * Allows access to redis records using the active record pattern.
 * <pre>
 * $record = ARedisRecord::model()->findByPk(1); // loads a record with a unique id of 1
 * $record->name = "a test name"; // sets the name attribute on the record
 * $record->save(); // saves the record to redis
 * $record->delete(); // deletes the record from redis
 * </pre>
 * @author Charles Pick / PeoplePerHour.com
 * @package packages.redis
 */
abstract class ARedisRecord extends CFormModel {

	/**
	 * The redis connection
	 * @var ARedisConnection
	 */
	public static $redis;
	/**
	 * The record attributes.
	 * @var CAttributeCollection
	 */
	protected $_attributes;

	/**
	 * The connection to redis
	 * @var ARedisConnection
	 */
	protected $_connection;

	/**
	 * The redis set that represents the list of models of this type
	 * @var ARedisIterableSet
	 */
	protected $_redisSet;

	/**
	 * The redis hash that contains the values for this record
	 * @var ARedisIterableHash
	 */
	protected $_redisHash;

	/**
	 * The old primary key value
	 * @var mixed
	 */
	private $_pk;

	/**
	 * Whether this is a new record or not
	 * @var boolean
	 */
	private $_new = true;

	/**
	 * An array of static model instances, clas name => model
	 * @var array
	 */
	private static $_models=array();

	/**
	 * Constructor.
	 * @param string $scenario the scenario name
	 * See {@link CModel::scenario} on how scenario is used by models.
	 * @see getScenario
	 */
	public function __construct($scenario = "insert")
	{
		if ($scenario === null) {
			return;
		}
		$this->init();
		$this->attachBehaviors($this->behaviors());
		$this->afterConstruct();
	}

	/**
	 * Returns the static model of the specified redis record class.
	 * The model returned is a static instance of the redis record class.
	 * It is provided for invoking class-level methods (something similar to static class methods.)
	 *
	 * EVERY derived redis record  class must override this method as follows,
	 * <pre>
	 * public static function model($className=__CLASS__)
	 * {
	 *	 return parent::model($className);
	 * }
	 * </pre>
	 *
	 * @param string $className redis record class name.
	 * @return ARedisRecord redis record model instance.
	 */
	public static function model($className=__CLASS__)
	{
		if(isset(self::$_models[$className]))
			return self::$_models[$className];
		else
		{
			$model=self::$_models[$className]=new $className(null);
			$model->attachBehaviors($model->behaviors());
			return $model;
		}
	}

	/**
	 * Returns the redis connection used by redis record.
	 * By default, the "redis" application component is used as the redis connection.
	 * You may override this method if you want to use a different redis connection.
	 * @return ARedisConnection the redis connection used by redis record.
	 */
	public function getRedisConnection()
	{
		if ($this->_connection !== null) {
			return $this->_connection;
		}
		elseif(self::$redis!==null) {
			return self::$redis;
		}
		else
		{
			self::$redis=Yii::app()->redis;
			if(self::$redis instanceof ARedisConnection)
				return self::$redis;
			else
				throw new CException(Yii::t('yii','Redis Record requires a "redis" ARedisConnection application component.'));
		}
	}
	/**
	 * Sets the redis connection used by this redis record
	 * @param ARedisConnection $connection the redis connection to use for this record
	 */
	public function setRedisConnection(ARedisConnection $connection) {
		$this->_connection = $connection;
	}

	/**
	 * Creates a redis record instance.
	 * This method is called by {@link populateRecord} and {@link populateRecords}.
	 * You may override this method if the instance being created
	 * depends the attributes that are to be populated to the record.
	 * For example, by creating a record based on the value of a column,
	 * you may implement the so-called single-table inheritance mapping.
	 * @param array $attributes list of attribute values for the redis record.
	 * @return ARedisRecord the active record
	 */
	protected function instantiate($attributes)
	{
		$class=get_class($this);
		$model=new $class(null);
		return $model;
	}

	/**
	 * Creates a redis record with the given attributes.
	 * This method is internally used by the find methods.
	 * @param array $attributes attribute values (column name=>column value)
	 * @param boolean $callAfterFind whether to call {@link afterFind} after the record is populated.
	 * @return ARedisRecord the newly created redis record. The class of the object is the same as the model class.
	 * Null is returned if the input data is false.
	 */
	public function populateRecord($attributes,$callAfterFind=true)
	{
		if($attributes!==false)
		{
			$record=$this->instantiate($attributes);
			$record->setScenario('update');
			$record->init();
			foreach($attributes as $name=>$value) {
				if (property_exists($record,$name)) {
					$record->$name=$value;
				}
			}
			$record->_pk=$record->getPrimaryKey();
			$record->attachBehaviors($record->behaviors());
			if($callAfterFind) {
				$record->afterFind();
			}
			return $record;
		}
		else {
			return null;
		}
	}

	/**
	 * Creates a list of redis records based on the input data.
	 * This method is internally used by the find methods.
	 * @param array $data list of attribute values for the redis records.
	 * @param boolean $callAfterFind whether to call {@link afterFind} after each record is populated.
	 * @param string $index the name of the attribute whose value will be used as indexes of the query result array.
	 * If null, it means the array will be indexed by zero-based integers.
	 * @return array list of redis records.
	 */
	public function populateRecords($data,$callAfterFind=true,$index=null)
	{
		$records=array();
		foreach($data as $attributes)
		{
			if(($record=$this->populateRecord($attributes,$callAfterFind))!==null)
			{
				if($index===null)
					$records[]=$record;
				else
					$records[$record->$index]=$record;
			}
		}
		return $records;
	}

	/**
	 * Returns the name of the primary key of the associated redis index.
	 * Child classes should override this if the primary key is anything other than "id"
	 * @return mixed the primary key attribute name(s). Defaults to "id"
	 */
	public function primaryKey()
	{
		return "id";
	}
	/**
	 * Gets the redis key used when storing the attributes for this model
	 * @param mixed $pk the primary key to create the redis key for
	 * @return string the redis key
	 */
	public function getRedisKey($pk = null) {
		if ($pk === null) {
			$pk = $this->getPrimaryKey();
		}
		if (is_array($pk)) {
			foreach($pk as $key => $value) {
				$pk[$key] = $key.":".$value;
			}
			$pk = implode(":",$pk);
		}
		return get_class($this).":".$pk;
	}


	/**
	 * Returns the primary key value.
	 * @return mixed the primary key value. An array (column name=>column value) is returned if the primary key is composite.
	 * If primary key is not defined, null will be returned.
	 */
	public function getPrimaryKey()
	{
		$attribute = $this->primaryKey();
		if (!is_array($attribute)) {
			return $this->{$attribute};
		}
		$pk = array();
		foreach($attribute as $field) {
			$pk[$field] = $this->{$attribute};
		}
		return $pk;
	}

	/**
	 * Sets the primary key value.
	 * After calling this method, the old primary key value can be obtained from {@link oldPrimaryKey}.
	 * @param mixed $value the new primary key value. If the primary key is composite, the new value
	 * should be provided as an array (column name=>column value).
	 */
	public function setPrimaryKey($value)
	{
		$this->_pk=$this->getPrimaryKey();
		$attribute = $this->primaryKey();
		if (!is_array($attribute)) {
			return $this->{$attribute} = $value;
		}
		foreach($value as $attribute => $attributeValue) {
			$this->{$attribute} = $attributeValue;
		}
		return $value;
	}
	/**
	 * Returns the old primary key value.
	 * This refers to the primary key value that is populated into the record
	 * after executing a find method (e.g. find(), findAll()).
	 * The value remains unchanged even if the primary key attribute is manually assigned with a different value.
	 * @return mixed the old primary key value. An array (column name=>column value) is returned if the primary key is composite.
	 * If primary key is not defined, null will be returned.
	 * @since 1.1.0
	 */
	public function getOldPrimaryKey()
	{
		return $this->_pk;
	}

	/**
	 * Sets the old primary key value.
	 * @param mixed $value the old primary key value.
	 * @since 1.1.3
	 */
	public function setOldPrimaryKey($value)
	{
		$this->_pk=$value;
	}


	/**
	 * Saves the redis record
	 * @param boolean $runValidation whether to run validation or not, defaults to true
	 * @return boolean whether the save succeeded or not
	 */
	public function save($runValidation = true) {
		if ($runValidation && !$this->validate()) {
			return false;
		}
		if (!$this->beforeSave()) {
			return false;
		}
		if ($this->getPrimaryKey() === null) {
			$count = $this->getRedisSet()->getCount();
			$this->setPrimaryKey($count);
			while (!$this->getRedisSet()->add($this->getRedisKey())) {
				$count++;
				$this->setPrimaryKey($count); // try again, this is suboptimal, need a better way to avoid collisions
			}
		}
		elseif($this->getIsNewRecord() && !$this->getRedisSet()->add($this->getRedisKey())) {
			$this->addError($this->primaryKey(),"A record with this id already exists");
			return false;
		}
		$this->getRedisConnection()->getClient()->multi(); // enter transactional mode

		$this->getRedisHash()->clear();
		foreach($this->attributeNames() as $attribute) {
			$this->getRedisHash()->add($attribute, $this->{$attribute});
		}
		$this->getRedisConnection()->getClient()->exec();
		$this->afterSave();
		return true;
	}
	/**
	 * Deletes the redis record
	 * @return boolean whether the delete succeeded or not
	 */
	public function delete() {
		if (!$this->beforeDelete()){
			return false;
		}
		$this->getRedisSet()->remove($this->getRedisKey());
		$this->getRedisHash()->clear();
		$this->afterDelete();
		return true;
	}

	/**
	 * Returns the number of records of this type
	 * @return integer the number of rows found
	 */
	public function count()
	{
		Yii::trace(get_class($this).'.count()','packages.redis.ARedisRecord');
		return $this->getRedisSet()->getCount();
	}


	/**
	 * Finds a single redis record with the specified primary key.
	 * @param mixed $pk primary key value(s). Use array for multiple primary keys. For composite key, each key value must be an array (column name=>column value).
	 * @return ARedisRecord the record found. Null if none is found.
	 */
	public function findByPk($pk)
	{
		Yii::trace(get_class($this).'.findByPk()','packages.redis.ARedisRecord');
		$this->beforeFind();
		$hash = new ARedisHash($this->getRedisKey($pk),$this->getRedisConnection());
		if ($hash->getCount() == 0) {
			return null;
		}
		return $this->populateRecord($hash->toArray(),true);

	}

	/**
	 * Finds multiple redis records with the specified primary keys.
	 * @param array $pks primary key values.
	 * @return ARedisRecord[] the records found.
	 */
	public function findAllByPk($pks)
	{
		Yii::trace(get_class($this).'.findAllByPk()','packages.redis.ARedisRecord');
		$hashes = array();
		$redis = $this->getRedisConnection()->getClient()->multi();
		foreach($pks as $pk) {
			$key = $this->getRedisKey($pk);
			$redis->hGetAll($key);
		}
		$response = $redis->exec();
		$rows = array();
		foreach($response as $row) {
			if (!$row || !count($row)) {
				continue;
			}
			$rows[] = $row;
		}
		return $this->populateRecords($rows,true);

	}

	/**
	 * Returns if the current record is new.
	 * @return boolean whether the record is new and should be inserted when calling {@link save}.
	 * This property is automatically set in constructor and {@link populateRecord}.
	 * Defaults to false, but it will be set to true if the instance is created using
	 * the new operator.
	 */
	public function getIsNewRecord()
	{
		return $this->_new;
	}

	/**
	 * Sets if the record is new.
	 * @param boolean $value whether the record is new and should be inserted when calling {@link save}.
	 * @see getIsNewRecord
	 */
	public function setIsNewRecord($value)
	{
		$this->_new=$value;
	}

	/**
	 * This event is raised before the record is saved.
	 * By setting {@link CModelEvent::isValid} to be false, the normal {@link save()} process will be stopped.
	 * @param CModelEvent $event the event parameter
	 */
	public function onBeforeSave($event)
	{
		$this->raiseEvent('onBeforeSave',$event);
	}

	/**
	 * This event is raised after the record is saved.
	 * @param CEvent $event the event parameter
	 */
	public function onAfterSave($event)
	{
		$this->raiseEvent('onAfterSave',$event);
	}

	/**
	 * This event is raised before the record is deleted.
	 * By setting {@link CModelEvent::isValid} to be false, the normal {@link delete()} process will be stopped.
	 * @param CModelEvent $event the event parameter
	 */
	public function onBeforeDelete($event)
	{
		$this->raiseEvent('onBeforeDelete',$event);
	}

	/**
	 * This event is raised after the record is deleted.
	 * @param CEvent $event the event parameter
	 */
	public function onAfterDelete($event)
	{
		$this->raiseEvent('onAfterDelete',$event);
	}

	/**
	 * This event is raised before a find call.
	 * In this event, the {@link CModelEvent::criteria} property contains the query criteria
	 * passed as parameters to those find methods. If you want to access
	 * the query criteria specified in scopes, please use {@link getDbCriteria()}.
	 * You can modify either criteria to customize them based on needs.
	 * @param CModelEvent $event the event parameter
	 * @see beforeFind
	 */
	public function onBeforeFind($event)
	{
		$this->raiseEvent('onBeforeFind',$event);
	}

	/**
	 * This event is raised after the record is instantiated by a find method.
	 * @param CEvent $event the event parameter
	 */
	public function onAfterFind($event)
	{
		$this->raiseEvent('onAfterFind',$event);
	}

	/**
	 * This method is invoked before saving a record (after validation, if any).
	 * The default implementation raises the {@link onBeforeSave} event.
	 * You may override this method to do any preparation work for record saving.
	 * Use {@link isNewRecord} to determine whether the saving is
	 * for inserting or updating record.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 * @return boolean whether the saving should be executed. Defaults to true.
	 */
	protected function beforeSave()
	{
		if($this->hasEventHandler('onBeforeSave'))
		{
			$event=new CModelEvent($this);
			$this->onBeforeSave($event);
			return $event->isValid;
		}
		else
			return true;
	}

	/**
	 * This method is invoked after saving a record successfully.
	 * The default implementation raises the {@link onAfterSave} event.
	 * You may override this method to do postprocessing after record saving.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function afterSave()
	{
		if($this->hasEventHandler('onAfterSave'))
			$this->onAfterSave(new CEvent($this));
	}

	/**
	 * This method is invoked before deleting a record.
	 * The default implementation raises the {@link onBeforeDelete} event.
	 * You may override this method to do any preparation work for record deletion.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 * @return boolean whether the record should be deleted. Defaults to true.
	 */
	protected function beforeDelete()
	{
		if($this->hasEventHandler('onBeforeDelete'))
		{
			$event=new CModelEvent($this);
			$this->onBeforeDelete($event);
			return $event->isValid;
		}
		else
			return true;
	}

	/**
	 * This method is invoked after deleting a record.
	 * The default implementation raises the {@link onAfterDelete} event.
	 * You may override this method to do postprocessing after the record is deleted.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function afterDelete()
	{
		if($this->hasEventHandler('onAfterDelete'))
			$this->onAfterDelete(new CEvent($this));
	}

	/**
	 * This method is invoked before a find call.
	 * The find calls include {@link find}, {@link findAll}, {@link findByPk},
	 * {@link findAllByPk}, {@link findByAttributes} and {@link findAllByAttributes}.
	 * The default implementation raises the {@link onBeforeFind} event.
	 * If you override this method, make sure you call the parent implementation
	 * so that the event is raised properly.
	 */
	protected function beforeFind()
	{
		if($this->hasEventHandler('onBeforeFind'))
		{
			$event=new CModelEvent($this);
			// for backward compatibility
			$event->criteria=func_num_args()>0 ? func_get_arg(0) : null;
			$this->onBeforeFind($event);
		}
	}

	/**
	 * This method is invoked after each record is instantiated by a find method.
	 * The default implementation raises the {@link onAfterFind} event.
	 * You may override this method to do postprocessing after each newly found record is instantiated.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function afterFind()
	{
		if($this->hasEventHandler('onAfterFind'))
			$this->onAfterFind(new CEvent($this));
	}

	/**
	 * Calls {@link beforeFind}.
	 * This method is internally used.
	 * @since 1.0.11
	 */
	public function beforeFindInternal()
	{
		$this->beforeFind();
	}

	/**
	 * Calls {@link afterFind}.
	 * This method is internally used.
	 * @since 1.0.3
	 */
	public function afterFindInternal()
	{
		$this->afterFind();
	}

	/**
	 * Sets the redis hash to use with this record
	 * @param ARedisIterableHash $redisHash the redis hash
	 */
	public function setRedisHash($redisHash)
	{
		$this->_redisHash = $redisHash;
	}

	/**
	 * Gets the redis hash to store the attributes for this record in
	 * @return ARedisIterableHash the redis hash
	 */
	public function getRedisHash()
	{
		if ($this->_redisHash === null) {
			$this->_redisHash = new ARedisHash($this->getRedisKey(), $this->getRedisConnection());
		}
		return $this->_redisHash;
	}

	/**
	 * Sets the redis set that contains the ids of the models of this type
	 * @param ARedisIterableSet $redisSet the redis set
	 */
	public function setRedisSet($redisSet)
	{
		$this->_redisSet = $redisSet;
	}

	/**
	 * Gets the redis set that contains the ids of the models of this type
	 * @return ARedisIterableSet the redis set
	 */
	public function getRedisSet()
	{
		if ($this->_redisSet === null) {
			$this->_redisSet = new ARedisSet(get_class($this), $this->getRedisConnection());
		}
		return $this->_redisSet;
	}


}

