<?php
if (!isset($_SERVER["PHP_AUTH_USER"])) {
    header('WWW-Authenticate: Basic realm="Ask me"');
    header('HTTP/1.0 401 Unauthorized');
	exit;
}

$config_file = __DIR__ . "/config.php";
if (!file_exists($config_file)) die("No config file. Please copy config.php.dist to config.php");
include $config_file;
if (!isset($GLOBALS["db"])) die("No database connection configured.");

$messages_file = __DIR__ . "/messages.php";
if (!file_exists($messages_file)) die("No messages file. Please copy messages.php.dist to messages.php");
include $messages_file;
if (!defined("msg_pagename")) die("Messages file seems to be malformed.");

include __DIR__ . "/notification.inc.php";
include __DIR__ . "/page.inc.php";
include __DIR__ . "/time.inc.php";

$GLOBALS["db"]->query("SET NAMES 'utf8'");

header("Content-type: text/html;charset=utf8");

$stmt = $GLOBALS["db"]->prepare("SELECT id, username, email FROM user WHERE username = ?");
$stmt->bind_param("s", $_SERVER["PHP_AUTH_USER"]);
$stmt->execute();
$stmt->bind_result($user_id, Page::$username, Page::$email);
$res = $stmt->fetch();
$stmt->close();
if (!$res) {
	exit;
}

$stmt = $GLOBALS["db"]->prepare("UPDATE user SET last_access = NOW() WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();
