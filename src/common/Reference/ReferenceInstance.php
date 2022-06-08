<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Reference;

use Reference;

/**
 * Stores a reference as extracted from some user text.
 * Only valid Reference Instances are created (i.e., the corresponding "Reference" object must exist).
 */
final class ReferenceInstance
{
    /** @psalm-readonly */
    private GotoLink $gotoLink;

    /**
     * Note that we need a valid reference parameter
     */
    public function __construct(
        /** @psalm-readonly */
        private string $match,
        /** @psalm-readonly */
        private Reference $reference,
        /** @psalm-readonly */
        private string $value,
        string $keyword,
        int $project_id,
    ) {
        $this->gotoLink = GotoLink::fromComponents($keyword, $value, $project_id);
    }

    /** @psalm-mutation-free */
    public function getMatch(): string
    {
        return $this->match;
    }

    /** @psalm-mutation-free */
    public function getReference(): Reference
    {
        return $this->reference;
    }

    /** @psalm-mutation-free */
    public function getValue(): string
    {
        return $this->value;
    }

    public function getFullGotoLink(): string
    {
        return $this->gotoLink->getFullGotoLink();
    }
}
