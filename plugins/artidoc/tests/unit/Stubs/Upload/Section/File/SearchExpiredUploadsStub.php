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

use Tuleap\Artidoc\Upload\Section\File\ExpiredFileInformation;
use Tuleap\Artidoc\Upload\Section\File\SearchExpiredUploads;

final readonly class SearchExpiredUploadsStub implements SearchExpiredUploads
{
    /**
     * @param list<ExpiredFileInformation> $results
     */
    public function __construct(private array $results)
    {
    }

    /**
     * @param list<ExpiredFileInformation> $results
     */
    public static function withResults(array $results): self
    {
        return new self($results);
    }

    #[\Override]
    public function searchExpiredUploads(\DateTimeImmutable $current_time): array
    {
        return $this->results;
    }
}
