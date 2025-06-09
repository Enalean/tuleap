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

use HTTPRequest;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\NeverThrow\Fault;
use Tuleap\Password\PasswordSanityChecker;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\User\Password\Change\PasswordChanger;

final class ResetPasswordController implements DispatchableWithRequestNoAuthz, DispatchableWithBurningParrot
{
    public function __construct(
        private UserFromConfirmationHashRetriever $user_retriever,
        private DisplayResetPasswordController $display_reset_password_controller,
        private DisplayLostPasswordController $display_lost_password_controller,
        private LocaleSwitcher $locale_switcher,
        private PasswordChanger $password_changer,
        private \TemplateRendererFactory $renderer_factory,
        private IncludeAssets $core_assets,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $confirm_hash = new ConcealedString(
            $request->valid(new \Valid_String('confirm_hash')) === false ? '' : $request->get('confirm_hash')
        );

        $this->user_retriever
            ->getUserFromConfirmationHash($confirm_hash)
            ->match(
                function (\PFUser $user) use ($request, $layout, $variables, $confirm_hash): void {
                    $this->locale_switcher
                        ->setLocaleForSpecificExecutionContext(
                            $user->getLocale(),
                            function () use ($request, $layout, $variables, $user) {
                                $password              = new ConcealedString((string) $request->get('form_pw'));
                                $password_confirmation = new ConcealedString((string) $request->get('form_pw2'));
                                if (! $password->isIdenticalTo($password_confirmation)) {
                                    $this->display_reset_password_controller->process($request, $layout, ['errors_pw' => [_('Passwords do not match.')], ...$variables]);
                                    return;
                                }

                                $password_sanity_checker = PasswordSanityChecker::build();
                                if (! $password_sanity_checker->check($password)) {
                                    $this->display_reset_password_controller->process($request, $layout, ['errors_pw' => $password_sanity_checker->getErrors(), ...$variables]);
                                    return;
                                }

                                $this->password_changer->changePassword($user, $password);

                                $layout->addCssAsset(
                                    new CssAssetWithoutVariantDeclinaisons($this->core_assets, 'account-registration-style')
                                );
                                $layout->header(HeaderConfigurationBuilder::get(_('Password recovery'))->build());
                                $this->renderer_factory
                                    ->getRenderer(__DIR__ . '/../../../../templates/account')
                                    ->renderToPage('reset-lost-password-success', []);
                                $layout->footer(FooterConfiguration::withoutContent());
                            }
                        );
                },
                function (Fault $fault) use ($request, $layout, $variables): void {
                    $this->display_lost_password_controller
                        ->process($request, $layout, ['error_message' => (string) $fault, ...$variables]);
                }
            );
    }
}
