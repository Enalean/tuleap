<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

use Tuleap\Artidoc\Domain\Document\Section\Identifier\InvalidSectionIdentifierStringException;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifierFactory;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class SectionOrderBuilder
{
    public function __construct(private SectionIdentifierFactory $identifier_factory)
    {
    }

    /**
     * @return Ok<SectionOrder>|Err<Fault>
     */
    public function build(
        array $submitted_ids,
        string $submitted_direction,
        string $submitted_compared_to,
    ): Ok|Err {
        $direction = Direction::tryFrom($submitted_direction);
        if ($direction === null) {
            return Result::err(InvalidDirectionFault::build($submitted_direction));
        }

        try {
            $compared_to = $this->identifier_factory->buildFromHexadecimalString($submitted_compared_to);
        } catch (InvalidSectionIdentifierStringException) {
            return Result::err(InvalidComparedToFault::build($submitted_compared_to));
        }

        if (count($submitted_ids) !== 1) {
            return Result::err(InvalidIdsFault::build());
        }

        if (in_array($submitted_compared_to, $submitted_ids, true)) {
            return Result::err(CannotMoveSectionRelativelyToItselfFault::build());
        }

        try {
            $identifiers = array_map(
                fn($id): SectionIdentifier => $this->identifier_factory->buildFromHexadecimalString($id),
                $submitted_ids,
            );
        } catch (InvalidSectionIdentifierStringException) {
            return Result::err(InvalidIdsFault::build());
        }

        return Result::ok(SectionOrder::build($identifiers[0], $direction, $compared_to));
    }
}
