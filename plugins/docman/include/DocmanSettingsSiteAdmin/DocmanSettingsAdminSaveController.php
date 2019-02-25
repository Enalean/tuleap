<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Docman\DocmanSettingsSiteAdmin;

use HTTPRequest;
use Feedback;
use CSRFSynchronizerToken;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;

class DocmanSettingsAdminSaveController implements DispatchableWithRequest
{
    /**
     * @var DocumentSettingsSaver
     */
    private $docman_settings_saver;

    public function __construct(
        DocumentSettingsSaver $saver
    ) {
        $this->docman_settings_saver = $saver;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout $layout
     * @param array $variables
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables) : void
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
        $csrf_token->check();

        $this->saveAdminInformation($request, $layout);

        $layout->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-docman', 'Settings have been saved successfully')
        );

        $layout->redirect($request->getFromServer('REQUEST_URI'));
    }

    private function saveAdminInformation(\HTTPRequest $request, BaseLayout $layout) : void
    {
        $this->docman_settings_saver->saveNbMaxFiles($request, $layout);
        $this->docman_settings_saver->saveMaxFileSize($request, $layout);
    }
}
