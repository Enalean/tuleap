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
use PasswordStrategy;
use PFUser;
use TemplateRendererFactory;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\NeverThrow\Fault;
use Tuleap\Password\Configuration\PasswordConfigurationRetriever;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

final class DisplayResetPasswordController implements DispatchableWithBurningParrot, DispatchableWithRequestNoAuthz
{
    public function __construct(
        private TemplateRendererFactory $renderer_factory,
        private IncludeAssets $core_assets,
        private UserFromConfirmationHashRetriever $user_retriever,
        private DisplayLostPasswordController $display_lost_password_controller,
        private LocaleSwitcher $locale_switcher,
        private PasswordConfigurationRetriever $password_configuration_retriever,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $confirm_hash = new ConcealedString(
            $request->get('confirm_hash') === false ? '' : $request->get('confirm_hash')
        );

        $this->user_retriever
            ->getUserFromConfirmationHash($confirm_hash)
            ->match(
                function (PFUser $user) use ($layout, $confirm_hash, $variables): void {
                    $this->locale_switcher
                        ->setLocaleForSpecificExecutionContext(
                            $user->getLocale(),
                            function () use ($layout, $confirm_hash, $user, $variables) {
                                $password_configuration = $this->password_configuration_retriever->getPasswordConfiguration();
                                $password_strategy      = new PasswordStrategy($password_configuration);
                                include($GLOBALS['Language']->getContent('account/password_strategy'));
                                $password_strategy_validators = [];
                                foreach ($password_strategy->validators as $key => $v) {
                                    $password_strategy_validators[] = [
                                        'key'         => $key,
                                        'description' => $v->description(),
                                    ];
                                }

                                $layout->addJavascriptAsset(
                                    new JavascriptAsset(
                                        new IncludeAssets(__DIR__ . '/../../../../scripts/account/frontend-assets', '/assets/core/account'),
                                        'check-pw.js'
                                    )
                                );
                                $layout->addJavascriptAsset(
                                    new JavascriptViteAsset(
                                        new IncludeViteAssets(
                                            __DIR__ . '/../../../../scripts/user-registration/frontend-assets',
                                            '/assets/core/user-registration'
                                        ),
                                        'src/index.ts'
                                    )
                                );
                                $layout->addCssAsset(
                                    new CssAssetWithoutVariantDeclinaisons(
                                        $this->core_assets,
                                        'account-registration-style'
                                    )
                                );

                                $errors = $variables['errors_pw'] ?? [];
                                $layout->header(HeaderConfigurationBuilder::get(_('Password recovery'))->build());
                                $this->renderer_factory
                                    ->getRenderer(__DIR__ . '/../../../../templates/account')
                                    ->renderToPage('reset-lost-password', [
                                        'title'                        => _('Password recovery'),
                                        'current_user_real_name'       => $user->getRealName(),
                                        'has_error'                    => count($errors) > 0,
                                        'errors'                       => $errors,
                                        'confirm_hash'                 => $confirm_hash,
                                        'json_password_strategy_keys'  => json_encode(array_keys($password_strategy->validators), JSON_THROW_ON_ERROR),
                                        'password_strategy_validators' => $password_strategy_validators,
                                    ]);
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
