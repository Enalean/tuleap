<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\View\Admin;

use Tuleap\Docman\FilenamePattern\FilenamePattern;

class FilenamePatternWarningsCollector implements \Tuleap\Event\Dispatchable
{
    public const string NAME = 'filenamePatternWarningsCollector';

    /**
     * @var string[]
     */
    private array $warnings = [];
    /**
     * @var string[]
     */
    private array $info = [];

    public function __construct(private int $project_id, private FilenamePattern $filename_pattern)
    {
    }

    /**
     * @return string[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * @return string[]
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }

    public function addInfo(string $info): void
    {
        $this->info[] = $info;
    }

    public function getFilenamePattern(): FilenamePattern
    {
        return $this->filename_pattern;
    }

    public function getProjectId(): int
    {
        return $this->project_id;
    }
}
