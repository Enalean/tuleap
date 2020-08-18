<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Request;

/**
 * @psalm-immutable
 */
final class PeriodQuery
{
    public const FUTURE  = 'future';
    public const CURRENT = 'current';
    /**
     * @var string
     */
    private $period;

    private function __construct(string $period)
    {
        $this->period = $period;
    }

    public static function createCurrent(): self
    {
        return new self(self::CURRENT);
    }

    public static function createFuture(): self
    {
        return new self(self::FUTURE);
    }

    public function isCurrent(): bool
    {
        return $this->period === self::CURRENT;
    }

    public function isFuture(): bool
    {
        return $this->period === self::FUTURE;
    }
}
