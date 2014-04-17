<?php
/**
 * Represents a redis sorted set.
 *
 * Redis Sorted Sets are, similarly to Redis Sets, non repeating collections of Strings. The difference is that every member of a Sorted Set is associated with score, that is used in order to take the sorted set ordered, from the smallest to the greatest score. While members are unique, scores may be repeated.
 *
 * <pre>
 * $set = new ARedisSortedSet("mySortedSet");
 * $set->add("myThing", 0.5);
 * $set->add("myOtherThing", 0.6);
 *
 * foreach($set as $key => $score) {
 * 	echo $key.":".$score."\n";
 * }
 * </pre>
 *
 * @author Charles Pick
 * @package packages.redis
 */
class ARedisSortedSet extends ARedisIterableEntity {

	/**
	 * Adds an item to the set
	 * @param string $key the key to add
	 * @param integer $value the score for this key
	 * @return boolean true if the item was added, otherwise false
	 */
	public function add($key, $value) {
		if (!$this->getConnection()->getClient()->zadd($this->name,$value, $key)) {
			return false;
		}
		$this->_data = null;
		$this->_count = null;
		return true;
	}
	/**
	 * Removes an item from the set
	 * @param string $key the item to remove
	 * @return boolean true if the item was removed, otherwise false
	 */
	public function remove($key) {
		if (!$this->getConnection()->getClient()->zrem($this->name,$key)) {
			return false;
		}
		$this->_data = null;
		$this->_count = null;
		return true;
	}

	/**
	 * Increment (or decrement if $byAmount is negative) the score of an item from the set
	 * @param integer $byAmount the amount to increment by, defaults to 1
	 * @return integer the new value of the score if was incremented, otherwise false
	 */
	public function increment($key, $byAmount = 1)
	{
		if (!($score = $this->getConnection()->getClient()->zincrby($this->name, $byAmount, $key))) {
			return false;
		}
		$this->_data = null;
		$this->_count = null;
		return $score;
	}

	/**
	 * Gets the intersection between this set and the given set(s), stores it in a new set and returns it
	 * @param ARedisSortedSet|string $destination the destination to store the result in
	 * @param mixed $set The sets to compare to, either ARedisSortedSet instances or their names
	 * @param array $weights the weights for the sets, if any
	 * @return ARedisSortedSet a set that contains the intersection between this set and the given sets
	 */
	public function interStore($destination, $set, $weights = null) {
		if ($destination instanceof ARedisSortedSet) {
			$destination->_count = null;
			$destination->_data = null;
		}
		else {
			$destination = new ARedisSortedSet($destination,$this->getConnection());
		}
		if (is_array($set)) {
			$sets = $set;
		}
		else {
			$sets = array($set);
		}

		foreach($sets as $n => $set) {
			if ($set instanceof ARedisSortedSet) {
				$sets[$n] = $set->name;
			}
		}

		array_unshift($sets,$this->name);
		$parameters = array(
				$destination->name,
				$sets,
			  );
		if ($weights !== null) {
			$parameters[] = $weights;
		}
		$total = call_user_func_array(array(
									 $this->getConnection()->getClient(),
									"zinter"
								),$parameters);
		$destination->_count = $total;
		return $destination;
	}

	/**
	 * Gets the union of this set and the given set(s), stores it in a new set and returns it
	 * @param ARedisSortedSet|string $destination the destination to store the result in
	 * @param mixed $set The sets to compare to, either ARedisSortedSet instances or their names
	 * @param array $weights the weights for the sets, if any
	 * @return ARedisSortedSet a set that contains the union of this set and the given sets
	 */
	public function unionStore($destination, $set, $weights = null) {
		if ($destination instanceof ARedisSortedSet) {
			$destination->_count = null;
			$destination->_data = null;
		}
		else {
			$destination = new ARedisSortedSet($destination,$this->getConnection());
		}
		if (is_array($set)) {
			$sets = $set;
		}
		else {
			$sets = array($set);
		}

		foreach($sets as $n => $set) {
			if ($set instanceof ARedisSortedSet) {
				$sets[$n] = $set->name;
			}
		}

		array_unshift($sets,$this->name);
		$parameters = array(
				$destination->name,
				$sets,
			  );
		if ($weights !== null) {
			$parameters[] = $weights;
		}
		$total = call_user_func_array(array(
									 $this->getConnection()->getClient(),
									"zunion"
								),$parameters);
		$destination->_count = $total;
		return $destination;
	}


	/**
	 * Returns an iterator for traversing the items in the set.
	 * This method is required by the interface IteratorAggregate.
	 * @return Iterator an iterator for traversing the items in the set.
	 */
	public function getIterator()
	{
		return new CMapIterator($this->getData());
	}


	/**
	 * Gets the number of items in the set
	 * @return integer the number of items in the set
	 */
	public function getCount() {
		if ($this->_count === null) {
			$this->_count = $this->getConnection()->getClient()->zcard($this->name);
		}
		return $this->_count;
	}
	/**
	 * Gets all the members in the  sorted set
	 * @param boolean $forceRefresh whether to force a refresh or not
	 * @return array the members in the set
	 */
	public function getData($forceRefresh = false) {
		if ($forceRefresh || $this->_data === null) {
			$this->_data = $this->getConnection()->getClient()->zrange($this->name,0, -1, true);
		}
		return $this->_data;
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
	 * @throws CException if the offset is invalid
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
		$this->add($offset,$item);
	}

	/**
	 * Unsets the item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer $offset the offset to unset item
	 */
	public function offsetUnset($offset)
	{
		$this->remove($offset);
	}
}