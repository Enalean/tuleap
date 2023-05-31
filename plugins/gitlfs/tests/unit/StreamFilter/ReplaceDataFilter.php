<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\StreamFilter;

final class ReplaceDataFilter implements FilterInterface
{
    private string $replacement_data;
    private bool $has_already_written_expected_data = false;

    public function __construct(string $replacement_data)
    {
        $this->replacement_data = $replacement_data;
    }

    /**
     * @param string $data_chunk
     */
    public function process($data_chunk): string
    {
        if ($this->has_already_written_expected_data) {
            return '';
        }
        return $this->replacement_data;
    }

    public function getFilteredChainIdentifier(): int
    {
        return STREAM_FILTER_READ;
    }

    public function filterDetachedEvent(): void
    {
    }
}
