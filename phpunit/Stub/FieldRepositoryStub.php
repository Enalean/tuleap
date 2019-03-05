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

namespace Tuleap\Baseline\Stub;

use Tracker;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_Text;
use Tuleap\Baseline\FieldRepository;

/**
 * Implementation of FieldRepository used for tests.
 */
class FieldRepositoryStub implements FieldRepository
{
    /** @var Tracker_FormElement_Field_Text */
    private $title;

    /** @var Tracker_FormElement_Field_Text */
    private $description;

    /** @var Tracker_FormElement_Field_List */
    private $status;

    public function findTitleByTracker(Tracker $tracker): ?Tracker_FormElement_Field_Text
    {
        return $this->title;
    }

    public function findDescriptionByTracker(Tracker $tracker): ?Tracker_FormElement_Field_Text
    {
        return $this->description;
    }

    public function findStatusByTracker(Tracker $tracker): ?Tracker_FormElement_Field_List
    {
        return $this->status;
    }

    public function setTitleForAllTrackers(Tracker_FormElement_Field_Text $title): void
    {
        $this->title = $title;
    }

    public function setDescriptionForAllTrackers(Tracker_FormElement_Field_Text $description): void
    {
        $this->description = $description;
    }

    public function setStatusForAllTrackers(Tracker_FormElement_Field_List $status): void
    {
        $this->status = $status;
    }
}
