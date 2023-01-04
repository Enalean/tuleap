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
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\User\Account\Register\RegisterFormPresenterBuilder;
use Tuleap\User\Account\RegistrationGuardEvent;

header("Cache-Control: no-cache, no-store, must-revalidate");

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/proj_email.php';
require_once __DIR__ . '/../include/account.php';
require_once __DIR__ . '/../include/timezones.php';

$GLOBALS['HTML']->includeCalendarScripts();
$request = HTTPRequest::instance();
$page    = $request->get('page');

// ###### function register_valid()
// ###### checks for valid register from form post
if ($page == "admin_creation") {
    $request->checkUserIsSuperUser();
}

$event_manager = EventManager::instance();

$registration_guard = $event_manager->dispatch(new RegistrationGuardEvent());

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
        $GLOBALS['Response']->addFeedback('error', _('Real name contains illegal characters.'));
        $errors['form_realname'] = _('Real name contains illegal characters.');
        return 0;
    }

    if ($is_password_needed && ! $request->existAndNonEmpty('form_pw')) {
        $GLOBALS['Response']->addFeedback('error', _('You must supply a password.'));
        $errors['form_pw'] = _('You must supply a password.');
        return 0;
    }
    $tz = $request->get('timezone');
    if (! is_valid_timezone($tz)) {
        $GLOBALS['Response']->addFeedback('error', _('You must supply a timezone.'));
        $errors['timezone'] = _('You must supply a timezone.');
        return 0;
    }
    if (! $request->existAndNonEmpty('form_register_purpose') && (ForgeConfig::getInt(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL) === 1 && $request->get('page') != "admin_creation")) {
        $GLOBALS['Response']->addFeedback('error', _('You must explain the purpose of your registration.'));
        $errors['form_register_purpose'] = _('You must explain the purpose of your registration.');
        return 0;
    }
    if (! validate_email($request->get('form_email'))) {
        $GLOBALS['Response']->addFeedback('error', _('Invalid Email Address'));
        $errors['form_email'] = _('Invalid Email Address');
        return 0;
    }

    $password = null;
    if ($is_password_needed) {
        $password              = new ConcealedString((string) $request->get('form_pw'));
        $password_confirmation = new ConcealedString((string) $request->get('form_pw2'));
        if ($request->get('page') !== "admin_creation" && ! $password->isIdenticalTo($password_confirmation)) {
            $GLOBALS['Response']->addFeedback('error', _('Passwords do not match.'));
            $errors['form_pw'] = _('Passwords do not match.');
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
        $GLOBALS['Response']->addFeedback('error', _('       Sorry - Expiration Date entry could not be parsed. It must be in YYYY-MM-DD format.'));
        $errors['form_expiry'] = _('       Sorry - Expiration Date entry could not be parsed. It must be in YYYY-MM-DD format.');
        return 0;
    }
    $vDate = new Valid_String();
    $vDate->required();
    if ($request->exist('form_expiry') && $vDate->validate($request->get('form_expiry'))) {
        $date_list        = preg_split("/-/D", $request->get('form_expiry'), 3);
        $unix_expiry_time = mktime(0, 0, 0, $date_list[1], $date_list[2], $date_list[0]);
        $expiry_date      = $unix_expiry_time;
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

$is_password_needed = true;
if ($page !== 'admin_creation') {
    $event_manager->processEvent(
        'before_register',
        [
            'request'                      => $request,
            'is_registration_confirmation' => false,
            'is_password_needed'           => &$is_password_needed,
        ]
    );
}

// ###### first check for valid login, if so, congratulate
$request = HTTPRequest::instance();
$hp      = Codendi_HTMLPurifier::instance();
$errors  = [];
if ($request->isPost() && $request->exist('Register')) {
    $is_registration_valid = true;
    EventManager::instance()->processEvent(
        Event::BEFORE_USER_REGISTRATION,
        [
            'request'               => $request,
            'is_registration_valid' => &$is_registration_valid,
        ]
    );
    $page                        = $request->get('page');
    $mail_confirm_code_generator = new MailConfirmationCodeGenerator(
        UserManager::instance(),
        new RandomNumberGenerator()
    );
    $mail_confirm_code           = $mail_confirm_code_generator->getConfirmationCode();
    if ($is_registration_valid && $new_userid = register_valid($is_password_needed, $mail_confirm_code, $errors)) {
        EventManager::instance()->processEvent(
            Event::AFTER_USER_REGISTRATION,
            [
                'request' => $request,
                'user_id' => $new_userid,
            ]
        );

        $user_name      = user_getname($new_userid);
        $admin_creation = false;

        if ($page == 'admin_creation') {
            $admin_creation = true;
            if ($request->get('form_send_email')) {
                //send an email to the user with th login and password
                $from    = ForgeConfig::get('sys_noreply');
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

        if (ForgeConfig::getInt(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL) === 0 || $admin_creation) {
            if (! $admin_creation) {
                if (! send_new_user_email($request->get('form_email'), $user_name, $mail_confirm_code)) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::ERROR,
                        $GLOBALS['Language']->getText('global', 'mail_failed', [ForgeConfig::get('sys_email_admin')])
                    );
                }
            }

            $theme_manager    = new ThemeManager(
                new \Tuleap\BurningParrotCompatiblePageDetector(
                    new \Tuleap\Request\CurrentPage(),
                    new User_ForgeUserGroupPermissionsManager(
                        new User_ForgeUserGroupPermissionsDao()
                    )
                )
            );
            $user_manager     = UserManager::instance();
            $renderer_factory = TemplateRendererFactory::build();
            $assets           = new \Tuleap\Layout\IncludeCoreAssets();

            $renderer = $renderer_factory->getRenderer(__DIR__ . "/../../templates/account/create/");

            $layout = $theme_manager->getBurningParrot($user_manager->getCurrentUserWithLoggedInInformation());
            if ($layout === null) {
                throw new \Exception("Could not load BurningParrot theme");
            }
            $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($assets, 'account-registration-style'));
            $layout->header(
                HeaderConfigurationBuilder::get(_('Register'))->build()
            );
            if ($admin_creation) {
                $renderer->renderToPage("confirmation-admin-creation", [
                    'login'    => $request->get('form_loginname'),
                    'password' => $request->get('form_pw'),
                ]);
            } else {
                $renderer->renderToPage("confirmation-link-sent", [
                    'email' => $request->get('form_email'),
                ]);
            }
            $layout->footer(FooterConfiguration::withoutContent());
            exit;
        } else {
            // Registration requires approval
            // inform the user that approval is required
            $theme_manager    = new ThemeManager(
                new \Tuleap\BurningParrotCompatiblePageDetector(
                    new \Tuleap\Request\CurrentPage(),
                    new User_ForgeUserGroupPermissionsManager(
                        new User_ForgeUserGroupPermissionsDao()
                    )
                )
            );
            $user_manager     = UserManager::instance();
            $renderer_factory = TemplateRendererFactory::build();
            $assets           = new \Tuleap\Layout\IncludeCoreAssets();

            $renderer = $renderer_factory->getRenderer(__DIR__ . "/../../templates/account/create/");

            $layout = $theme_manager->getBurningParrot($user_manager->getCurrentUserWithLoggedInInformation());
            if ($layout === null) {
                throw new \Exception("Could not load BurningParrot theme");
            }
            $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($assets, 'account-registration-style'));
            $layout->header(
                HeaderConfigurationBuilder::get(_('Register'))->build()
            );
            $renderer->renderToPage("waiting-for-approval", [
                'email' => $request->get('form_email'),
            ]);
            $layout->footer(FooterConfiguration::withoutContent());
            exit;
        }
    }
}


$theme_manager    = new ThemeManager(
    new \Tuleap\BurningParrotCompatiblePageDetector(
        new \Tuleap\Request\CurrentPage(),
        new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        )
    )
);
$user_manager     = UserManager::instance();
$renderer_factory = TemplateRendererFactory::build();
$assets           = new \Tuleap\Layout\IncludeCoreAssets();

$layout = $theme_manager->getBurningParrot($user_manager->getCurrentUserWithLoggedInInformation());
if ($layout === null) {
    throw new \Exception("Could not load BurningParrot theme");
}

$builder = new RegisterFormPresenterBuilder(
    EventManager::instance(),
    TemplateRendererFactory::build(),
    new Account_TimezonesCollection(),
);
$render  = $builder->getPresenterClosure($request, $layout, $is_password_needed, $errors);

$layout->addJavascriptAsset(new JavascriptViteAsset(
    new IncludeViteAssets(
        __DIR__ . '/../../scripts/user-registration/frontend-assets',
        '/assets/core/user-registration'
    ),
    'src/index.ts'
));

$layout->addJavascriptAsset(
    new \Tuleap\Layout\JavascriptAsset(
        $assets,
        'account/check-pw.js'
    )
);

$layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($assets, 'account-registration-style'));
$layout->header(
    HeaderConfigurationBuilder::get(_('Register'))->build()
);
$render();
$layout->footer(FooterConfiguration::withoutContent());
