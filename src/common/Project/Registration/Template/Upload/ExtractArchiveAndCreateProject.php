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

namespace Tuleap\Project\Registration\Template\Upload;

use Psr\Log\LoggerInterface;
use Tuleap\NeverThrow\Fault;
use Tuleap\Project\ImportFromArchive;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\Import\ZipArchive;

final readonly class ExtractArchiveAndCreateProject implements FinishFileUploadPostAction
{
    public function __construct(
        private ImportFromArchive $importer,
        private LoggerInterface $logger,
    ) {
    }

    public function process(int $project_id, string $filename): void
    {
        $this->importer->importFromArchive(
            new ImportConfig(),
            $project_id,
            new ZipArchive($filename, \ForgeConfig::get('tmp_dir')),
        )->match(
            function () use ($project_id): void {
                $this->logger->info("Successfully imported archive into project #{$project_id}");
            },
            function (Fault $fault) use ($project_id): void {
                $this->logger->error("Unable to import archive into project #{$project_id}");
                Fault::writeToLogger($fault, $this->logger);
            }
        );
        unlink($filename);
    }
}
