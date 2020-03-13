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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use DataAccessQueryException;
use Transition;
use Transition_PostAction_CIBuildDao;
use Tuleap\Tracker\Workflow\PostAction\Update\CIBuildValue;

/**
 * Anti-corruption layer around Transition_PostAction_CIBuildDao, dedicated to CI Build updates.
 */
class CIBuildValueRepository
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
    public function create(Transition $transition, CIBuildValue $build)
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
        }
    }

    public function deleteAllByTransition(Transition $transition)
    {
        $success = $this->ci_build_dao->deletePostActionByTransition($transition->getId());
        if ($success === false) {
            throw new DataAccessQueryException(
                sprintf(
                    "Cannot delete all CI Build post actions on transition with id '%u'",
                    $transition->getId()
                )
            );
        }
    }
}
