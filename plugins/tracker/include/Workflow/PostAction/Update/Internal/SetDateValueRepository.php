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
use Transition_PostAction_Field_DateDao;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Workflow\PostAction\Update\SetDateValue;

/**
 * Anti-corruption layer around Transition_PostAction_Field_DateDao, dedicated to Set Date Value updates.
 */
class SetDateValueRepository
{
    /**
     * @var Transition_PostAction_Field_DateDao
     */
    private $set_date_value_dao;
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;

    public function __construct(
        Transition_PostAction_Field_DateDao $set_date_value_dao,
        DBTransactionExecutor $transaction_executor
    ) {
        $this->set_date_value_dao   = $set_date_value_dao;
        $this->transaction_executor = $transaction_executor;
    }

    /**
     * @throws DataAccessQueryException
     */
    public function create(Transition $transition, SetDateValue $set_date_value)
    {
        $this->transaction_executor->execute(function () use ($transition, $set_date_value) {
            $id_or_failure = $this->set_date_value_dao->create($transition->getId());
            if ($id_or_failure === false) {
                throw new DataAccessQueryException(
                    sprintf(
                        "Cannot create Set Date Value post action on transition with id '%u'",
                        $transition->getId()
                    )
                );
            }
            $success = $this->set_date_value_dao->updatePostAction(
                $id_or_failure,
                $set_date_value->getFieldId(),
                $set_date_value->getValue()
            );
            if ($success === false) {
                throw new DataAccessQueryException(
                    sprintf(
                        "Cannot create Set Date Value post action with field id '%u' and value '%u'",
                        $set_date_value->getFieldId(),
                        $set_date_value->getValue()
                    )
                );
            }
        });
    }

    public function deleteAllByTransition(Transition $transition)
    {
        $success = $this->set_date_value_dao->deletePostActionsByTransitionId($transition->getId());

        if ($success === false) {
            throw new DataAccessQueryException(
                sprintf(
                    "Cannot delete all Set Date Value post actions for transition '%u'",
                    $transition->getId()
                )
            );
        }
    }
}
