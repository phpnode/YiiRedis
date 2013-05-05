<?php
/**
 * Commonly required settings for the redis tests
 */
Yii::import("packages.redis.*");
define("REDIS_HOSTNAME","localhost");
define("REDIS_PORT", 6379);
define("REDIS_DATABASE",1);
define("REDIS_PASSWORD", null);
