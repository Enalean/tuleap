<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\Grammar;

final readonly class FromProjectIn implements FromProjectCondition
{
    /**
     * @param list<string> $values
     */
    public function __construct(private array $values)
    {
    }

    /**
     * @return list<string>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    #[\Override]
    public function acceptFromProjectConditionVisitor(FromProjectConditionVisitor $visitor, $parameters)
    {
        return $visitor->visitIn($this, $parameters);
    }
}
