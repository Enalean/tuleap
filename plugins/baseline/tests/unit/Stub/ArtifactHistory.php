<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Baseline\Stub;

use DateTimeInterface;
use Tuleap\Baseline\Domain\BaselineArtifact;

/**
 * History of an artifact, represented as a list of change sets (i.e. snapshot version).
 */
class ArtifactHistory
{
    /**
     * Chronological ordered list of change sets.
     * @var BaselineChangeSet[]
     */
    private $change_sets = [];

    public function add(BaselineArtifact $artifact, DateTimeInterface $date): void
    {
        foreach ($this->change_sets as $index => $change_set) {
            if ($change_set->isLaterThan($date)) {
                array_splice(
                    $this->change_sets,
                    $index,
                    0,
                    [new BaselineChangeSet($date, $artifact)]
                );
                return;
            }
        }
        $this->change_sets[] = new BaselineChangeSet($date, $artifact);
    }

    public function findAt(DateTimeInterface $date): ?BaselineArtifact
    {
        $previous_change_set = null;
        foreach ($this->change_sets as $change_set) {
            if ($change_set->isLaterThan($date)) {
                if ($previous_change_set === null) {
                    return null;
                }
                return $previous_change_set->getArtifact();
            }
            $previous_change_set = $change_set;
        }
        return $previous_change_set?->getArtifact();
    }
}
