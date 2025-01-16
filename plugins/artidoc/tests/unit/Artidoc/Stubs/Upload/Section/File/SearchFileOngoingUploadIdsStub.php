<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Stubs\Upload\Section\File;

use Tuleap\Artidoc\Upload\Section\File\SearchFileOngoingUploadIds;
use Tuleap\Tus\Identifier\FileIdentifier;

final readonly class SearchFileOngoingUploadIdsStub implements SearchFileOngoingUploadIds
{
    /**
     * @param list<FileIdentifier> $results
     */
    public function __construct(private array $results)
    {
    }

    /**
     * @param list<FileIdentifier> $results
     */
    public static function withResults(array $results): self
    {
        return new self($results);
    }

    public function searchFileOngoingUploadIds(): array
    {
        return $this->results;
    }
}
