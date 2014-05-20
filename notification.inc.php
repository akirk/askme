<?php

class Notification {
	public static function question($asking_username, $asked_username, $question) {
		if (!NOTIFICATION_MAILS_ENABLED) return;

		$stmt = $GLOBALS["db"]->prepare("SELECT id, last_emailed, last_emailer, email FROM user WHERE username = ?");
		$stmt->bind_param("s", $asked_username);
		$stmt->execute();
		$stmt->bind_result($asked_userid, $last_emailed, $last_emailer, $email);
		$res = $stmt->fetch();
		$stmt->close();
		if (!$res) {
			exit;
		}

		if (!$email) return;

		if ($last_emailer == $asking_username && $last_emailed) {
			$last_emailed = strtotime($last_emailed);
			if ($last_emailed + 3600 > time()) return;
		}

		$subject = str_replace("%username", $asked_username, msg_mail_question_asked_subject);
		$body = str_replace(array("%me", "%username", "%question", "%url"), array($asking_username, $asked_username, $question, BASE_URL . "questions.php?username=$asked_username"), msg_mail_question_asked_body);

		mail($email, $subject, $body, "From: " . NOTIFICATION_FROM_ADDRESS . "\r\nReply-To: alexander@kirk.at\r\n");

		$stmt = $GLOBALS["db"]->prepare("UPDATE user SET last_emailed = NOW(), last_emailer = ? WHERE id = ?");
		$stmt->bind_param("si", $asking_username, $asked_userid);
		$stmt->execute();
		$stmt->close();
	}

	public static function answer($asking_username, $asked_username, $question, $answer) {
		if (!NOTIFICATION_MAILS_ENABLED) return;

		$stmt = $GLOBALS["db"]->prepare("SELECT id, last_emailed, last_emailer, email FROM user WHERE username = ?");
		$stmt->bind_param("s", $asking_username);
		$stmt->execute();
		$stmt->bind_result($asking_userid, $last_emailed, $last_emailer, $email);
		$res = $stmt->fetch();
		$stmt->close();
		if (!$res) {
			exit;
		}

		if (!$email) return;

		if ($last_emailer == $asked_username && $last_emailed) {
			$last_emailed = strtotime($last_emailed);
			if ($last_emailed + 3600 < time()) return;
		}

		$subject = str_replace("%username", $asked_username, msg_mail_question_answered_subject);
		$body = str_replace(array("%me", "%username", "%question", "%answer", "%url"), array($asking_username, $asked_username, $question, $answer, BASE_URL . "questions.php?username=$asked_username"), msg_mail_question_answered_body);

		mail($email, $subject, $body, "From: alexander@kirk.at\r\nReply-To: alexander@kirk.at\r\n");

		$stmt = $GLOBALS["db"]->prepare("UPDATE user SET last_emailed = NOW(), last_emailer = ? WHERE id = ?");
		$stmt->bind_param("si", $asked_username, $asking_userid);
		$stmt->execute();
		$stmt->close();
	}
}
