<?php
/* SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright (c) Enalean, 2015. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('pre.php');    

$em = EventManager::instance();
$em->processEvent('before_change_email-complete', array());

$hp = Codendi_HTMLPurifier::instance();
$request = HTTPRequest::instance();
$user_manager = UserManager::instance();


/** @var PFUser */
$user = $user_manager->getUserByConfirmHash($request->getValidated('confirm_hash', 'string', ''));

if ($user === null) {
    $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('account_change_email-complete', 'duplicate_hash'));
    $GLOBALS['Response']->redirect('/');
}

$old_email_user = clone $user;
$user->clearConfirmHash();
$user->setEmail($old_email_user->getEmailNew());
$user->setEmailNew($old_email_user->getEmail());

$user_manager->updateDb($user);

$em->processEvent(Event::USER_EMAIL_CHANGED, $user->getId());

$HTML->header(array('title'=>$Language->getText('account_change_email-complete', 'title')));
?>
<p><b><?php echo $Language->getText('account_change_email-complete', 'title'); ?></b>
<P><?php echo $Language->getText('account_change_email-complete', 'message',
			     array(  $hp->purify($user->getRealname(), CODENDI_PURIFIER_CONVERT_HTML) , $user->getEmail(),
				    $GLOBALS['sys_name'], $user->getUsername())); ?>

<P><A href="/">[ <?php echo $Language->getText('global', 'back_home'); ?> ]</A>

<?php
$HTML->footer(array());

?>
