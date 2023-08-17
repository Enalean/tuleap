<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Platform\Banner;

use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

final class PlatformBannerAdministrationController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public function __construct(
        private readonly AdminPageRenderer $admin_page_renderer,
        private readonly JavascriptAssetGeneric $ckeditor_assets,
        private readonly JavascriptAssetGeneric $banner_assets,
        private readonly BannerRetriever $banner_retriever,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $layout->addJavascriptAsset($this->ckeditor_assets);
        $layout->addJavascriptAsset($this->banner_assets);

        $banner = $this->banner_retriever->getBanner();

        $banner_expiration_date = '';
        if ($banner !== null && $banner->getExpirationDate() !== null) {
            $banner_expiration_date = $banner->getExpirationDate()->format('c');
        }

        $this->admin_page_renderer->renderAPresenter(
            _('Platform banner'),
            __DIR__ . '/../../../templates/admin/banner/',
            'administration',
            [
                'message'    => $banner === null ? '' : $banner->getMessage(),
                'importance' => $banner === null ? '' : $banner->getImportance(),
                'expiration_date' => $banner_expiration_date,
            ]
        );
    }
}
