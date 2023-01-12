<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\User\Account\Register;

use EventManager;
use Feedback;
use ForgeConfig;
use HTTPRequest;
use MailConfirmationCodeGenerator;
use PFUser;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use TuleapRegisterMail;
use User_UserStatusManager;

final class RegisterFormProcessor implements IProcessRegisterForm
{
    public function __construct(
        private RegisterFormHandler $form_handler,
        private MailConfirmationCodeGenerator $mail_confirmation_code_generator,
        private TemplateRendererFactory $renderer_factory,
        private TuleapRegisterMail $user_register_mail_builder,
        private TuleapRegisterMail $admin_register_mail_builder,
        private string $base_url,
        private IncludeAssets $assets,
        private EventManager $event_manager,
        private RegisterFormDisplayer $form_displayer,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, bool $is_admin, bool $is_password_needed): void
    {
        $mail_confirm_code = $this->mail_confirmation_code_generator->getConfirmationCode();

        $this->form_handler
            ->process($request, $is_password_needed, $is_admin, $mail_confirm_code)
            ->andThen(
                function (PFUser $new_user) use ($request): Ok|Err {
                    $this->event_manager->dispatch(
                        new AfterUserRegistrationEvent($request, $new_user)
                    );

                    return Result::ok($new_user);
                }
            )
            ->match(
                function (PFUser $new_user) use ($request, $mail_confirm_code, $layout, $is_admin) {
                    if ($is_admin) {
                        if ($request->get('form_send_email')) {
                            $is_sent = $this->sendLoginAndPasswordByMailToUser(
                                (string) $request->get('form_email'),
                                (string) $request->get('form_loginname')
                            );

                            if (! $is_sent) {
                                $layout->addFeedback(
                                    Feedback::ERROR,
                                    $GLOBALS['Language']->getText(
                                        'global',
                                        'mail_failed',
                                        [ForgeConfig::get('sys_email_admin')]
                                    )
                                );
                            }
                        }

                        $this->displayConfirmationForAdmin($layout, $request);
                        return;
                    }

                    if (ForgeConfig::getInt(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL)) {
                        $this->displayWaitForApproval($layout, $request);
                        return;
                    }

                    if (
                        ! $this->sendNewUserEmail(
                            (string) $request->get('form_email'),
                            $new_user->getUserName(),
                            $mail_confirm_code
                        )
                    ) {
                        $layout->addFeedback(
                            Feedback::ERROR,
                            $GLOBALS['Language']->getText(
                                'global',
                                'mail_failed',
                                [ForgeConfig::get('sys_email_admin')]
                            )
                        );
                    }

                    $this->displayConfirmationLinkSent($layout, $request);
                },
                function (?RegisterFormValidationIssue $issue) use ($request, $layout, $is_admin, $is_password_needed) {
                    $this->form_displayer->displayWithPossibleIssue($request, $layout, $is_admin, $is_password_needed, $issue);
                }
            );
    }

    private function sendLoginAndPasswordByMailToUser(string $to, string $login): bool
    {
        return $this->admin_register_mail_builder
            ->getMail(
                $login,
                '',
                $this->base_url,
                ForgeConfig::get('sys_noreply'),
                $to,
                "admin"
            )
            ->send();
    }

    private function sendNewUserEmail(string $to, string $login, string $confirm_hash): bool
    {
        return $this->user_register_mail_builder
            ->getMail(
                $login,
                $confirm_hash,
                $this->base_url,
                ForgeConfig::get('sys_noreply'),
                $to,
                "user"
            )
            ->send();
    }

    private function displayConfirmationForAdmin(BaseLayout $layout, HTTPRequest $request): void
    {
        $renderer = $this->renderer_factory->getRenderer(__DIR__ . "/../../../../templates/account/create/");

        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->assets, 'account-registration-style'));
        $layout->header(
            HeaderConfigurationBuilder::get(_('Register'))->build()
        );
        $renderer->renderToPage("confirmation-admin-creation", [
            'login'    => $request->get('form_loginname'),
            'password' => $request->get('form_pw'),
        ]);
        $layout->footer(FooterConfiguration::withoutContent());
    }

    private function displayConfirmationLinkSent(BaseLayout $layout, HTTPRequest $request): void
    {
        $renderer = $this->renderer_factory->getRenderer(__DIR__ . "/../../../../templates/account/create/");

        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->assets, 'account-registration-style'));
        $layout->header(
            HeaderConfigurationBuilder::get(_('Register'))->build()
        );
        $renderer->renderToPage("confirmation-link-sent", [
            'email' => $request->get('form_email'),
        ]);
        $layout->footer(FooterConfiguration::withoutContent());
    }

    private function displayWaitForApproval(BaseLayout $layout, HTTPRequest $request): void
    {
        $renderer = $this->renderer_factory->getRenderer(__DIR__ . "/../../../../templates/account/create/");

        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->assets, 'account-registration-style'));
        $layout->header(
            HeaderConfigurationBuilder::get(_('Register'))->build()
        );
        $renderer->renderToPage("waiting-for-approval", [
            'email' => $request->get('form_email'),
        ]);
        $layout->footer(FooterConfiguration::withoutContent());
    }
}
