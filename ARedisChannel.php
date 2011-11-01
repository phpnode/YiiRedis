<?php
/**
 * Represents a redis pub/sub channel.
 *
 * Publishing messages:
 * <pre>
 * $channel = new ARedisChannel("myChannel");
 * $channel->publish("hello world"); // sends a message to the channel
 * </pre>
 *
 * Subscribing to channels:
 * <pre>
 * $channel = new ARedisChannel("myChannel");
 * $channel->onReceiveMessage = function($redis, $channel, $message) {
 * 	echo "Message Received:".$message."\n";
 * };
 * $channel->subscribe(); // blocks, the callback is triggered when a message is received
 * </pre>
 * @author Charles Pick
 * @package packages.redis
 */
class ARedisChannel extends ARedisIterableEntity {
	/**
	 * Holds the data in the entity
	 * @var array
	 */
	protected $_data = array();
	/**
	 * Subscribes to the channel
	 * @return ARedisIterableChannel $this subscribed to the channel
	 */
	public function subscribe() {
		if ($this->name === null) {
			throw new CException(get_class($this)." requires a name!");
		}
		$this->getConnection()->getClient()->subscribe(array($this->name),array($this,"receiveMessage"));
		return $this;
	}
	/**
	 * Unsubscribes from the channel
	 * @return ARedisIterableChannel $this unsubscribed from the channel
	 */
	public function unsubscribe() {
		if ($this->name === null) {
			throw new CException(get_class($this)." requires a name!");
		}
		$this->getConnection()->getClient()->unsubscribe(array($this->name));
		return $this;
	}

	/**
	 * Publishes a message to the channel
	 * @param string $message The message to publish
	 * @return integer the number of clients that received the message
	 */
	public function publish($message) {
		if ($this->name === null) {
			throw new CException(get_class($this)." requires a name!");
		}
		$this->_data[] = $message;
		return $this->getConnection()->getClient()->publish($this->name,$message);
	}
	/**
	 * Receives a message from a subscribed channel
	 * @param Redis $redis the redis client instance
	 * @param string $channel the name of the channel
	 * @param string $message the message content
	 */
	public function receiveMessage($redis, $channel, $message) {
		$this->_data[] = $message;
		$event=new CEvent($this);
		$this->onReceiveMessage($event);
	}
	/**
	 * Gets the last received / sent message
	 * @return mixed the last message received, or null if no messages have been received yet
	 */
	public function getLastMessage() {
		$count = count($this->_data);
		if (!$count) {
			return null;
		}
		return $this->_data[$count - 1];
	}

	/**
	 * This event is raised after a message is received
	 * @param CEvent $event the event parameter
	 */
	public function onReceiveMessage($event)
	{
		$this->raiseEvent('onReceiveMessage',$event);
	}

	/**
	 * Gets the number of items in the channel
	 * @return integer the number of items in the channel
	 */
	public function getCount() {
		return count($this->_data);
	}
	/**
	 * Gets all the members in the  sorted set
	 * @param boolean $forceRefresh whether to force a refresh or not, IGNORED!
	 * @return array the members in the set
	 */
	public function getData($forceRefresh = false) {
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
		return isset($this->data[$offset]);
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
		$this->_data[$offset] = $item;
	}

	/**
	 * Unsets the item at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param integer $offset the offset to unset item
	 */
	public function offsetUnset($offset)
	{
		unset($this->_data[$offset]);
	}
}