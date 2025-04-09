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

namespace Tuleap\CrossTracker\Widget;

use Codendi_Request;
use Tuleap\CrossTracker\Query\CrossTrackerQueryFactory;
use Tuleap\CrossTracker\Query\QueryCreator;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class CrossTrackerWidgetCreator
{
    public function __construct(
        private CreateWidget $create_widget_dao,
        private QueryCreator $query_creator,
        private DBTransactionExecutor $transaction_executor,
    ) {
    }

    /**
     * @return Ok<int>|Err<Fault>
     */
    public function createWithQueries(Codendi_Request $request): Ok|Err
    {
        $cross_tracker_search_parameter = $request->get('queries');

        if (! is_array($cross_tracker_search_parameter)) {
            return Result::ok($this->create_widget_dao->createWidget());
        }

        return $this->transaction_executor->execute(function () use ($cross_tracker_search_parameter) {
            $widget_id = $this->create_widget_dao->createWidget();
            foreach ($cross_tracker_search_parameter as $query) {
                if (! isset($query['title'], $query['tql'], $query['is_default'], $query['description'])) {
                    return Result::err(Fault::fromMessage('Title, description, is_default attribute or TQL query is missing'));
                }
                $title       = $query['title'];
                $description = $query['description'];
                $tql         = $query['tql'];
                $is_default  = $query['is_default'];

                $new_query = CrossTrackerQueryFactory::fromNewQueryToInsert(
                    $widget_id,
                    $title,
                    $description,
                    $tql,
                    $is_default
                );
                $this->query_creator->createNewQuery($new_query);
            }
            return Result::ok($widget_id);
        });
    }
}
