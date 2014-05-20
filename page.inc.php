<?php

class Page {
	public static $username, $email;
	public static function header($title = msg_pagetitle) {
		?><html><head>
			<title><?php echo $title; ?></title>
			<meta name="viewport" content="width = device-width" />
			<link rel="stylesheet" type="text/css" href="style.css">
			<script type="text/javascript" src="lib.js"></script>
			<script type="text/javascript" src="script.js"></script>
		</head><body>
		<header>
			<div class="navbar">
				<a href="index.php"><?php echo msg_pagename; ?></a>
				<ul class="nav pull-right">
					<li><a href="<?php echo self::getUserpageLink(self::$username); ?>"><?php echo str_replace(array("%username", "%email"), array(self::$username, self::$email), msg_header_username_email); ?></a></li>
				</ul>
			</div>
		</header><div class="container"><?php
	}

	public static function footer() {
		?></div></body></html><?php
	}

	public static function question($question, $username, $time, $answer_now = false, $editable = false) {
		echo nl2br(htmlspecialchars($question)); ?><br/><?php

		?><span class="smaller"><?php

		$msg = $username ? msg_asked_by_username_time : msg_asked_anonymously_time;
		echo str_replace(array("%username", "%time"), array($username, Time::since($time)), $msg);

		if ($answer_now) {
			?> <a href="" class="edit-question"><?php echo msg_answer_now; ?></a><?php
		}

		if ($editable) {
			?> <a href="" class="edit-question"><?php echo msg_edit; ?></a><?php
		}

		?></span><?php
	}

	public static function answer($answer, $username, $privately, $time, $editable = false) {

		echo nl2br(htmlspecialchars($answer)); ?><br/>
		<span class="smaller"><?php

		$msg = $privately ? msg_answered_privately_time : msg_answered_time;
		echo str_replace("%time", Time::since($time), $msg);

		if ($editable) {
			?> <a href="" class="edit-question"><?php echo msg_edit; ?></a><?php
		}

		?></span><?php
	}

	public static function getUserpageLink($username) {
		return "questions.php?username=" . urlencode($username);
	}
}
