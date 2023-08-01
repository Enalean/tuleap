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

namespace Tuleap\Reference;

use Tuleap\Event\Dispatchable;

final class CheckCrossReferenceValidityEvent implements Dispatchable
{
    public const NAME = "checkCrossReferenceValidityEvent";

    /**
     * @param CrossReference[] $cross_references
     */
    public function __construct(private array $cross_references, private \PFUser $user)
    {
    }

    /**
     * @return CrossReference[]
     */
    public function getCrossReferences(): array
    {
        return $this->cross_references;
    }

    public function removeInvalidCrossReference(int $key): void
    {
        unset($this->cross_references[$key]);
    }

    public function getUser(): \PFUser
    {
        return $this->user;
    }
}
