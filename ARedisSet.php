<?php
/**
 * Represents a redis set.
 * Redis Sets are an unordered collection of Strings. It is possible to add, remove, and test for existence of members in O(1) (constant time regardless of the number of elements contained inside the Set).
 *
 * <pre>
 * $set = new ARedisSet("mySet");
 * $set->add(1);
 * $set->add(2);
 * $set->add(3);
 *
 * $otherSet = new ARedisSet("myOtherSet");
 * $otherSet->add(2);
 *
 * print_r($set->diff($otherSet)); // the difference between the sets
 * </pre>
 *
 *
 * @author Charles Pick
 * @package packages.redis
 */
class ARedisSet extends ARedisIterableEntity {
	/**
	 * Adds an item to the set
	 * @param mixed $item the item to add
	 * @return boolean true if the item was added, otherwise false
	 */
	public function add($item) {
		if (!$this->getConnection()->getClient()->sadd($this->name,$item)) {
			return false;
		}
		$this->_data = null;
		$this->_count = null;
		return true;
	}
	/**
	 * Removes an item from the set
	 * @param mixed $item the item to remove
	 * @return boolean true if the item was removed, otherwise false
	 */
	public function remove($item) {
		if (!$this->getConnection()->getClient()->srem($this->name,$item)) {
			return false;
		}
		$this->_data = null;
		$this->_count = null;
		return true;
	}

	/**
	 * Removes and returns a random item from the set
	 * @return mixed the item that was removed from the set
	 */
	public function pop() {
		$member = $this->getConnection()->getClient()->spop($this->name);
		$this->_data = null;
		$this->_count = null;
		return $member;
	}
	/**
	 * Gets a random member of the set
	 * @return mixed a random member of the set
	 */
	public function random() {
		return $this->getConnection()->getClient()->srandmember($this->name);
	}

	/**
	 * Gets the difference between this set and the given set(s) and returns it
	 * @param mixed $set, $set2 The sets to compare to, either ARedisSet instances or their names
	 * @return array the difference between this set and the given sets
	 */
	public function diff($set) {
		if (is_array($set)) {
			$parameters = $set;
		}
		else {
			$parameters = func_get_args();
		}
		foreach($parameters as $n => $set) {
			if ($set instanceof ARedisSet) {
				$parameters[$n] = $set->name;
			}
		}
		array_unshift($parameters,$this->name);
		return call_user_func_array(array(
										 $this->getConnection()->getClient(),
										"sdiff"
									),$parameters);
	}

	/**
	 * Gets the difference between this set and the given set(s), stores it in a new set and returns it
	 * @param ARedisSet|string $destination the destination to store the result in
	 * @param mixed $set, $set2 The sets to compare to, either ARedisSet instances or their names
	 * @return ARedisSet a set that contains the difference between this set and the given sets
	 */
	public function diffStore($destination, $set) {
		if ($destination instanceof ARedisSet) {
			$destination->_count = null;
			$destination->_data = null;
		}
		else {
			$destination = new ARedisSet($destination,$this->getConnection());
		}
		if (is_array($set)) {
			$parameters = $set;
		}
		else {
			$parameters = func_get_args();
			array_shift($parameters);
		}
		foreach($parameters as $n => $set) {
			if ($set instanceof ARedisSet) {
				$parameters[$n] = $set->name;
			}
		}

		array_unshift($parameters,$this->name);
		array_unshift($parameters, $destination->name);
		call_user_func_array(array(
									 $this->getConnection()->getClient(),
									"sdiffstore"
								),$parameters);
		return $destination;
	}

	/**
	 * Gets the intersection between this set and the given set(s) and returns it
	 * @param mixed $set, $set2 The sets to compare to, either ARedisSet instances or their names
	 * @return array the intersection between this set and the given sets
	 */
	public function inter($set) {
		if (is_array($set)) {
			$parameters = $set;
		}
		else {
			$parameters = func_get_args();
		}
		foreach($parameters as $n => $set) {
			if ($set instanceof ARedisSet) {
				$parameters[$n] = $set->name;
			}
		}
		array_unshift($parameters,$this->name);
		return call_user_func_array(array(
										 $this->getConnection()->getClient(),
										"sinter"
									),$parameters);
	}
	/**
	 * Gets the intersection between this set and the given set(s), stores it in a new set and returns it
	 * @param ARedisSet|string $destination the destination to store the result in
	 * @param mixed $set, $set2 The sets to compare to, either ARedisSet instances or their names
	 * @return ARedisSet a set that contains the intersection between this set and the given sets
	 */
	public function interStore($destination, $set) {
		if ($destination instanceof ARedisSet) {
			$destination->_count = null;
			$destination->_data = null;
		}
		else {
			$destination = new ARedisSet($destination,$this->getConnection());
		}
		if (is_array($set)) {
			$parameters = $set;
		}
		else {
			$parameters = func_get_args();
			array_shift($parameters);
		}
		foreach($parameters as $n => $set) {
			if ($set instanceof ARedisSet) {
				$parameters[$n] = $set->name;
			}
		}

		array_unshift($parameters,$this->name);
		array_unshift($parameters, $destination->name);
		call_user_func_array(array(
									 $this->getConnection()->getClient(),
									"sinterstore"
								),$parameters);
		return $destination;
	}

	/**
	 * Gets the union of this set and the given set(s) and returns it
	 * @param mixed $set, $set2 The sets to compare to, either ARedisSet instances or their names
	 * @return array the union of this set and the given sets
	 */
	public function union($set) {
		if (is_array($set)) {
			$parameters = $set;
		}
		else {
			$parameters = func_get_args();
		}
		foreach($parameters as $n => $set) {
			if ($set instanceof ARedisSet) {
				$parameters[$n] = $set->name;
			}
		}
		array_unshift($parameters,$this->name);
		return call_user_func_array(array(
										 $this->getConnection()->getClient(),
										"sunion"
									),$parameters);
	}
	/**
	 * Gets the union of this set and the given set(s), stores it in a new set and returns it
	 * @param ARedisSet|string $destination the destination to store the result in
	 * @param mixed $set, $set2 The sets to compare to, either ARedisSet instances or their names
	 * @return ARedisSet a set that contains the union of this set and the given sets
	 */
	public function unionStore($destination, $set) {
		if ($destination instanceof ARedisSet) {
			$destination->_count = null;
			$destination->_data = null;
		}
		else {
			$destination = new ARedisSet($destination,$this->getConnection());
		}
		if (is_array($set)) {
			$parameters = $set;
		}
		else {
			$parameters = func_get_args();
			array_shift($parameters);
		}
		foreach($parameters as $n => $set) {
			if ($set instanceof ARedisSet) {
				$parameters[$n] = $set->name;
			}
		}

		array_unshift($parameters,$this->name);
		array_unshift($parameters, $destination->name);
		call_user_func_array(array(
									 $this->getConnection()->getClient(),
									"sunionstore"
								),$parameters);
		return $destination;
	}

	/**
	 * Moves an item from this redis set to another
	 * @param ARedisSet|string $destination the set to move the item to
	 * @param mixed $item the item to move
	 * @return boolean true if the item was moved successfully
	 */
	public function move($destination, $item) {
		if ($destination instanceof ARedisSet) {
			$destination->_count = null;
			$destination->_data = null;
			$destination = $destination->name;
		}
		$this->_count = null;
		$this->_data = null;
		return $this->getConnection()->getClient()->smove($this->name, $destination, $item);
	}



	/**
	 * Gets the number of items in the set
	 * @return integer the number of items in the set
	 */
	public function getCount() {
		if ($this->_count === null) {
			$this->_count = $this->getConnection()->getClient()->scard($this->name);
		}
		return $this->_count;
	}
	/**
	 * Gets all the members in the set
	 * @param boolean $forceRefresh whether to force a refresh or not
	 * @return array the members in the set
	 */
	public function getData($forceRefresh = false) {
		if ($forceRefresh || $this->_data === null) {
			$this->_data = $this->getConnection()->getClient()->smembers($this->name);
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
	 * Determines whether the item is contained in the entity
	 * @param mixed $item the item to check for
	 * @return boolean true if the item exists in the entity, otherwise false
	 */
	public function contains($item) {
		return $this->getConnection()->getClient()->sismember($this->name, $item);
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