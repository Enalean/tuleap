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
use Transition_PostAction_CIBuildDao;

/**
 * Anti-corruption layer around Transition_PostAction_CIBuildDao, dedicated to CI Build updates.
 */
class CIBuildRepository
{
    /**
     * @var Transition_PostAction_CIBuildDao
     */
    private $ci_build_dao;

    public function __construct(Transition_PostAction_CIBuildDao $ci_build_dao)
    {
        $this->ci_build_dao = $ci_build_dao;
    }

    /**
     * @throws DataAccessQueryException
     */
    public function create(Transition $transition, CIBuild $build)
    {
        $id_or_failure = $this->ci_build_dao->create($transition->getId(), $build->getJobUrl());
        if ($id_or_failure === false) {
            throw new DataAccessQueryException(
                sprintf(
                    "Cannot create CI Build post action with Job_url '%s' on transition with id '%u'",
                    $build->getJobUrl(),
                    $transition->getId()
                )
            );
        };
    }

    /**
     * @throws DataAccessQueryException
     */
    public function update(CIBuild $build): void
    {
        $success = $this->ci_build_dao->updatePostAction($build->getId(), $build->getJobUrl());
        if ($success === false) {
            throw new DataAccessQueryException(
                sprintf(
                    "Cannot update CI Build post action with id '%u' and Job_url '%s'",
                    $build->getId(),
                    $build->getJobUrl()
                )
            );
        }
    }

    /**
     * @param CIBuild[] $ci_builds
     * @throws DataAccessQueryException
     */
    public function deleteAllByTransitionIfNotIn(Transition $transition, array $ci_builds)
    {
        $ids_to_skip = array_map(
            function (CIBuild $action) {
                return $action->getId();
            },
            $ci_builds
        );

        $success = $this->ci_build_dao->deletePostActionByTransitionIfIdNotIn($transition->getId(), $ids_to_skip);
        if ($success === false) {
            throw new DataAccessQueryException(
                sprintf(
                    "Cannot delete all CI Build post actions which ids are not in [%s], and on transition with id '%u'",
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
        $rows_or_failure = $this->ci_build_dao->findAllIdsByTransitionId($transition->getId());
        if ($rows_or_failure === false) {
            throw new DataAccessQueryException(
                sprintf(
                    "Cannot find ids of all actions on transition with id '%u'",
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
