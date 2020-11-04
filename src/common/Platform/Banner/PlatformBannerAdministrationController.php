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
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

final class PlatformBannerAdministrationController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;
    /**
     * @var IncludeAssets
     */
    private $banner_assets;
    /**
     * @var BannerRetriever
     */
    private $banner_retriever;

    public function __construct(
        AdminPageRenderer $admin_page_renderer,
        IncludeAssets $banner_assets,
        BannerRetriever $banner_retriever
    ) {
        $this->admin_page_renderer = $admin_page_renderer;
        $this->banner_assets       = $banner_assets;
        $this->banner_retriever    = $banner_retriever;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $layout->includeFooterJavascriptFile($this->banner_assets->getFileURL('ckeditor.js'));
        $layout->includeFooterJavascriptFile($this->banner_assets->getFileURL('site-admin/platform-banner.js'));

        $banner = $this->banner_retriever->getBanner();

        $this->admin_page_renderer->renderAPresenter(
            _('Platform banner'),
            __DIR__ . '/../../../templates/admin/banner/',
            'administration',
            [
                'message'    => $banner === null ? '' : $banner->getMessage(),
                'importance' => $banner === null ? '' : $banner->getImportance(),
            ]
        );
    }
}
