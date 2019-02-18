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
use Transition_PostAction_Field_FloatDao;
use Tuleap\DB\DataAccessObject;
use Tuleap\Tracker\Workflow\PostAction\Update\SetFloatValue;

/**
 * Anti-corruption layer around Transition_PostAction_Field_FloatDao, dedicated to Set Float Value updates.
 */
class SetFloatValueRepository
{
    /**
     * @var Transition_PostAction_Field_FloatDao
     */
    private $set_float_value_dao;

    /**
     * @var DataAccessObject
     */
    private $pdo_wrapper;

    public function __construct(Transition_PostAction_Field_FloatDao $set_float_value_dao, DataAccessObject $pdo_wrapper
    )
    {
        $this->set_float_value_dao = $set_float_value_dao;
        $this->pdo_wrapper         = $pdo_wrapper;
    }

    /**
     * @throws DataAccessQueryException
     */
    public function create(Transition $transition, SetFloatValue $set_float_value)
    {
        $this->pdo_wrapper->wrapAtomicOperations(function () use ($transition, $set_float_value) {
            $id_or_failure = $this->set_float_value_dao->create($transition->getId());
            if ($id_or_failure === false) {
                throw new DataAccessQueryException(
                    sprintf(
                        "Cannot create Set Float Value post action on transition with id '%u'",
                        $transition->getId()
                    )
                );
            };
            $success = $this->set_float_value_dao->updatePostAction(
                $id_or_failure,
                $set_float_value->getFieldId(),
                $set_float_value->getValue()
            );
            if ($success === false) {
                throw new DataAccessQueryException(
                    sprintf(
                        "Cannot create Set Float Value post action with field id '%u' and value '%u'",
                        $set_float_value->getFieldId(),
                        $set_float_value->getValue()
                    )
                );
            }
        });
    }

    /**
     * @throws DataAccessQueryException
     */
    public function update(SetFloatValue $set_float_value): void
    {
        $success = $this->set_float_value_dao->updatePostAction(
            $set_float_value->getId(),
            $set_float_value->getFieldId(),
            $set_float_value->getValue()
        );
        if ($success === false) {
            throw new DataAccessQueryException(
                sprintf(
                    "Cannot update Set Float Value post action with id '%u', field id '%u', and value '%u'",
                    $set_float_value->getId(),
                    $set_float_value->getFieldId(),
                    $set_float_value->getValue()
                )
            );
        }
    }

    /**
     * @param SetFloatValue[] $set_float_values
     * @throws DataAccessQueryException
     */
    public function deleteAllByTransitionIfNotIn(Transition $transition, array $set_float_values)
    {
        $ids_to_skip = array_map(
            function (SetFloatValue $action) {
                return $action->getId();
            },
            $set_float_values
        );

        $success = $this->set_float_value_dao->deletePostActionByTransitionIfIdNotIn($transition->getId(), $ids_to_skip);
        if ($success === false) {
            throw new DataAccessQueryException(
                sprintf(
                    "Cannot delete all Set Float Value post actions which ids are not in [%s], and on transition with id '%u'",
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
        $rows_or_failure = $this->set_float_value_dao->findAllIdsByTransitionId($transition->getId());
        if ($rows_or_failure === false) {
            throw new DataAccessQueryException(
                sprintf(
                    "Cannot find ids of all Set Float Value post actions on transition with id '%u'",
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
