<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 */

namespace Tuleap\Tracker\Action;

use Tracker;
use Tracker_FormElementFactory;

class MoveDescriptionSemanticChecker extends MoveSemanticChecker
{
    const DESCRIPTION_SEMANTIC_LABEL = 'description';

    public function __construct(Tracker_FormElementFactory $form_element_factory)
    {
        $this->form_element_factory = $form_element_factory;
    }

    /**
     * @return bool
     */
    public function areBothSemanticsDefined(Tracker $source_tracker, Tracker $target_tracker)
    {
        return $source_tracker->hasSemanticsDescription() && $target_tracker->hasSemanticsDescription();
    }

    /**
     * @return bool
     */
    public function doesBothSemanticFieldHaveTheSameType(Tracker $source_tracker, Tracker $target_tracker)
    {
        $source_tracker_description_field = $this->getSourceSemanticField($source_tracker);
        $target_tracker_description_field = $target_tracker->getDescriptionField();

        return $this->form_element_factory->getType($source_tracker_description_field) ===
            $this->form_element_factory->getType($target_tracker_description_field);
    }

    /**
     * @return string
     */
    public function getSemanticName()
    {
        return self::DESCRIPTION_SEMANTIC_LABEL;
    }

    public function getSourceSemanticField(Tracker $source_tracker)
    {
        return $source_tracker->getDescriptionField();
    }
}
