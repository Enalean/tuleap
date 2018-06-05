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

use Feedback;
use Tracker;
use Tracker_FormElementFactory;

class BackgroundColorFieldSaver
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $tracker_form_element_factory;
    /**
     * @var BackgroundColorDao
     */
    private $dao;

    public function __construct(Tracker_FormElementFactory $tracker_form_element_factory, BackgroundColorDao $dao)
    {
        $this->tracker_form_element_factory = $tracker_form_element_factory;
        $this->dao                          = $dao;
    }

    public function chooseBackgroundColorField(Tracker $tracker, $field_id)
    {
        $field = $this->tracker_form_element_factory->getFieldById($field_id);
        if (! $field) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-cardwall', 'Field not found')
            );

            return;
        }

        $this->dao->save($tracker->getId(), $field_id);

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            sprintf(
                dgettext('tuleap-cardwall', 'The field used to determine background color is now %s'),
                $field->getLabel()
            )
        );
    }

    public function unsetBackgroundColorSemantic(Tracker $tracker)
    {
        $this->dao->unsetBackgroundColorSemantic($tracker->getId());

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-cardwall', 'The field used to determine background color has been removed with success.')
        );
    }
}
