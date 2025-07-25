<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query;

use Tuleap\DB\DBTransactionExecutor;

final class QueryUpdater
{
    public function __construct(private DBTransactionExecutor $transaction, private UpdateQuery $query_dao, private ResetIsDefaultColumn $reset_is_default_query_dao)
    {
    }

    public function updateQuery(CrossTrackerQuery $query): void
    {
        $query->getWidgetId()->match(
            function (int $widget_id) use ($query): void {
                $this->transaction->execute(function () use ($query, $widget_id): void {
                    if ($query->isDefault()) {
                        $this->reset_is_default_query_dao->resetIsDefaultColumnByWidgetId($widget_id);
                    }
                    $this->query_dao->update($query->getUUID(), $query->getQuery(), $query->getTitle(), $query->getDescription(), $query->isDefault());
                });
            },
            function (): never {
                throw new \LogicException('Cannot update a cross-tracker query associated with a widget without providing a widget ID');
            }
        );
    }
}
