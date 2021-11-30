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

use Tuleap\Docman\DocmanSettingsSiteAdmin\DocmanSettingsTabPresenter;
use Tuleap\Docman\DocmanSettingsSiteAdmin\DocmanSettingsTabsPresenterCollection;
use Tuleap\Docman\DocmanSettingsSiteAdmin\FileUploadTabPresenter;

class DocmanFilesUploadLimitsAdminPresenter
{
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var int
     */
    public $max_size_of_file;
    /**
     * @var int
     */
    public $max_number_of_files;
    /**
     * @var DocmanSettingsTabPresenter[]
     */
    public $tabs;

    public function __construct(
        \CSRFSynchronizerToken $csrf_token,
        int $max_number_of_files,
        int $max_size_of_file,
        DocmanSettingsTabsPresenterCollection $tabs_collection,
    ) {
        $this->csrf_token          = $csrf_token;
        $this->max_size_of_file    = $max_size_of_file;
        $this->max_number_of_files = $max_number_of_files;
        $this->tabs                = $tabs_collection->getTabs(FileUploadTabPresenter::URL);
    }
}
