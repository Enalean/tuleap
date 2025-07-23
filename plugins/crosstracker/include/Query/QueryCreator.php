<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker\Query;

use Tuleap\DB\DBTransactionExecutor;

final readonly class QueryCreator
{
    public function __construct(private DBTransactionExecutor $transaction, private InsertNewQuery $query_dao, private ResetIsDefaultColumn $reset_is_default_query_dao)
    {
    }

    public function createNewQuery(CrossTrackerQuery $query): CrossTrackerQuery
    {
        return $query->getWidgetId()->match(
            function (int $widget_id) use ($query): CrossTrackerQuery {
                return $this->transaction->execute(function () use ($query, $widget_id): CrossTrackerQuery {
                    if ($query->isDefault()) {
                        $this->reset_is_default_query_dao->resetIsDefaultColumnByWidgetId($widget_id);
                    }
                    $uuid = $this->query_dao->create($query->getQuery(), $query->getTitle(), $query->getDescription(), $widget_id, $query->isDefault());

                    return CrossTrackerQueryFactory::fromCreatedQuery($uuid, $query);
                });
            },
            function (): never {
                throw new \LogicException('Cannot create a cross-tracker query associated with a widget without providing a widget ID');
            }
        );
    }
}
