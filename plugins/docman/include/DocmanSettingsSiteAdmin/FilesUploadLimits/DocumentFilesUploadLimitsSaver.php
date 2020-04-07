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

use Tuleap\Layout\BaseLayout;

class DocumentFilesUploadLimitsSaver
{
    /**
     * @var \ConfigDao
     */
    private $config_dao;

    public function __construct(\ConfigDao $dao)
    {
        $this->config_dao = $dao;
    }

    public function saveNbMaxFiles(\HTTPRequest $request, BaseLayout $layout): void
    {
        $nb_files = (int) $request->getValidated('number-of-files-in-parallel', 'uint');
        if (! $nb_files) {
            $layout->addFeedback(
                \Feedback::ERROR,
                dgettext('tuleap-docman', 'An error occurred during the update of the number of files uploadable in parallel')
            );
            return;
        }

        $this->config_dao->save(PLUGIN_DOCMAN_MAX_NB_FILE_UPLOADS_SETTING, $nb_files);
    }

    public function saveMaxFileSize(\HTTPRequest $request, BaseLayout $layout): void
    {
        $max_file_size = (int) $request->getValidated('max-file-size', 'uint');
        if (! $max_file_size) {
            $layout->addFeedback(
                \Feedback::ERROR,
                dgettext('tuleap-docman', 'An error occurred during the update of the max file size')
            );
            return;
        }

        $this->config_dao->save(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, $max_file_size);
    }
}
