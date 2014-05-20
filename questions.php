<?php include_once __DIR__ . "/common.php";

if (!isset($_GET["username"])) {
	exit;
}

$stmt = $GLOBALS["db"]->prepare("SELECT id, username FROM user WHERE username = ?");
$stmt->bind_param("s", $_GET["username"]);
$stmt->execute();
$stmt->bind_result($asked_userid, $asked_username);
$res = $stmt->fetch();
$stmt->close();
if (!$res) {
	exit;
}

if (!empty($_POST)) {

	$stmt = false;
	if (isset($_POST["mode"])) {
		if (!isset($_POST["id"])) exit;

		if ($_POST["mode"] == "delete") {
			if ($asked_userid == $user_id) {
				// $stmt = $GLOBALS["db"]->prepare("DELETE FROM question WHERE asked_user = ? AND id = ?");
				$stmt = $GLOBALS["db"]->prepare("UPDATE question SET hide = '1' WHERE asked_user = ? AND id = ?");
				$stmt->bind_param("si", $user_id, $_POST["id"]);
			} else {
				// $stmt = $GLOBALS["db"]->prepare("DELETE FROM question WHERE asked_user = ? AND asking_user = ? AND id = ?");
				$stmt = $GLOBALS["db"]->prepare("UPDATE question SET hide = '1' WHERE asked_user = ? AND asking_user = ? AND id = ?");
				$stmt->bind_param("sii", $asked_userid, $user_id, $_POST["id"]);
			}
		} elseif ($_POST["mode"] == "edit") {
			if ($asked_userid == $user_id) {
				if (!empty($_POST["answer"])) {
					$stmt = $GLOBALS["db"]->prepare("SELECT u.username, u.email FROM question q LEFT JOIN user u ON q.asking_user = u.id WHERE q.id = ?");
					$stmt->bind_param("i", $_POST["id"]);
					$stmt->execute();
					$stmt->bind_result($asking_username, $asking_email);
					$res = $stmt->fetch();
					$stmt->close();

					if (!$res) $asking_email = false;

					$stmt = $GLOBALS["db"]->prepare("UPDATE question SET question = ?, answer = ?, private_answer = ?, answered = NOW() WHERE asked_user = ? AND id = ?");
					$private_answer = strval(isset($_POST["private_answer"]) ? 1 : 0);
					$stmt->bind_param("sssii", $_POST["question"], $_POST["answer"], $private_answer, $user_id, $_POST["id"]);

					if ($asking_email) Notification::answer($asking_username, Page::$username, $_POST["question"], $_POST["answer"]);
				}
			} else {
				$stmt = $GLOBALS["db"]->prepare("UPDATE question SET question = ? WHERE asking_user = ? AND id = ?");
				$stmt->bind_param("sii", $_POST["question"], $user_id, $_POST["id"]);
			}
		} else {
			$stmt = $GLOBALS["db"]->prepare("UPDATE question SET question = ? WHERE asked_user = ? AND asking_user = ? AND id = ?");
			$stmt->bind_param("siii", $_POST["question"], $asked_userid, $user_id, $_POST["id"]);
		}
	} else {
		if (isset($_POST["ask_anonymously"])) {
			$stmt = $GLOBALS["db"]->prepare("INSERT INTO question (question, asked_user, created) VALUES (?, ?, NOW())");
			$stmt->bind_param("si", $_POST["question"], $asked_userid);
			Notification::question("Someone", $asked_username, $_POST["question"]);
		} else {
			$stmt = $GLOBALS["db"]->prepare("INSERT INTO question (question, asked_user, asking_user, created) VALUES (?, ?, ?, NOW())");
			$stmt->bind_param("sii", $_POST["question"], $asked_userid, $user_id);
			Notification::question(Page::$username, $asked_username, $_POST["question"]);
		}
	}
	if ($stmt) $stmt->execute();

	header("Location: " . Page::getUserpageLink($asked_username));
	exit;
}

Page::header("ask " . $asked_username);

if ($asked_userid == $user_id) {

	$stmt = $GLOBALS["db"]->prepare("SELECT q.id, q.question, q.created, u.username FROM question q LEFT JOIN user u ON q.asking_user = u.id WHERE q.asked_user = ? AND q.answered IS NULL AND q.hide = '0' ORDER BY q.created ASC");
	$stmt->bind_param("s", $asked_userid);
	$stmt->execute();
	$stmt->bind_result($id, $question, $created, $asking_username);
	$res = $stmt->fetch();

	if ($res) {
		?><h1><?php echo msg_you_have_been_asked; ?></h1><?php
		?><p><?php echo msg_note_about_being_asked; ?></p><?php

		do {
			?><p class="question"><?php
				Page::question($question, $asking_username, $created, true);
			?></p><?php

			?><form method="post" class="question" style="display: none"><?php
				?><input type="hidden" name="id" value="<?php echo $id; ?>" /><?php
				?><input type="hidden" name="mode" class="mode" value="edit" /><?php

				$msg = $asking_username ? msg_asked_by_username_time : msg_asked_anonymously_time;
				echo str_replace(array("%username", "%time"), array($asking_username, Time::since($created)), $msg); ?><br/><?php

				?><textarea name="question" style="width: 100%" rows="3" placeholder="<?php echo msg_question; ?>"><?php echo htmlspecialchars($question); ?></textarea><br/><?php

				?><textarea name="answer" class="answer" style="width: 100%" rows="3" placeholder="<?php echo msg_your_answer; ?>"></textarea><br/><?php

				if ($asking_username) {
					?><label><?php echo str_replace("%checkbox", '<input type="checkbox" name="private_answer" value="1" />', msg_answer_privately_checkbox); ?></label><br/><?php
				}

				?><button class="btn"><?php echo msg_button_answer; ?></button> <?php
				?><button class="btn cancel-question-edit"><?php echo msg_button_cancel_answer; ?></button> <?php
				?><button class="pull-right btn delete-question"><?php echo msg_button_delete_question; ?></button><?php

			?></form><?php
		} while ($stmt->fetch());

	} else {
		$stmt = $GLOBALS["db"]->prepare("SELECT COUNT(*) FROM question WHERE asked_user = ?");
		$stmt->bind_param("i", $asked_userid);
		$stmt->execute();
		$stmt->bind_result($count);
		$res = $stmt->fetch();

		if ($res && $count) {
			?><h1><?php echo msg_you_dont_have_any_open_questions; ?></h1><?php
		} else {
			?><h1><?php echo msg_you_havent_been_asked_anything_yet; ?></h1><?php
		}

		?><p><a href="index.php"><?php echo msg_ask_someone_else_about_something; ?></a></p><?php
	}
	$stmt->close();
}

$sql = "SELECT q.id, q.question, q.answer, q.private_answer, u.username, q.created, q.answered FROM question q LEFT JOIN user u ON q.asking_user = u.id WHERE q.asked_user = ? AND q.answered IS NOT NULL AND q.hide = '0' ";
if (Page::$username != $asked_username) $sql .= "AND q.private_answer = '0' OR (q.asking_user = ? AND q.private_answer = '1') ";
$sql .= "ORDER BY q.answered DESC";

$stmt = $GLOBALS["db"]->prepare($sql);
if (Page::$username != $asked_username) {
	$stmt->bind_param("ii", $asked_userid, $asked_userid);
} else {
	$stmt->bind_param("i", $asked_userid);
}
$stmt->execute();

$stmt->bind_result($id, $question, $answer, $private_answer, $asking_username, $created, $answered);
$res = $stmt->fetch();

if ($res) {

	$headline = $asked_username == Page::$username ? msg_you_have_answered_these_questions : msg_username_has_answered_these_questions;
	?><h1><?php echo str_replace("%username", ucfirst($asked_username), $headline); ?></h1><?php

	do {
		if ($private_answer) {
			?><div class="privately"><?php
		}

		?><p class="question"><?php
			Page::question($question, $asking_username, $created);
		?></p><p class="answer"><?php
			Page::answer($answer, $asked_username, $private_answer, $answered, Page::$username == $asked_username);
		?></p><?php

		if (Page::$username == $asked_username) {
			?><form method="post" style="display: none"><?php

				?><input type="hidden" name="id" value="<?php echo $id; ?>" /><?php
				?><input type="hidden" name="mode" class="mode" value="edit" /><?php

				echo str_replace("%username", Page::$username, msg_question_by_username); ?><br/><?php

				?><textarea name="question" style="width: 100%" rows="3"><?php echo htmlspecialchars($question); ?></textarea><br/><?php

				echo msg_your_answer; ?>:<br/>

				<textarea name="answer" style="width: 100%" rows="3"><?php echo htmlspecialchars($answer); ?></textarea><br/><?php

				?><label><?php echo str_replace("%checkbox", '<input type="checkbox" name="private_answer" value="1"' . ($private_answer ? ' checked="checked"' : '') . ' />', msg_answer_privately_checkbox); ?></label><br/><?php

				?><button class="btn"><?php echo msg_button_save; ?></button> <?
				?><button class="btn cancel-question-edit"><?php echo msg_button_cancel_edit; ?></button> <?php
				?><button class="pull-right btn delete-question"><?php echo msg_button_delete_question; ?></button><?php

			?></form><?php
		}

		if ($private_answer) {
			?></div><?php
		}

	} while ($res = $stmt->fetch());

}
$stmt->close();

if ($asked_userid != $user_id) {
	?><h2><?php echo str_replace("%username", ucfirst($asked_username), msg_ask_username); ?></h2><?php

	?><form method="post"><?php

		?><textarea name="question" style="width: 100%" rows="3" placeholder="<?php echo msg_your_question; ?>"></textarea><br/><?php

		?><label><?php echo str_replace("%checkbox", '<input type="checkbox" name="ask_anonymously" value="1" />', msg_ask_anonymously_checkbox); ?></label><br/><?php

		?><button class="btn"><?php echo msg_button_ask; ?></button><?php

	?></form><?php

	?><p><?php echo str_replace("%username", ucfirst($asked_username), msg_note_about_question); ?></p><?php

	$stmt = $GLOBALS["db"]->prepare("SELECT id, question, created FROM question WHERE asking_user = ? AND asked_user = ? AND hide = '0' AND answered IS NULL ORDER BY created DESC");
	$stmt->bind_param("ii", $user_id, $asked_userid);
	$stmt->execute();
	$stmt->bind_result($id, $question, $created);
	$res = $stmt->fetch();

	if ($res) {
		?><h3><?php echo str_replace("%username", ucfirst($asked_username), msg_username_has_not_yet_answered_these_questions_of_yours); ?></h3><?php

		do {
			?><p class="question"><?php
				Page::question($question, Page::$username, $created, false, true);
			?></p><?php

			?><form method="post" style="display: none"><?php
				?><input type="hidden" name="id" value="<?php echo $id; ?>" /><?php
				?><input type="hidden" name="mode" class="mode" value="edit" /><?php

				?><textarea name="question" style="width: 100%" rows="3" placeholder="<?php echo msg_question; ?>"><?php echo htmlspecialchars($question); ?></textarea><br/><?php

				?><button class="btn"><?php echo msg_button_save; ?></button> <?
				?><button class="btn cancel-question-edit"><?php echo msg_button_cancel_edit; ?></button> <?php
				?><button class="pull-right btn delete-question"><?php echo msg_button_delete_question; ?></button><?php

			?></form><?php
		} while ($stmt->fetch());
	}
	$stmt->close();

	$stmt = $GLOBALS["db"]->prepare("SELECT COUNT(*) FROM question WHERE asking_user != ? AND asked_user = ? AND answered IS NULL AND hide = '0'");
	$stmt->bind_param("ii", $user_id, $asked_userid);
	$stmt->execute();
	$stmt->bind_result($count);
	$res = $stmt->fetch();

	if ($res && $count) {
		?><p><?php echo str_replace("%count", $count, $count == 1 ? msg_1_unanswered_question_asked_by_others : msg_count_unanswered_questions_asked_by_others); ?></p><?php
	}

}
Page::footer();
