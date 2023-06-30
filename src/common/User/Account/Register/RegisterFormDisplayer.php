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

use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\IncludeCoreAssets;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Layout\JavascriptViteAsset;

final class RegisterFormDisplayer implements IDisplayRegisterForm
{
    public function __construct(
        private RegisterFormPresenterBuilder $presenter_builder,
        private IncludeCoreAssets $core_assets,
    ) {
    }

    public function display(
        \HTTPRequest $request,
        BaseLayout $layout,
        RegisterFormContext $context,
    ): void {
        $this->render(
            $layout,
            $this->presenter_builder->getPresenterClosureForFirstDisplay($request, $layout, $context)
        );
    }

    public function displayWithPossibleIssue(
        \HTTPRequest $request,
        BaseLayout $layout,
        RegisterFormContext $context,
        ?RegisterFormValidationIssue $issue,
    ): void {
        $this->render(
            $layout,
            $this->presenter_builder->getPresenterClosure($request, $layout, $context, $issue)
        );
    }

    /**
     * @param \Closure(): void $render
     */
    private function render(BaseLayout $layout, \Closure $render): void
    {
        $layout->addJavascriptAsset(
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../../scripts/user-registration/frontend-assets',
                    '/assets/core/user-registration'
                ),
                'src/index.ts'
            )
        );

        $layout->addJavascriptAsset(
            new JavascriptAsset(
                new IncludeAssets(__DIR__ . '/../../../../scripts/account/frontend-assets', '/assets/core/account'),
                'check-pw.js'
            )
        );

        $layout->addCssAsset(
            new CssAssetWithoutVariantDeclinaisons($this->core_assets, 'account-registration-style')
        );
        $layout->header(
            HeaderConfigurationBuilder::get(_('Register'))->build()
        );
        $render();
        $layout->footer(FooterConfiguration::withoutContent());
    }
}
