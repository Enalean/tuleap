<?php
//
// Copyright 2015-2018 (c) Enalean
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//

use Tuleap\User\Password\Reset\TokenNotCreatedException;

require_once('pre.php');

$event_manager = EventManager::instance();
$event_manager->processEvent('before_lostpw-confirm', array());

$number_generator = new RandomNumberGenerator();
$confirm_hash     = $number_generator->getNumber();

$request      = HTTPRequest::instance();
$user_manager = UserManager::instance();

$user = $user_manager->getUserByUserName($request->get('form_loginname'));
if ($user === null) {
    exit_error('Invalid User', 'That user does not exist.');
}

$reset_token_dao         = new Tuleap\User\Password\Reset\DataAccessObject();
$random_number_generator = new RandomNumberGenerator();
$password_handler        = PasswordHandlerFactory::getPasswordHandler();
$reset_token_creator     = new \Tuleap\User\Password\Reset\Creator($reset_token_dao, $random_number_generator, $password_handler);
try {
    $reset_token = $reset_token_creator->create($user);
} catch (TokenNotCreatedException $ex) {
    $GLOBALS['Response']->addFeedback(
        Feedback::ERROR,
        $GLOBALS['Language']->getText('account_lostpw-confirm', 'token_generation_failed')
    );
    $GLOBALS['Response']->redirect('/account/lostpw.php');
}

$message = stripcslashes($Language->getText('account_lostpw-confirm', 'mail_body',
	      array($GLOBALS['sys_name'],
                $request->getServerUrl(). '/account/lostlogin.php?confirm_hash=' . urlencode($reset_token->getIdentifier()))));

$mail = new Codendi_Mail();
$mail->setTo($user->getEmail(), true);
$mail->setSubject($Language->getText('account_lostpw-confirm', 'mail_subject', array($GLOBALS['sys_name'])));
$mail->setBodyText($message);
$mail->setFrom($GLOBALS['sys_noreply']);
$mail_is_sent = $mail->send();
if (!$mail_is_sent) {
    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])), CODENDI_PURIFIER_FULL);
}
site_header(array('title'=>$Language->getText('account_lostpw-confirm', 'title')));
if ($mail_is_sent) {
    echo '<p>'. $Language->getText('account_lostpw-confirm', 'msg_confirm') .'</p>';
}
echo '<p><a href="/">['. $Language->getText('global', 'back_home'). ']</a></p>';
site_footer(array());

?>
