<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\PdfTemplate\Admin;

use HTTPRequest;
use PFUser;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

final readonly class IndexPdfTemplateController implements DispatchableWithBurningParrot, DispatchableWithRequest
{
    public const ROUTE = '/pdftemplate/admin';

    public function __construct(private AdminPageRenderer $admin_page_renderer)
    {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $current_user = $request->getCurrentUser();
        $this->checkUserCanManageTemplates($current_user);

        $layout->addJavascriptAsset(
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../scripts/admin/frontend-assets',
                    '/assets/pdftemplate/admin'
                ),
                'src/index.ts'
            )
        );

        $this->admin_page_renderer->renderAPresenter(
            dgettext('tuleap-pdftemplate', 'PDF Template'),
            __DIR__,
            'index',
            [],
        );
    }

    private function checkUserCanManageTemplates(PFUser $user): void
    {
        if (! $user->isSuperUser()) {
            throw new NotFoundException();
        }
    }
}
