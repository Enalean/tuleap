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
use Tuleap\DB\DataAccessObject;
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
     * @var DataAccessObject
     */
    private $pdo_wrapper;

    public function __construct(Transition_PostAction_Field_IntDao $set_int_value_dao, DataAccessObject $pdo_wrapper)
    {
        $this->set_int_value_dao = $set_int_value_dao;
        $this->pdo_wrapper       = $pdo_wrapper;
    }

    /**
     * @throws DataAccessQueryException
     */
    public function create(Transition $transition, SetIntValue $set_int_value)
    {
        $this->pdo_wrapper->wrapAtomicOperations(function () use ($transition, $set_int_value) {
            $id_or_failure = $this->set_int_value_dao->create($transition->getId());
            if ($id_or_failure === false) {
                throw new DataAccessQueryException(
                    sprintf(
                        "Cannot create Set Int Value post action on transition with id '%u'",
                        $transition->getId()
                    )
                );
            };
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

    /**
     * @throws DataAccessQueryException
     */
    public function update(SetIntValue $set_int_value): void
    {
        $success = $this->set_int_value_dao->updatePostAction(
            $set_int_value->getId(),
            $set_int_value->getFieldId(),
            $set_int_value->getValue()
        );
        if ($success === false) {
            throw new DataAccessQueryException(
                sprintf(
                    "Cannot update Set Int Value post action with id '%u', field id '%u', and value '%u'",
                    $set_int_value->getId(),
                    $set_int_value->getFieldId(),
                    $set_int_value->getValue()
                )
            );
        }
    }

    /**
     * @param SetIntValue[] $set_int_values
     * @throws DataAccessQueryException
     */
    public function deleteAllByTransitionIfNotIn(Transition $transition, array $set_int_values)
    {
        $ids_to_skip = array_map(
            function (SetIntValue $action) {
                return $action->getId();
            },
            $set_int_values
        );

        $success = $this->set_int_value_dao->deletePostActionByTransitionIfIdNotIn($transition->getId(), $ids_to_skip);
        if ($success === false) {
            throw new DataAccessQueryException(
                sprintf(
                    "Cannot delete all Set Int Value post actions which ids are not in [%s], and on transition with id '%u'",
                    implode(", ", $ids_to_skip),
                    $transition->getId()
                )
            );
        }
    }

    /**
     * @throws DataAccessQueryException
     */
    public function findAllIdsByTransition(Transition $transition): PostActionIdCollection
    {
        $rows_or_failure = $this->set_int_value_dao->findAllIdsByTransitionId($transition->getId());
        if ($rows_or_failure === false) {
            throw new DataAccessQueryException(
                sprintf(
                    "Cannot find ids of all Set Int Value post actions on transition with id '%u'",
                    $transition->getId()
                )
            );
        }

        $ids = [];
        foreach ($rows_or_failure as $row) {
            $ids[] = (int)$row['id'];
        }
        return new PostActionIdCollection(...$ids);
    }
}
