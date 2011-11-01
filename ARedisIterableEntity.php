<?php
/**
 * A base class for iterable redis entities (lists, hashes, sets and sorted sets)
 * @author Charles Pick
 * @package packages.redis
 */
abstract class ARedisIterableEntity extends ARedisEntity implements IteratorAggregate,ArrayAccess,Countable {

	/**
	 * The number of items in the entity
	 * @var integer
	 */
	protected $_count;

	/**
	 * Holds the data in the entity
	 * @var array
	 */
	protected $_data;


	/**
	 * Returns an iterator for traversing the items in the set.
	 * This method is required by the interface IteratorAggregate.
	 * @return Iterator an iterator for traversing the items in the set.
	 */
	public function getIterator()
	{
		return new CListIterator($this->getData());
	}

	/**
	 * Returns the number of items in the set.
	 * This method is required by Countable interface.
	 * @return integer number of items in the set.
	 */
	public function count()
	{
		return $this->getCount();
	}

	/**
	 * Gets a list of items in the set
	 * @return array the list of items in array
	 */
	public function toArray()
	{
		return $this->getData();
	}

	/**
	 * Gets the number of items in the entity
	 * @return integer the number of items in the entity
	 */
	abstract public function getCount();

	/**
	 * Gets all the members in the entity
	 * @param boolean $forceRefresh whether to force a refresh or not
	 * @return array the members in the entity
	 */
	abstract public function getData($forceRefresh = false);

	/**
	 * Determines whether the item is contained in the entity
	 * @param mixed $item the item to check for
	 * @return boolean true if the item exists in the entity, otherwise false
	 */
	public function contains($item) {
		return in_array($item, $this->getData());
	}

	/**
	 * Removes all the items from the entity
	 * @return ARedisIterableEntity the current entity
	 */
	public function clear() {
		$this->_data = null;
		$this->_count = null;
		$this->getConnection()->getClient()->delete($this->name);
		return $this;
	}

}