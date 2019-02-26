<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\FRS;

use FRSFileFactory;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class FileDownloadController implements DispatchableWithRequest
{

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout  $layout
     * @param array       $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $group_id = $variables['group_id'];
        $file_id  = $variables['file_id'];

        $frsff = new FRSFileFactory();
        $file  = $frsff->getFRSFileFromDb($file_id, $group_id);

        if (! $file) {
            exit_error($GLOBALS['Language']->getText('file_download', 'incorrect_release_id'), $GLOBALS['Language']->getText('file_download', 'report_error', $GLOBALS['sys_name']));
        }

        // Check permissions for downloading the file, and check that the file has the active status
        if (! $file->userCanDownload() || ! $file->isActive()) {
            exit_error($GLOBALS['Language']->getText('file_download', 'access_denied'), $GLOBALS['Language']->getText('file_download', 'access_not_authorized', session_make_url("/project/memberlist.php?group_id=$group_id")));
        }

        if (! $file->fileExists()) {
            exit_error($GLOBALS['Language']->getText('global', 'error'), $GLOBALS['Language']->getText('file_download', 'file_not_available'));
        }

        // Log the download in the Log system
        $file->LogDownload($request->getCurrentUser()->getId());

        // Start download
        $file->download();
    }
}
