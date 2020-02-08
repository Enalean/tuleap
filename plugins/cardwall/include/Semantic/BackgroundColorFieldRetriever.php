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

namespace Tuleap\Cardwall\Semantic;

use Tracker;
use Tracker_FormElementFactory;

class BackgroundColorFieldRetriever
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var BackgroundColorDao
     */
    private $background_color_dao;

    public function __construct(
        Tracker_FormElementFactory $form_element_factory,
        BackgroundColorDao $background_color_dao
    ) {
        $this->form_element_factory = $form_element_factory;
        $this->background_color_dao = $background_color_dao;
    }

    /**
     * @return \Tracker_FormElement_Field_List
     * @throws BackgroundColorSemanticFieldNotFoundException
     */
    public function getField(Tracker $tracker)
    {
        $field_id = $this->background_color_dao->searchBackgroundColor($tracker->getId());
        $field    = $this->form_element_factory->getUsedListFieldById($tracker, $field_id);
        if (! $field) {
            throw new BackgroundColorSemanticFieldNotFoundException();
        }

        return $field;
    }
}
