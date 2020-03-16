<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use DataAccessQueryException;
use Transition;
use Transition_PostAction_Field_IntDao;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Workflow\PostAction\Update\SetIntValue;

/**
 * Anti-corruption layer around Transition_PostAction_Field_IntDao, dedicated to Set Int Value updates.
 */
class SetIntValueRepository
{
    /**
     * @var Transition_PostAction_Field_IntDao
     */
    private $set_int_value_dao;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        Transition_PostAction_Field_IntDao $set_int_value_dao,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->set_int_value_dao    = $set_int_value_dao;
        $this->transaction_executor = $transaction_executor;
    }

    /**
     * @throws DataAccessQueryException
     */
    public function create(Transition $transition, SetIntValue $set_int_value)
    {
        $this->transaction_executor->execute(function () use ($transition, $set_int_value) {
            $id_or_failure = $this->set_int_value_dao->create($transition->getId());
            if ($id_or_failure === false) {
                throw new DataAccessQueryException(
                    sprintf(
                        "Cannot create Set Int Value post action on transition with id '%u'",
                        $transition->getId()
                    )
                );
            }
            $success = $this->set_int_value_dao->updatePostAction(
                $id_or_failure,
                $set_int_value->getFieldId(),
                $set_int_value->getValue()
            );
            if ($success === false) {
                throw new DataAccessQueryException(
                    sprintf(
                        "Cannot create Set Int Value post action with field id '%u' and value '%u'",
                        $set_int_value->getFieldId(),
                        $set_int_value->getValue()
                    )
                );
            }
        });
    }

    public function deleteAllByTransition(Transition $transition)
    {
        $success = $this->set_int_value_dao->deletePostActionsByTransitionId($transition->getId());

        if ($success === false) {
            throw new DataAccessQueryException(
                sprintf(
                    "Cannot delete all Set Int Value post actions for transition '%u'",
                    $transition->getId()
                )
            );
        }
    }
}
