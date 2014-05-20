<?php include_once __DIR__ . "/common.php";

Page::header();

$stmt = $GLOBALS["db"]->prepare("SELECT u.username, COUNT(q.answered), COUNT(q.id) FROM user u LEFT JOIN question q ON q.hide = '0' AND u.id = q.asked_user GROUP BY u.id ORDER BY u.username ASC");
$stmt->execute();
$stmt->bind_result($user, $answered, $open);
$res = $stmt->fetch();

?><ul class="users"><?php
do {
	?><li<?php if (Page::$username == $user) echo ' class="me"'; ?>><a href="<?php echo Page::getUserpageLink($user); ?>"><?php echo Page::$username == $user ? msg_you : ucfirst($user); ?></a><br/><?php echo str_replace(array("%answered", "%open"), array($answered, $open), msg_answered_open_questions); ?></li>
	</tr><?php
} while ($res = $stmt->fetch());
?></ul><?php
$stmt->close();

Page::footer();
