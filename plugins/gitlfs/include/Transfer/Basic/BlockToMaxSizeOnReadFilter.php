<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\GitLFS\Transfer\Basic;

use Tuleap\GitLFS\StreamFilter\FilterInterface;

final class BlockToMaxSizeOnReadFilter implements FilterInterface
{
    /**
     * @var int
     */
    private $max_size;
    /**
     * @var int
     */
    private $read_data_size = 0;
    /**
     * @var bool
     */
    private $maximum_size_exceeded = false;

    public function __construct($max_size)
    {
        if ($max_size < 0) {
            throw new \UnexpectedValueException('The size must be positive');
        }
        $this->max_size = $max_size;
    }

    public function process($data_chunk): string
    {
        if ($this->maximum_size_exceeded) {
            throw new ReadTooMuchDataException($this->max_size);
        }
        $remaining_size = $this->max_size - $this->read_data_size;
        $cut_data_chunk = \substr($data_chunk, 0, $remaining_size);
        if ($cut_data_chunk !== $data_chunk) {
            $this->maximum_size_exceeded = true;
        }

        $this->read_data_size += \strlen($cut_data_chunk);
        return $cut_data_chunk;
    }

    public function getFilteredChainIdentifier(): int
    {
        return STREAM_FILTER_READ;
    }

    public function filterDetachedEvent(): void
    {
    }

    public function hasMaximumSizeBeenExceeded(): bool
    {
        return $this->maximum_size_exceeded;
    }

    public function getReadDataSize(): int
    {
        return $this->read_data_size;
    }
}
