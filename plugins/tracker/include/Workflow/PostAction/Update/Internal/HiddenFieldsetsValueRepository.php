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
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDao;
use Tuleap\Tracker\Workflow\PostAction\Update\HiddenFieldsetsValue;

class HiddenFieldsetsValueRepository
{
    /**
     * @var HiddenFieldsetsDao
     */
    private $hidden_fieldsets_dao;

    public function __construct(HiddenFieldsetsDao $hidden_fieldsets_dao)
    {
        $this->hidden_fieldsets_dao = $hidden_fieldsets_dao;
    }

    public function create(Transition $transition, HiddenFieldsetsValue $hidden_fieldsets_value): void
    {
        $this->hidden_fieldsets_dao->createPostActionForTransitionId(
            $transition->getId(),
            $hidden_fieldsets_value->getFieldsetIds()
        );
    }

    public function deleteAllByTransition(Transition $transition): void
    {
        $this->hidden_fieldsets_dao->deletePostActionsByTransitionId($transition->getId());
    }
}
