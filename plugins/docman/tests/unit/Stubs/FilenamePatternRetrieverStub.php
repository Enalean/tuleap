<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Docman\Tests\Stub;

use Tuleap\Docman\FilenamePattern\FilenamePattern;
use Tuleap\Docman\FilenamePattern\RetrieveFilenamePattern;

final class FilenamePatternRetrieverStub implements RetrieveFilenamePattern
{
    private function __construct(private FilenamePattern $pattern)
    {
    }

    #[\Override]
    public function getPattern(int $project_id): FilenamePattern
    {
        return $this->pattern;
    }

    public static function buildWithPattern(string $pattern): self
    {
        return new self(new FilenamePattern($pattern, true));
    }

    public static function buildWithNoPattern(): self
    {
        return new self(new FilenamePattern('', false));
    }
}
