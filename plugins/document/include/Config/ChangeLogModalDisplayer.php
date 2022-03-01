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

namespace Tuleap\Document\Config;

use Tuleap\Docman\FilenamePattern\RetrieveFilenamePattern;

final class ChangeLogModalDisplayer
{
    public function __construct(
        private RetrieveFilenamePattern $pattern_retriever,
        private HistoryEnforcementSettings $history_enforcement_settings,
    ) {
    }

    public function isChangelogModalDisplayedAfterDragAndDrop(int $project_id): bool
    {
        return $this->history_enforcement_settings->isChangelogProposedAfterDragAndDrop()
            || $this->isFilenamePatternSet($project_id);
    }

    private function isFilenamePatternSet(int $project_id): bool
    {
        $filename_pattern =  $this->pattern_retriever->getPattern($project_id);
        return isset($filename_pattern) && $filename_pattern !== "";
    }
}
