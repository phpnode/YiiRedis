<h1>YiiRedis</h1>

Provides object oriented access to Redis in a familiar Yii Style.
When you add or remove items from redis entities (lists, sets, sorted sets, hashes), changes
are pushed to the server immediately, this is useful when your application needs to make information
available across multiple requests.

This package relies on the https://github.com/nicolasff/phpredis PHP extension, please make sure this is installed before continuing.

<h2>Usage</h2>

<h3>Configuring The Redis Connection</h3>
<p>Add the following to your application config</p>
<pre>
"components" => array(
	"redis" => array(
		"class" => "packages.redis.ARedisConnection",
		"hostname" => "localhost",
		"port" => 6379
	),
	...
),
</pre>

<h3>Storing and Retrieving simple keys</h3>
<p>To store a simple value in a key and read it back:</p>
<pre>
Yii::app()->redis->getClient()->set("myKey", "Your Value");
echo Yii::app()->redis->getClient()->get("myKey"); // outputs "Your Value"
Yii::app()->redis->getClient()->del("myKey"); // deletes the key
</pre>


<h3>Using lists</h3>
<p>Redis lists are simple lists of values that are kept in the order that the items are added</p>

<pre>
$list = new ARedisList("aNameForYourListGoesHere");
$list->add("cats");
$list->add("dogs");
$list->add("fish");
foreach($list as $i => $val) {
	echo $val."<br />";
}
$list->clear(); // delete the list
</pre>


<h3>Using sets</h3>
<p>Redis sets are unordered lists of non repeatable values</p>

<pre>
$set = new ARedisSet("aNameForYourSet");
$set->add(1);
$set->add(2);
$set->add(3);
echo $set->count; // outputs 3
$set->add(3);
echo $set->count; // still 3, cannot add the same value more than once
foreach($set as $val) {
	echo $val."<br />";
}
</pre>


<h3>Using Sorted Sets</h3>
<p>Redis sorted sets are lists of non repeatable values where each value is associated with a score that affects
the order of the results</p>

<pre>
$sortedSet = new ARedisSortedSet("aNameForYourSortedSet");
$sortedSet->add("myValue", 0.4);
$sortedSet->add("myOtherValue", 0.8);
$sortedSet->add("myOtherOtherValue", 0.9);
foreach($sortedSet as $key => $score) {
	echo $key.": ".$score."<br />";
}
</pre>


<h3>Using Hashes</h3>
<p>Redis Hashes are maps between string fields and string values, so they are the perfect data type to represent objects (eg: A User with a number of fields like name, surname, age, and so forth)</p>

<pre>
$hash = new ARedisHash("myHashNameHere");
$hash->whatever = "someValue";
$hash->greeting = "hello world";

echo $hash->count; // outputs 2
</pre>

<h3>Using Pub/Sub</h3>
<p>Redis allows us to subscribe to channels and publish messages to them.</p>

<pre>
$channel = new ARedisChannel("myChan");
$channel->onReceiveMessage = function($redis, $channel, $message) {
	echo "Message Received: ".$message."\n";
}
$channel->publish("hello world"); // sends a messsage to the channel
$channel->subscribe(); // subscribes to the channel and listens to messages, blocks the process
</pre>

<h3>Using Redis for Counters</h3>
<p>Often we need to store counters for a particular database table or row, with YiiRedis this is fast and easy.</p>

<pre>
$counter = new ARedisCounter("totalPageViews");
$counter->increment();
echo $counter->getValue();
</pre>

<h3>Using Redis for Mutexes</h3>
<p>Mutexes are useful to ensure that only one client can access a particular resources at a time.</p>
<pre>
$mutex = new ARedisMutex("someOperation");
$mutex->block(); // blocks execution until the resource becomes available
// do something
$mutex->unlock(); // release the lock
</pre>

<h3>Using Redis for Caching</h3>
<p>Redis is a good alternative to memcached for caching because its speed is comparable and the data is persisted.</p>
<p>In your app config:</p>
<pre>
"components" => array(
	"cache" => array(
		"class" => "packages.redis.ARedisCache"
	),
	...
),
</pre>


<h3>Using Redis as a backend for Active Record</h3>
<p>It is possible to store active record like structures in redis using ARedisRecord.</p>
<p>Note: this is experimental functionality and may be subject to change</p>

<pre>
$record = ARedisRecord::model()->findByPk(1); // loads a record with a unique id of 1
$record->name = "a test name"; // sets the name attribute on the record
$record->somethingElse = "some other value";
$record->save(); // saves the record to redis
$record->delete(); // deletes the record from redis
</pre>

[![githalytics.com alpha](https://cruel-carlota.pagodabox.com/d204205868904fefb008f31d7cb50b12 "githalytics.com")](http://githalytics.com/phpnode/YiiRedis)
