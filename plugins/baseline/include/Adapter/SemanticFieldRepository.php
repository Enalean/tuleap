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
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Semantic\Description\CachedSemanticDescriptionFieldRetriever;
use Tuleap\Tracker\Semantic\Status\CachedSemanticStatusFieldRetriever;
use Tuleap\Tracker\Semantic\Title\CachedSemanticTitleFieldRetriever;
use Tuleap\Tracker\Tracker;

/**
 * Wrap tracker semantic static functions to enable dependency injections which makes classes testable (even if this one is not).
 */
class SemanticFieldRepository
{
    public function findTitleByTracker(Tracker $tracker): ?TextField
    {
        return CachedSemanticTitleFieldRetriever::instance()->fromTracker($tracker);
    }

    public function findDescriptionByTracker(Tracker $tracker): ?TextField
    {
        return (CachedSemanticDescriptionFieldRetriever::instance())->fromTracker($tracker);
    }

    public function findInitialEffortByTracker(Tracker $tracker): ?TrackerField
    {
        return AgileDashBoard_Semantic_InitialEffort::load($tracker)->getField();
    }

    public function findStatusByTracker(Tracker $tracker): ?ListField
    {
        return CachedSemanticStatusFieldRetriever::instance()->fromTracker($tracker);
    }
}
