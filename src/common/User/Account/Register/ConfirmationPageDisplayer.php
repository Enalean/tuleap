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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeAssets;

final class ConfirmationPageDisplayer implements IDisplayConfirmationPage
{
    public function __construct(
        private \TemplateRendererFactory $renderer_factory,
        private IncludeAssets $assets,
    ) {
    }

    #[\Override]
    public function displayConfirmationForAdmin(BaseLayout $layout, \PFUser $new_user, ConcealedString $password): void
    {
        $renderer = $this->renderer_factory->getRenderer(__DIR__ . '/../../../../templates/account/create/');

        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->assets, 'account-registration-style'));
        $layout->header(
            HeaderConfigurationBuilder::get(_('Register'))->build()
        );
        $renderer->renderToPage('confirmation-admin-creation', [
            'login'    => $new_user->getUserName(),
            'password' => $password->getString(),
        ]);
        $layout->footer(FooterConfiguration::withoutContent());
    }

    #[\Override]
    public function displayConfirmationLinkSent(BaseLayout $layout, \PFUser $new_user): void
    {
        $renderer = $this->renderer_factory->getRenderer(__DIR__ . '/../../../../templates/account/create/');

        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->assets, 'account-registration-style'));
        $layout->header(
            HeaderConfigurationBuilder::get(_('Register'))->build()
        );
        $renderer->renderToPage('confirmation-link-sent', [
            'email' => $new_user->getEmail(),
        ]);
        $layout->footer(FooterConfiguration::withoutContent());
    }

    #[\Override]
    public function displayWaitForApproval(BaseLayout $layout, \PFUser $new_user): void
    {
        $renderer = $this->renderer_factory->getRenderer(__DIR__ . '/../../../../templates/account/create/');

        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->assets, 'account-registration-style'));
        $layout->header(
            HeaderConfigurationBuilder::get(_('Register'))->build()
        );
        $renderer->renderToPage('waiting-for-approval', [
            'email' => $new_user->getEmail(),
        ]);
        $layout->footer(FooterConfiguration::withoutContent());
    }

    #[\Override]
    public function displayConfirmationLinkError(BaseLayout $layout): void
    {
        $renderer = $this->renderer_factory->getRenderer(__DIR__ . '/../../../../templates/account/create/');

        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($this->assets, 'account-registration-style'));
        $layout->header(
            HeaderConfigurationBuilder::get(_('Register'))->build()
        );
        $renderer->renderToPage('confirmation-link-error', [
            'email_admin' => \ForgeConfig::get('sys_email_admin'),
        ]);
        $layout->footer(FooterConfiguration::withoutContent());
    }
}
