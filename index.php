<?php
// initialize access IP address
$ip = $_SERVER['REMOTE_ADDR'];
empty($ip) && exit;

// set the default timezone
date_default_timezone_set('Asia/Shanghai');

// get the current UNIX timestamp
$current_time = time();
// set the last access time
$last_time = 0;

// connect the Redis Server
$redis = new Redis();
$res = $redis->connect('127.0.0.1', '6379');
$res || exit;

/**
 * insert or update the info of access IP
 */
$ip_info = [
    'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'],
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
    'REQUEST_URI' => $_SERVER['REQUEST_URI'],
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
    'REQUEST_URL' => $_SERVER['SERVER_ADDR'] . $_SERVER['REQUEST_URI'],
    'REQUEST_TIME' => $current_time,
];
$redis->hSet('ips:info', $ip, json_encode($ip_info));
$redis->lPush('ips:access_record', $ip);
$redis->lTrim('ips:access_record', 0, 149);
$access_times = $redis->zScore('ips:access_times', $ip);
empty($access_times) && $access_times = 0;
$redis->zAdd('ips:access_times', ++$access_times, $ip);

/**
 * Determine whether banned IP,
 * and refused to visit, the opposite to continue
 */
require dirname(__FILE__) . '/ipadmin/AccessDenied.php';
if (AccessDenied::isBanIP($redis, $ip)) {
    require dirname(__FILE__) . '/ipadmin/accessDenied.html';
}

/**
 * Determine whether the IP access frequency is more than 10 times/min
 * and refused to visit, allowing access to conversely.
 */
$redis->select(1);
$redis->lPush($ip, $current_time);
$times = $redis->lLen($ip);
if ($times > 10) {
    $last_time = $redis->lIndex($ip, -1);
}
$redis->lTrim($ip, 0, 9);

// close the Redis's connection
$redis->close();

// take corresponding measures to access frequently IP
if ($current_time - $last_time < 60) {
    require dirname(__FILE__) . '/wait.html';
    die;    // access denied
}
