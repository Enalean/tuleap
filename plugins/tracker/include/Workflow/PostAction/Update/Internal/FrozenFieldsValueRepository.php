<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

use Transition;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\Update\FrozenFieldsValue;

class FrozenFieldsValueRepository
{
    /**
     * @var FrozenFieldsDao
     */
    private $frozen_fields_dao;

    public function __construct(FrozenFieldsDao $frozen_fields_dao)
    {
        $this->frozen_fields_dao = $frozen_fields_dao;
    }

    public function create(Transition $transition, FrozenFieldsValue $frozen_fields): void
    {
        $this->frozen_fields_dao->createPostActionForTransitionId($transition->getId(), $frozen_fields->getFieldIds());
    }

    public function deleteAllByTransition(Transition $transition): void
    {
        $this->frozen_fields_dao->deletePostActionsByTransitionId($transition->getId());
    }
}
