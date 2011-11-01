<?php
Yii::import("packages.redis.*");
class ARedisCommand extends CConsoleCommand {

	public function actionSubscribe($args) {

		$redis = $this->getConnection();
		$channel = new ARedisChannel(array_shift($args), $redis);
		$channel->onReceiveMessage = function(CEvent $event) {
			$message = (object) json_decode($event->sender->getLastMessage());
			if (preg_match_all("/in (.*) \((.*)\)/",$message->message,$matches)) {
				foreach($matches[1] as $filename) {
					$line = array(
								$message->time,
								$message->category,
								"M",
								$filename
							);
					echo implode("|",$line)."\n";

				}

			}
		};
		$channel->subscribe();
	}
	/**
	 * Gets the connection to redis
	 * @return ARedisConnection the connection to redis
	 */
	public function getConnection() {
		return Yii::app()->redis;
	}
}