<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use Psr\Log\LoggerInterface;

final readonly class ChartCachedDaysComparator
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param int[] $expected_days
     * @param int[] $cached_days
     */
    public function areCachedDaysCorrect(array $expected_days, array $cached_days): bool
    {
        sort($expected_days);
        sort($cached_days);
        $result = $expected_days === $cached_days;
        if ($result) {
            $this->logger->debug('Cache is valid');
        } else {
            $this->logger->debug('Cache is NOT valid');
        }

        return $result;
    }
}
