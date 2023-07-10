<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\date\Admin;

use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\date\DefaultRelativeDatesDisplayPreferenceRetriever;
use Tuleap\date\RelativeDatesAssetsRetriever;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class RelativeDatesDisplayController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const URL = '/admin/dates-display';

    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;

    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(
        AdminPageRenderer $admin_page_renderer,
        \CSRFSynchronizerToken $csrf_token,
    ) {
        $this->admin_page_renderer = $admin_page_renderer;
        $this->csrf_token          = $csrf_token;
    }

    /**
     * @throws ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $this->admin_page_renderer->addJavascriptAsset(
            RelativeDatesAssetsRetriever::getAsJavascriptAssets()
        );

        $this->admin_page_renderer->addJavascriptAsset(
            new JavascriptAsset(
                new IncludeAssets(__DIR__ . '/../../../scripts/site-admin/frontend-assets', '/assets/core/site-admin'),
                'site-admin-dates-display.js'
            )
        );

        $this->admin_page_renderer->renderANoFramedPresenter(
            _('Relative dates display'),
            __DIR__ . '/../../../templates/admin/date',
            'dates-display',
            new RelativeDatesDisplayAdminPresenter(
                $request->getCurrentUser(),
                $this->csrf_token,
                DefaultRelativeDatesDisplayPreferenceRetriever::retrieveDefaultValue()
            )
        );
    }

    public static function buildCSRFToken(): \CSRFSynchronizerToken
    {
        return new \CSRFSynchronizerToken(self::URL);
    }
}
