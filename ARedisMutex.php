<?php
/**
 * Represents a redis mutex.
 *
 * Simple usage:
 * <pre>
 * $mutex = new ARedisMutex("someOperation");
 * $mutex->block(); // blocks execution until the resource becomes available
 * // do something
 * $mutex->unlock(); // release the lock
 * </pre>
 *
 * With events:
 * <pre>
 * $mutex = new ARedisMutex("someOperation");
 * $mutex->afterLock = function(CEvent $event) {
 *  echo "Locked!\n";
 * 	// do some processing here
 *  $event->sender->unlock(); // finally, unlock the mutex
 * };
 * $mutex->afterUnlock = function(CEvent $event) {
 *  echo "Unlocked!";
 * }
 * $mutex->block(); // triggers appropriate events when the resource becomes available
 * </pre>
 * @author Charles Pick
 * @package packages.redis
 */
class ARedisMutex extends ARedisEntity {

	/**
	 * The number of seconds before this mutex will automatically expire
	 * @var integer
	 */
	public $expiresAfter = 10;

	/**
	 * The number of micro seconds to sleep for between poll requests.
	 * Defaults to half a second.
	 * @var integer
	 */
	public $pollDelay = 500000;

	/**
	 * The time the mutex expires at
	 * @var integer
	 */
	protected $_expiresAt;


	/**
	 * Attempts to lock the mutex, returns true if successful or false if the mutex is locked by another process.
	 * @return boolean whether the lock was successful or not
	 */
	public function lock() {
		if (!$this->beforeLock()) {
			return false;
		}
		$redis = $this->getConnection()->getClient();
		if (!$redis->setnx($this->name, $this->getExpiresAt(true))) {
			// see if this mutex has expired

			$value = $redis->get($this->name);
			if ($value > microtime(true)) {
				return false;
			}
			else {
				//prevent a race codition if another process finds it expired at the same time
				$current = $redis->getset($this->name, $this->getExpiresAt(true));
				if ($value != $current){
					return false; 
				}
			}
		}
		$this->afterLock();
		return true;
	}
	/**
	 * Attempts to unlock the mutex, returns true if successful, or false if the mutex is in use by another process
	 * @return boolean whether the unlock was successful or not
	 */
	public function unlock() {
		if (!$this->beforeUnlock()) {
			return false;
		}
		$redis = $this->getConnection()->getClient();
		$value = $redis->get($this->name);
		$decimalPlaces = max(strlen(substr($value,strpos($value,"."))),strlen(substr($this->_expiresAt,strpos($this->_expiresAt,".")))) - 1;
		if (bccomp($value,$this->_expiresAt,$decimalPlaces) == -1 && bccomp($value,microtime(true),$decimalPlaces) == 1) {
			return false;
		}


		$redis->delete($this->name);
		$this->afterUnlock();
		return true;
	}
	/**
	 * Blocks program execution until the lock becomes available
	 * @return ARedisMutex $this after the lock is opened
	 */
	public function block() {
		while($this->lock() === false) {

			usleep($this->pollDelay);
		}
		return $this;
	}

	/**
	 * Invoked before the mutex is locked.
	 * The default implementation raises the onBeforeLock event
	 * @return boolean true if the lock should continue
	 */
	public function beforeLock() {
		$event = new CModelEvent();
		$event->sender = $this;
		$this->onBeforeLock($event);
		return $event->isValid;
	}

	/**
	 * Invoked after the mutex is locked.
	 * The default implementation raises the onAfterLock event
	 */
	public function afterLock() {
		$event = new CEvent;
		$event->sender = $this;
		$this->onAfterLock($event);
	}

	/**
	 * Invoked before the mutex is unlocked.
	 * The default implementation raises the onBeforeUnlock event
	 * @return boolean true if the unlock should continue
	 */
	public function beforeUnlock() {
		$event = new CModelEvent;
		$event->sender = $this;
		$this->onBeforeUnlock($event);
		return $event->isValid;
	}

	/**
	 * Invoked after the mutex is unlocked.
	 * The default implementation raises the onAfterUnlock event
	 */
	public function afterUnlock() {
		$event = new CEvent;
		$event->sender = $this;
		$this->onAfterUnlock($event);
	}

	/**
	 * Raises the onBeforeLock event
	 * @param CEvent $event the event to raise
	 */
	public function onBeforeLock($event) {
		$this->raiseEvent("onBeforeLock",$event);
	}

	/**
	 * Raises the onAfterLock event
	 * @param CEvent $event the event to raise
	 */
	public function onAfterLock($event) {
		$this->raiseEvent("onAfterLock",$event);
	}

	/**
	 * Raises the onBeforeUnlock event
	 * @param CEvent $event the event to raise
	 */
	public function onBeforeUnlock($event) {
		$this->raiseEvent("onBeforeUnlock",$event);
	}

	/**
	 * Raises the onAfterUnlock event
	 * @param CEvent $event the event to raise
	 */
	public function onAfterUnlock($event) {
		$this->raiseEvent("onAfterUnlock",$event);
	}


	/**
	 * Gets the time the mutex expires
	 * @param boolean $forceRecalculate whether to force recalculation or not
	 * @return float the time the mutex expires
	 */
	public function getExpiresAt($forceRecalculate = false)
	{
		if ($forceRecalculate || $this->_expiresAt === null) {
			 $this->_expiresAt = $this->expiresAfter + microtime(true);
		}
		return $this->_expiresAt;
	}
}
