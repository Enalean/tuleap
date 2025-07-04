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
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

final class DisplayLostPasswordController implements DispatchableWithRequestNoAuthz, DispatchableWithBurningParrot
{
    public function __construct(
        private \TemplateRendererFactory $renderer_factory,
        private IncludeAssets $core_assets,
        private EventDispatcherInterface $event_manager,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $this->event_manager->dispatch(new BeforeLostPassword());

        $layout->addCssAsset(
            new CssAssetWithoutVariantDeclinaisons($this->core_assets, 'account-registration-style')
        );
        $layout->header(HeaderConfigurationBuilder::get(_('Password recovery'))->build());
        $this->renderer_factory
            ->getRenderer(__DIR__ . '/../../../../templates/account')
            ->renderToPage('lost-password', [
                'title' => _('Password recovery'),
                'error_message' => $variables['error_message'] ?? '',
            ]);
        $layout->footer(FooterConfiguration::withoutContent());
    }
}
