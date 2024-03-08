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

namespace Tuleap\Test\Stubs\Project;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\ImportFromArchive;
use Tuleap\Project\XML\Import\ArchiveInterface;
use Tuleap\Project\XML\Import\ImportConfig;

final class ImportFromArchiveStub implements ImportFromArchive
{
    private ?int $captured_project_id = null;

    /**
     * @param Ok<true>|Err<Fault> $result
     */
    private function __construct(private readonly Ok|Err $result)
    {
    }

    public static function buildWithSuccessfulImport(): self
    {
        return new self(Result::ok(true));
    }

    public static function buildWithErrorDuringImport(string $message): self
    {
        return new self(Result::err(Fault::fromMessage($message)));
    }

    public function importFromArchive(ImportConfig $configuration, int $project_id, ArchiveInterface $archive): Ok|Err
    {
        $this->captured_project_id = $project_id;

        return $this->result;
    }

    public function getCapturedProjectId(): ?int
    {
        return $this->captured_project_id;
    }
}
