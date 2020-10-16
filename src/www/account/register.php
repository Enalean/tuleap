<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright 1999-2001 (c) VA Linux Systems
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Cryptography\ConcealedString;
use Tuleap\User\Account\RegistrationGuardEvent;

header("Cache-Control: no-cache, no-store, must-revalidate");

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/proj_email.php';
require_once __DIR__ . '/../include/account.php';
require_once __DIR__ . '/../include/timezones.php';

$GLOBALS['HTML']->includeCalendarScripts();
$request = HTTPRequest::instance();
$page = $request->get('page');
$confirmation_register = false;
// ###### function register_valid()
// ###### checks for valid register from form post
if ($page == "admin_creation") {
    $request->checkUserIsSuperUser();
}

$event_manager = EventManager::instance();

$registration_guard = $event_manager->dispatch(new RegistrationGuardEvent());
assert($registration_guard instanceof RegistrationGuardEvent);

if (! $request->getCurrentUser()->isSuperUser() && ! $registration_guard->isRegistrationPossible()) {
    exit_error(
        $GLOBALS['Language']->getText('include_session', 'insufficient_access'),
        $GLOBALS['Language']->getText('include_session', 'no_access')
    );
}

function register_valid(bool $is_password_needed, $mail_confirm_code, array &$errors)
{
    global $Language;

    $request = HTTPRequest::instance();

    $rule_username = new Rule_UserName();
    if (! $rule_username->isValid((string) $request->get('form_loginname'))) {
        $errors['form_loginname'] = $rule_username->getErrorMessage();
        return 0;
    }

    $vRealName = new Valid_RealNameFormat('form_realname');
    $vRealName->required();
    if (! $request->valid($vRealName)) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_realname'));
        $errors['form_realname'] = $Language->getText('account_register', 'err_realname');
        return 0;
    }

    if ($is_password_needed && ! $request->existAndNonEmpty('form_pw')) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_nopasswd'));
        $errors['form_pw'] = $Language->getText('account_register', 'err_nopasswd');
        return 0;
    }
    $tz = $request->get('timezone');
    if (! is_valid_timezone($tz)) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_notz'));
        $errors['timezone'] = $Language->getText('account_register', 'err_notz');
        return 0;
    }
    if (! $request->existAndNonEmpty('form_register_purpose') && (ForgeConfig::get('sys_user_approval') && $request->get('page') != "admin_creation")) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_nopurpose'));
        $errors['form_register_purpose'] = $Language->getText('account_register', 'err_nopurpose');
        return 0;
    }
    if (! validate_email($request->get('form_email'))) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_email'));
        $errors['form_email'] = $Language->getText('account_register', 'err_email');
        return 0;
    }

    $password = null;
    if ($is_password_needed) {
        $password              = new ConcealedString((string) $request->get('form_pw'));
        $password_confirmation = new ConcealedString((string) $request->get('form_pw2'));
        if ($request->get('page') !== "admin_creation" && ! $password->isIdenticalTo($password_confirmation)) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_passwd'));
            $errors['form_pw'] = $Language->getText('account_register', 'err_passwd');
            return 0;
        }

        $password_sanity_checker = \Tuleap\Password\PasswordSanityChecker::build();
        if (! $password_sanity_checker->check($password)) {
            foreach ($password_sanity_checker->getErrors() as $error) {
                $GLOBALS['Response']->addFeedback('error', $error);
            }
            $errors['form_pw'] = 'Error';
            return 0;
        }
    }

    $expiry_date = 0;
    if ($request->exist('form_expiry') && $request->get('form_expiry') != '' && ! preg_match("/[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}/", $request->get('form_expiry'))) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('account_register', 'data_not_parsed'));
        $errors['form_expiry'] = $Language->getText('account_register', 'data_not_parsed');
        return 0;
    }
    $vDate = new Valid_String();
    $vDate->required();
    if ($request->exist('form_expiry') && $vDate->validate($request->get('form_expiry'))) {
        $date_list = preg_split("/-/D", $request->get('form_expiry'), 3);
        $unix_expiry_time = mktime(0, 0, 0, $date_list[1], $date_list[2], $date_list[0]);
        $expiry_date = $unix_expiry_time;
    }

    $status = 'P';
    if ($request->get('page') == "admin_creation") {
        if ($request->get('form_restricted')) {
            $status = 'R';
        } else {
            $status = 'A';
        }
    }

    //use sys_lang as default language for each user at register
    $res = account_create(
        $request->get('form_loginname'),
        $password,
        '',
        $request->get('form_realname'),
        $request->get('form_register_purpose'),
        $request->get('form_email'),
        $status,
        $mail_confirm_code,
        $request->get('form_mail_site'),
        $request->get('form_mail_va'),
        $tz,
        UserManager::instance()->getCurrentUser()->getLocale(),
        'A',
        $expiry_date
    );

    return $res;
}

/**
 * Function to get errors with its key
 * to display errors for each element
**/
function getFieldError($field_key, array $errors)
{
    if (isset($errors[$field_key])) {
        return $errors[$field_key];
    }
    return null;
}


function display_account_form(bool $is_password_needed, $register_error, array $errors): void
{
    $request  = HTTPRequest::instance();

    $page = $request->get('page');
    if ($register_error) {
        print "<p><blink><b><span class=\"feedback\">$register_error</span></b></blink>";
    }
    $form_loginname         = $request->exist('form_loginname') ? $request->get('form_loginname') : '';
    $form_loginname_error   = getFieldError('form_loginname', $errors);

    $form_realname          = $request->exist('form_realname') ? $request->get('form_realname') : '';
    $form_realname_error    = getFieldError('form_realname', $errors);

    $form_email             = $request->exist('form_email') ? $request->get('form_email') : '';
    $form_email_error       = getFieldError('form_email', $errors);

    $form_pw                = '';
    $form_pw_error          = getFieldError('form_pw', $errors);

    $form_mail_site         = ! $request->exist('form_mail_site') || $request->get('form_mail_site') == 1;
    $form_mail_site_error   = getFieldError('form_mail_site', $errors);

    $form_restricted        = ForgeConfig::areRestrictedUsersAllowed() && (! $request->exist('form_restricted') || $request->get('form_restricted') == 1);
    $form_restricted_error  = getFieldError('form_restricted', $errors);

    $form_send_email        = $request->get('form_send_email') == 1;
    $form_send_email_error  = getFieldError('form_send_email', $errors);

    if ($request->exist('timezone') && is_valid_timezone($request->get('timezone'))) {
        $timezone = $request->get('timezone');
    } else {
        $timezone = false;
    }
    $timezone_error = getFieldError('timezone', $errors);

    $form_register_purpose          = $request->exist('form_register_purpose') ? $request->get('form_register_purpose') : '';
    $form_register_purpose_error    = getFieldError('form_register_purpose', $errors);

    $extra_plugin_field = '';
    EventManager::instance()->processEvent(
        Event::USER_REGISTER_ADDITIONAL_FIELD,
        [
            'request' => $request,
            'field'   => &$extra_plugin_field
        ]
    );

    if ($page == "admin_creation") {
        $prefill = new Account_RegisterAdminPrefillValuesPresenter(
            new Account_RegisterField($form_loginname, $form_loginname_error),
            new Account_RegisterField($form_email, $form_email_error),
            new Account_RegisterField($form_pw, $form_pw_error),
            new Account_RegisterField($form_realname, $form_realname_error),
            new Account_RegisterField($form_register_purpose, $form_register_purpose_error),
            new Account_RegisterField($form_mail_site, $form_mail_site_error),
            new Account_RegisterField($timezone, $timezone_error),
            new Account_RegisterField($form_restricted, $form_restricted_error),
            new Account_RegisterField($form_send_email, $form_send_email_error),
            $form_restricted
        );
        $presenter = new Account_RegisterByAdminPresenter($prefill, $extra_plugin_field);
        $template = 'register-admin';
    } else {
        $password_field = null;
        if ($is_password_needed) {
            $password_field = new Account_RegisterField($form_pw, $form_pw_error);
        }

        $prefill = new Account_RegisterPrefillValuesPresenter(
            new Account_RegisterField($form_loginname, $form_loginname_error),
            new Account_RegisterField($form_email, $form_email_error),
            $password_field,
            new Account_RegisterField($form_realname, $form_realname_error),
            new Account_RegisterField($form_register_purpose, $form_register_purpose_error),
            new Account_RegisterField($form_mail_site, $form_mail_site_error),
            new Account_RegisterField($timezone, $timezone_error)
        );
        $presenter = new Account_RegisterByUserPresenter($prefill, $extra_plugin_field);
        $template = 'register-user';
    }
    $renderer = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/account/');
    $renderer->renderToPage($template, $presenter);
}

$is_password_needed = true;
if ($page !== 'admin_creation') {
    $event_manager->processEvent(
        'before_register',
        [
            'request'                      => $request,
            'is_registration_confirmation' => $confirmation_register,
            'is_password_needed'           => &$is_password_needed,
        ]
    );
}

// ###### first check for valid login, if so, congratulate
$request = HTTPRequest::instance();
$hp = Codendi_HTMLPurifier::instance();
$errors = [];
if ($request->isPost() && $request->exist('Register')) {
    $is_registration_valid = true;
    EventManager::instance()->processEvent(
        Event::BEFORE_USER_REGISTRATION,
        [
            'request'               => $request,
            'is_registration_valid' => &$is_registration_valid
        ]
    );
    $page                        = $request->get('page');
    $displayed_image             = true;
    $image_url                   = '';
    $email_presenter             = '';
    $mail_confirm_code_generator = new MailConfirmationCodeGenerator(
        UserManager::instance(),
        new RandomNumberGenerator()
    );
    $mail_confirm_code           = $mail_confirm_code_generator->getConfirmationCode();
    $logo_retriever              = new LogoRetriever();
    if ($is_registration_valid && $new_userid = register_valid($is_password_needed, $mail_confirm_code, $errors)) {
        EventManager::instance()->processEvent(
            Event::AFTER_USER_REGISTRATION,
            [
                'request' => $request,
                'user_id' => $new_userid
            ]
        );

        $confirmation_register   = true;
        $user_name               = user_getname($new_userid);
        $content                 = '';
        $admin_creation          = false;

        if ($page == 'admin_creation') {
            $admin_creation = true;
            if ($request->get('form_send_email')) {
                //send an email to the user with th login and password
                $from      = ForgeConfig::get('sys_noreply');
                $is_sent = send_admin_new_user_email(
                    $request->get('form_email'),
                    $request->get('form_loginname')
                );

                if (! $is_sent) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::ERROR,
                        $GLOBALS['Language']->getText('global', 'mail_failed', [ForgeConfig::get('sys_email_admin')])
                    );
                }
            }
        }
        $thanks = $Language->getText('account_register', 'msg_thanks');
        $is_thanks = true;

        if (ForgeConfig::get('sys_user_approval') == 0 || $admin_creation) {
            if (! $admin_creation) {
                if (! send_new_user_email($request->get('form_email'), $user_name, $mail_confirm_code)) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::ERROR,
                        $GLOBALS['Language']->getText('global', 'mail_failed', [ForgeConfig::get('sys_email_admin')])
                    );
                }
                $presenter = new MailPresenterFactory();
                $email_presenter = $presenter->createMailAccountPresenter($user_name, $mail_confirm_code, "user", (string) $logo_retriever->getLegacyUrl());
            }

            $title  = $Language->getText('account_register', 'title_confirm');

            if ($admin_creation) {
                $title  = $Language->getText('account_register', 'title_confirm_admin');
                $content = $Language->getText(
                    'account_register',
                    'msg_confirm_admin',
                    [
                        $hp->purify($request->get('form_realname')),
                        ForgeConfig::get('sys_name'),
                        $hp->purify($request->get('form_loginname')),
                        $hp->purify($request->get('form_pw'))
                    ]
                );
                $thanks             = '';
                $is_thanks           = false;
                $redirect_url       = '/admin';
                $redirect_content   = $Language->getText('account_register', 'msg_redirect_admin');
                $displayed_image    = false;
            } else {
                $content            = $Language->getText('account_register', 'msg_confirm', [ForgeConfig::get('sys_name'), $user_name]);
                $redirect_url       = '/';
                $redirect_content   = $Language->getText('account_register', 'msg_redirect');
            }
        } else {
            // Registration requires approval
            // inform the user that approval is required
            $href_approval      = HTTPRequest::instance()->getServerUrl() . '/admin/approve_pending_users.php?page=pending';
            $title              = $Language->getText('account_register', 'title_approval');
            $content            = $Language->getText('account_register', 'msg_approval', [ForgeConfig::get('sys_name'), $user_name, $href_approval]);
            $redirect_url       = '/';
            $redirect_content   = $Language->getText('account_register', 'msg_redirect');
            $presenter          = new MailPresenterFactory();
            $email_presenter    = $presenter->createMailAccountPresenter($user_name, $mail_confirm_code, "user", (string) $logo_retriever->getLegacyUrl());
        }
        $presenter = new Account_ConfirmationPresenter(
            $title,
            $content,
            $thanks,
            $is_thanks,
            $redirect_url,
            $redirect_content,
            $displayed_image,
            $image_url,
            $email_presenter
        );
        $template = 'confirmation';
    }
}

$body_class = ['register-page'];
if ($page == 'admin_creation') {
    $body_class[] = 'admin_register';
}

// not valid registration, or first time to page
$HTML->includeJavascriptFile('/scripts/check_pw.js');
$HTML->includeFooterJavascriptFile('/scripts/jstimezonedetect/jstz.min.js');
$HTML->includeFooterJavascriptFile('/scripts/tuleap/timezone.js');
$HTML->header(['title' => $Language->getText('account_register', 'title'), 'body_class' => $body_class]);


if (! $confirmation_register || ! isset($presenter, $template)) {
    $reg_err = isset($GLOBALS['register_error']) ? $GLOBALS['register_error'] : '';
    display_account_form($is_password_needed, $reg_err, $errors);
} else {
    $renderer = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/account/');
    $renderer->renderToPage($template, $presenter);
}

$HTML->footer(['without_content' => true]);
