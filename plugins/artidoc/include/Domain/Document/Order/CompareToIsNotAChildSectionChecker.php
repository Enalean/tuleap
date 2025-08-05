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

namespace Tuleap\Artidoc\Domain\Document\Order;

use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class CompareToIsNotAChildSectionChecker
{
    public function __construct()
    {
    }

    /**
     * @param list<SectionIdentifier> $child_identifiers
     * @return Ok<list<SectionIdentifier>>|Err<Fault>
     */
    public function checkCompareToIsNotAChildSection(array $child_identifiers, SectionIdentifier $compare_to): Ok|Err
    {
        foreach ($child_identifiers as $child_identifier) {
            if ($child_identifier->toString() === $compare_to->toString()) {
                return Result::err(CompareToSectionIsAChildSectionFault::build());
            }
        }
        return Result::ok($child_identifiers);
    }
}
