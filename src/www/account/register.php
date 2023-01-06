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

use Tuleap\InviteBuddy\AccountCreationFeedback;
use Tuleap\InviteBuddy\AccountCreationFeedbackEmailNotifier;
use Tuleap\InviteBuddy\InvitationDao;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\User\Account\Register\AccountRegister;
use Tuleap\User\Account\Register\RegisterFormPresenterBuilder;
use Tuleap\User\Account\Register\RegisterFormValidationIssue;
use Tuleap\User\Account\RegistrationGuardEvent;

header("Cache-Control: no-cache, no-store, must-revalidate");

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/proj_email.php';

$GLOBALS['HTML']->includeCalendarScripts();
$request = HTTPRequest::instance();
$page    = $request->get('page');

if ($page === "admin_creation") {
    $request->checkUserIsSuperUser();
}

$event_manager = EventManager::instance();

$layout = $GLOBALS['Response'];
$assets = new \Tuleap\Layout\IncludeCoreAssets();

$renderer_factory = TemplateRendererFactory::build();

$registration_guard = $event_manager->dispatch(new RegistrationGuardEvent());

if (! $request->getCurrentUser()->isSuperUser() && ! $registration_guard->isRegistrationPossible()) {
    exit_error(
        $GLOBALS['Language']->getText('include_session', 'insufficient_access'),
        $GLOBALS['Language']->getText('include_session', 'no_access')
    );
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

$request = HTTPRequest::instance();

$form_validation_issue = null;
if ($request->isPost() && $request->exist('Register')) {
    if (
        $event_manager
        ->dispatch(new \Tuleap\User\Account\Register\BeforeRegisterFormValidationEvent($request))
        ->isRegistrationValid()
    ) {
        $mail_confirm_code_generator = new MailConfirmationCodeGenerator(
            UserManager::instance(),
            new RandomNumberGenerator()
        );
        $mail_confirm_code           = $mail_confirm_code_generator->getConfirmationCode();

        $form_handler = new \Tuleap\User\Account\Register\RegisterFormHandler(
            new AccountRegister(
                UserManager::instance(),
                new AccountCreationFeedback(
                    new InvitationDao(),
                    UserManager::instance(),
                    new AccountCreationFeedbackEmailNotifier(),
                    BackendLogger::getDefaultLogger(),
                )
            ),
            new Account_TimezonesCollection(),
        );
        $form_handler->process($request, $is_password_needed, $mail_confirm_code)->match(
            function (PFUser $new_user) use ($event_manager, $request, $mail_confirm_code, $renderer_factory, $layout, $assets) {
                $new_userid = $new_user->getId();
                $event_manager->dispatch(
                    new \Tuleap\User\Account\Register\AfterUserRegistrationEvent($request, $new_user)
                );

                $admin_creation = false;

                if ($request->get('page') === 'admin_creation') {
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
                                $GLOBALS['Language']->getText(
                                    'global',
                                    'mail_failed',
                                    [ForgeConfig::get('sys_email_admin')]
                                )
                            );
                        }
                    }
                }

                if (
                    ForgeConfig::getInt(
                        User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL
                    ) === 0 || $admin_creation
                ) {
                    if (! $admin_creation) {
                        if (
                            ! send_new_user_email(
                                $request->get('form_email'),
                                $new_user->getUserName(),
                                $mail_confirm_code
                            )
                        ) {
                            $GLOBALS['Response']->addFeedback(
                                Feedback::ERROR,
                                $GLOBALS['Language']->getText(
                                    'global',
                                    'mail_failed',
                                    [ForgeConfig::get('sys_email_admin')]
                                )
                            );
                        }
                    }

                    $renderer = $renderer_factory->getRenderer(__DIR__ . "/../../templates/account/create/");

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
                    $renderer = $renderer_factory->getRenderer(__DIR__ . "/../../templates/account/create/");

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
            },
            function (?RegisterFormValidationIssue $issue) use (&$form_validation_issue) {
                $form_validation_issue = $issue;
            }
        );
    }
}

$builder = new RegisterFormPresenterBuilder(
    EventManager::instance(),
    $renderer_factory,
    new Account_TimezonesCollection(),
);
$render  = $builder->getPresenterClosure($request, $layout, $is_password_needed, $form_validation_issue);

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
