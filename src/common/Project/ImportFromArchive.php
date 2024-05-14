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

namespace Tuleap\Project;

use Closure;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\Project\Registration\Template\Upload\CheckArchiveContent;
use Tuleap\Project\XML\Import\ArchiveInterface;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\Import\ImportNotValidException;

interface ImportFromArchive
{
    /**
     * @psalm-param pure-Closure(\SimpleXMLElement): \Tuleap\Project\Event\ProjectXMLImportPreChecks $pre_check_xml_is_valid_event
     * @return Ok<true>|Err<Fault>
     * @throws ImportNotValidException
     */
    public function importFromArchive(
        ImportConfig $configuration,
        int $project_id,
        ArchiveInterface $archive,
        CheckArchiveContent $check_archive_content,
        Closure $pre_check_xml_is_valid_event,
    ): Ok|Err;
}
