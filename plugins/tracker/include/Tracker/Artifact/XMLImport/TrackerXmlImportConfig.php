<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Tracker\Artifact\XMLImport;

use DateTimeImmutable;
use ForgeConfig;
use PFUser;
use Project;

class TrackerXmlImportConfig implements TrackerImportConfig
{
    /**
     * @var string
     */
    private $file_system_folder;
    /**
     * @var string
     */
    private $file_name;
    /**
     * @var int
     */
    private $user_id;
    /**
     * @var int
     */
    private $import_timestamp;

    public function __construct(Project $project, PFUser $user, DateTimeImmutable $import_time)
    {
        $this->file_system_folder = ForgeConfig::get('sys_data_dir') . "/xml_import/";
        $this->file_name          = $project->getId() . '_tracker_import_' . $import_time->getTimestamp() . '.xml';
        $this->user_id            = (int) $user->getId();
        $this->import_timestamp   = $import_time->getTimestamp();
    }

    public function getXMLFileName(): string
    {
        return $this->file_name;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function getImportTimestamp(): int
    {
        return $this->import_timestamp;
    }

    public function isFromXml(): bool
    {
        return true;
    }

    public function getFileSystemFolder(): string
    {
        return $this->file_system_folder;
    }
}
