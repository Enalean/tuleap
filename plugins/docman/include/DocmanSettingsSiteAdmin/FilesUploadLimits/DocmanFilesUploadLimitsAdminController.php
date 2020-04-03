<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\DocmanSettingsSiteAdmin\FilesUploadLimits;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

class DocmanFilesUploadLimitsAdminController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;

    public function __construct(
        AdminPageRenderer $admin_page_renderer
    ) {
        $this->admin_page_renderer = $admin_page_renderer;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-docman', 'You should be site administrator to access this page')
            );
            $layout->redirect('/');
            return;
        }

        $csrf_token = new CSRFSynchronizerToken($request->getFromServer('REQUEST_URI'));

        $this->admin_page_renderer->renderANoFramedPresenter(
            dgettext('tuleap-docman', 'Document settings'),
            __DIR__ . '/../../../templates',
            'document-settings',
            new DocmanFilesUploadLimitsAdminPresenter(
                $csrf_token,
                (int) \ForgeConfig::get(PLUGIN_DOCMAN_MAX_NB_FILE_UPLOADS_SETTING),
                (int) \ForgeConfig::get(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING)
            )
        );
    }
}
