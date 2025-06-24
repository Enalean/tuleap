<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Baseline\Adapter;

use AgileDashBoard_Semantic_InitialEffort;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_Text;
use Tuleap\Tracker\Semantic\Description\CachedSemanticDescriptionFieldRetriever;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;
use Tuleap\Tracker\Tracker;

/**
 * Wrap tracker semantic static functions to enable dependency injections which makes classes testable (even if this one is not).
 */
class SemanticFieldRepository
{
    public function findTitleByTracker(Tracker $tracker): ?Tracker_FormElement_Field_Text
    {
        return TrackerSemanticTitle::load($tracker)->getField();
    }

    public function findDescriptionByTracker(Tracker $tracker): ?Tracker_FormElement_Field_Text
    {
        return (CachedSemanticDescriptionFieldRetriever::instance())->fromTracker($tracker);
    }

    public function findInitialEffortByTracker(Tracker $tracker): ?Tracker_FormElement_Field
    {
        return AgileDashBoard_Semantic_InitialEffort::load($tracker)->getField();
    }

    public function findStatusByTracker(Tracker $tracker): ?Tracker_FormElement_Field_List
    {
        return TrackerSemanticStatus::load($tracker)->getField();
    }
}
