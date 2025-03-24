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

namespace Tuleap\CrossTracker\Query\Advanced;

use ForgeConfig;
use PFUser;
use Tracker;
use Tuleap\CrossTracker\Query\CrossTrackerArtifactQueryFactory;
use Tuleap\Tracker\Report\Query\Advanced\IBuildInvalidSelectablesCollection;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSelectablesCollection;
use Tuleap\Tracker\Report\Query\Advanced\SelectablesMustBeUniqueException;
use Tuleap\Tracker\Report\Query\Advanced\SelectLimitExceededException;

final readonly class InvalidSelectablesCollectionBuilder implements IBuildInvalidSelectablesCollection
{
    /**
     * @param Tracker[] $trackers
     */
    public function __construct(
        private InvalidSelectablesCollectorVisitor $collector_visitor,
        private array $trackers,
        private PFUser $user,
    ) {
    }

    public function buildCollectionOfInvalidSelectables(array $selectables): InvalidSelectablesCollection
    {
        $unique_selectables = array_unique($selectables, SORT_REGULAR);
        if (count($unique_selectables) !== count($selectables)) {
            throw new SelectablesMustBeUniqueException();
        }

        $max_select = ForgeConfig::getInt(CrossTrackerArtifactQueryFactory::MAX_SELECT);
        if ($max_select > 0 && count($selectables) > $max_select) {
            throw new SelectLimitExceededException(count($selectables), $max_select);
        }

        $collection = new InvalidSelectablesCollection();
        foreach ($selectables as $selectable) {
            $this->collector_visitor->collectErrors(
                $selectable,
                $collection,
                $this->trackers,
                $this->user,
            );
        }

        return $collection;
    }
}
