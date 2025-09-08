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

namespace Tuleap\User\Account\LostPassword;

use ForgeConfig;
use HTTPRequest;
use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRendererFactory;
use Tuleap_Template_Mail;
use Tuleap\Config\ConfigurationVariables;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Mail\MailFactory;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\ServerHostname;
use Tuleap\User\Password\Reset\Creator;
use Tuleap\User\Password\Reset\RecentlyCreatedCodeException;
use Tuleap\User\Password\Reset\ResetTokenSerializer;
use Tuleap\User\RetrieveUserByUserName;

final class LostPasswordController implements DispatchableWithRequestNoAuthz, DispatchableWithBurningParrot
{
    public function __construct(
        private RetrieveUserByUserName $retrieve_user_by_username,
        private Creator $password_reset_token_creator,
        private ResetTokenSerializer $reset_token_formatter,
        private LocaleSwitcher $locale_switcher,
        private TemplateRendererFactory $renderer_factory,
        private EventDispatcherInterface $event_manager,
        private IncludeAssets $core_assets,
        private DisplayLostPasswordController $display_controller,
        private LoggerInterface $logger,
        private Tuleap_Template_Mail $template_mail,
        private MailFactory $mail_factory,
    ) {
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $this->event_manager->dispatch(new BeforeLostPasswordConfirm());

        $user     = null;
        $username = (string) $request->get('form_loginname');
        if ($username !== '') {
            $user = $this->retrieve_user_by_username->getUserByUserName($username);
        }

        if ($user === null || $user->getUserPw() === null) {
            $this->displayConfirmation($layout);
            return;
        }

        $this->locale_switcher
            ->setLocaleForSpecificExecutionContext(
                $user->getLocale(),
                function () use ($user): Ok|Err {
                    try {
                        $reset_token = $this->password_reset_token_creator->create($user);
                    } catch (RecentlyCreatedCodeException) {
                        $this->logger->info(sprintf('Reset code for user #%d was recently requested, not sending one again', $user->getId()));
                        return Result::ok(true);
                    }

                    $identifier = $this->reset_token_formatter->getIdentifier($reset_token);

                    $mail = $this->mail_factory->getMail();
                    $mail->setLookAndFeelTemplate($this->template_mail);
                    $mail->setFrom(ForgeConfig::get('sys_noreply'));
                    $mail->setTo($user->getEmail());
                    $mail->setSubject(
                        sprintf(
                            _('%1$s Password Verification'),
                            ForgeConfig::get(ConfigurationVariables::NAME)
                        )
                    );

                    $renderer = $this->renderer_factory->getRenderer(__DIR__ . '/../../../../templates/account/lostpw');

                    $presenter = [
                        'current_user_real_name' => $user->getRealName(),
                        'instance_name'          => ForgeConfig::get(ConfigurationVariables::NAME),
                        'reset_link'             => ServerHostname::HTTPSUrl(
                        ) . '/account/lostlogin.php?confirm_hash=' . urlencode($identifier->getString()),
                    ];

                    $mail->setBodyHtml($renderer->renderToString('lostpw-mail', $presenter));
                    $mail->setBodyText($renderer->renderToString('lostpw-mail-text', $presenter));

                    if (! $mail->send()) {
                        return Result::err(Fault::fromMessage('An error occurred while sending the email'));
                    }

                    return Result::ok(true);
                }
            )->match(
                function () use ($layout): void {
                    $this->displayConfirmation($layout);
                },
                function (Fault $fault) use ($request, $layout, $variables) {
                    Fault::writeToLogger($fault, $this->logger);
                    $this->redisplayFormWithError($request, $layout, $variables, (string) $fault);
                }
            );
    }

    private function redisplayFormWithError(
        HTTPRequest $request,
        BaseLayout $layout,
        array $variables,
        string $error_message,
    ): void {
        $this->display_controller->process($request, $layout, [...$variables, 'error_message' => $error_message]);
    }

    private function displayConfirmation(BaseLayout $layout): void
    {
        $layout->addCssAsset(
            new CssAssetWithoutVariantDeclinaisons($this->core_assets, 'account-registration-style')
        );
        $layout->header(HeaderConfigurationBuilder::get(_('Password recovery'))->build());
        $this->renderer_factory
            ->getRenderer(__DIR__ . '/../../../../templates/account')
            ->renderToPage('lost-password-confirmation', [
                'title' => _('Password recovery'),
            ]);
        $layout->footer(FooterConfiguration::withoutContent());
    }
}
