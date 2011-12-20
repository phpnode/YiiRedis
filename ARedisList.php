<?php
/**
 * Represents a redis list.
 * <pre>
 * $list = new ARedisList("myList");
 * $list[] = "an item"; // instantly saved to redis
 * $list[] = "another item"; // instantly saved to redis
 * echo count($list); // 2
 * echo $list->pop() // "another item"
 * echo count($list); // 1
 * </pre>
 * @author Charles Pick
 * @package packages.redis
 */
class ARedisList extends ARedisIterableEntity {
	/**
	 * Adds an item to the list
	 * @param mixed $item the item to add
	 * @return boolean true if the item was added, otherwise false
	 */
	public function add($item) {
		if ($this->name === null) {
			throw new CException(get_class($this)." requires a name!");
		}
		if (!$this->getConnection()->getClient()->rpush($this->name,$item)) {
			return false;
		}
		$this->_data = null;
		$this->_count = null;
		return true;
	}

	/**
	 * Removes an item from the list
	 * @param mixed $item the item to remove
	 * @return boolean true if the item was removed, otherwise false
	 */
	public function remove($item) {
		if ($this->name === null) {
			throw new CException(get_class($this)." requires a name!");
		}
		if (!$this->getConnection()->getClient()->lrem($this->name,$item,1)) {
			return false;
		}
		$this->_data = null;
		$this->_count = null;
		return true;
	}

	/**
	 * Adds an item to the end of the list
	 * @param mixed $item the item to add
	 * @return boolean true if the item was added, otherwise false
	 */
	public function push($item) {
		return $this->add($item);
	}

	/**
	 * Adds an item to the start of the list
	 * @param mixed $item the item to add
	 * @return boolean true if the item was added, otherwise false
	 */
	public function unshift($item) {
		if ($this->name === null) {
			throw new CException(get_class($this)." requires a name!");
		}
		if (!$this->getConnection()->getClient()->lpush($this->name,$item)) {
			return false;
		}
		$this->_data = null;
		$this->_count = null;
		return true;
	}

	/**
	 * Removes and returns the first item from the list
	 * @return mixed the item that was removed from the list
	 */
	public function shift() {
		if ($this->name === null) {
			throw new CException(get_class($this)." requires a name!");
		}
		$item = $this->getConnection()->getClient()->lpop($this->name);
		$this->_data = null;
		$this->_count = null;
		return $item;
	}

	/**
	 * Removes and returns the last item from the list
	 * @return mixed the item that was removed from the list
	 */
	public function pop() {
		if ($this->name === null) {
			throw new CException(get_class($this)." requires a name!");
		}
		$item = $this->getConnection()->getClient()->rpop($this->name);
		$this->_data = null;
		$this->_count = null;
		return $item;
	}
	/**
	 * Gets a range of items in the list
	 * @param integer $start the 0 based index to start from
	 * @param integer $stop  the 0 based index to end at
	 * @return array the items in the range
	 */
	public function range($start = 0, $stop = -1) {
		if ($this->name === null) {
			throw new CException(get_class($this)." requires a name!");
		}
		return $this->getConnection()->getClient()->lrange($this->name, $start, $stop);
	}
	/**
	 * Trims the list so that it will only contain the specified range of items
	 * @param integer $start the 0 based index to start from
	 * @param integer $stop the 0 based index to end at
	 * @return boolean true if the trim was successful
	 */
	public function trim($start, $stop) {
		if ($this->name === null) {
			throw new CException(get_class($this)." requires a name!");
		}
		return $this->getConnection()->getClient()->ltrim($this->name, $start, $stop) ? true : false;
	}

	/**
	 * Gets the number of items in the list
	 * @return integer the number of items in the list
	 */
	public function getCount() {
		if ($this->_count === null) {
			if ($this->name === null) {
				throw new CException(get_class($this)." requires a name!");
			}
			$this->_count = (int) $this->getConnection()->getClient()->lSize($this->name);
		}
		return $this->_count;
	}
	/**
	 * Gets all the members in the list
	 * @param boolean $forceRefresh whether to force a refresh or not
	 * @return array the members in the list
	 */
	public function getData($forceRefresh = false) {
		if ($forceRefresh || $this->_data === null) {
			$this->_data = $this->range(0,-1);
		}
		return $this->_data;
	}

	/**
	 * Copies iterable data into the list.
	 * Note, existing data in the list will be cleared first.
	 * @param mixed $data the data to be copied from, must be an array or object implementing Traversable
	 * @throws CException If data is neither an array nor a Traversable.
	 */
	public function copyFrom($data)
	{
		if(is_array($data) || ($data instanceof Traversable))
		{
			if($this->_count>0)
				$this->clear();
			if($data instanceof CList)
				$data=$data->_data;
			foreach($data as $item) {
				$this->add($item);
			}
		}
		else if($data!==null)
			throw new CException(Yii::t('yii','List data must be an array or an object implementing Traversable.'));
	}

	/**
	 * Returns whether there is an item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer $offset the offset to check on
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return ($offset>=0 && $offset<$this->getCount());
	}

	/**
	 * Returns the item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer $offset the offset to retrieve item.
	 * @return mixed the item at the offset
	 */
	public function offsetGet($offset)
	{
		return $this->_data[$offset];
	}

	/**
	 * Sets the item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer $offset the offset to set item
	 * @param mixed $item the item value
	 */
	public function offsetSet($offset,$item)
	{
		$this->add($item);
	}

	/**
	 * Unsets the item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer $offset the offset to unset item
	 */
	public function offsetUnset($offset)
	{
		$this->remove($this->_data[$offset]);
	}
}