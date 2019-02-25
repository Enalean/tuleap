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

namespace Tuleap\Baseline\Adapter;

use Tracker;
use Tracker_FormElement;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_Text;
use Tracker_Semantic_Description;
use Tracker_Semantic_Status;
use Tracker_Semantic_Title;
use Tuleap\Baseline\FieldRepository;

class FieldRepositoryAdapter implements FieldRepository
{
    public function findTitleByTracker(Tracker $tracker): ?Tracker_FormElement_Field_Text
    {
        return $this->getNullIfNotAllowed(Tracker_Semantic_Title::load($tracker)->getField());
    }

    public function findDescriptionByTracker(Tracker $tracker): ?Tracker_FormElement_Field_Text
    {
        return $this->getNullIfNotAllowed(Tracker_Semantic_Description::load($tracker)->getField());
    }

    public function findStatusByTracker(Tracker $tracker): ?Tracker_FormElement_Field_List
    {
        return $this->getNullIfNotAllowed(Tracker_Semantic_Status::load($tracker)->getField());
    }

    private function getNullIfNotAllowed(?Tracker_FormElement $field): ?Tracker_FormElement
    {
        if ($field === null || ! $field->userCanRead()) {
            return null;
        }

        return $field;
    }
}
