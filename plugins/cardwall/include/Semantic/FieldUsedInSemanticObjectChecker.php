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

use Tracker_FormElement_Field;

class FieldUsedInSemanticObjectChecker
{
    /**
     * @var BackgroundColorDao
     */
    private $color_dao;

    public function __construct(BackgroundColorDao $color_dao)
    {
        $this->color_dao = $color_dao;
    }

    /**
     * @param Tracker_FormElement_Field[] $card_fields
     *
     * @return bool
     */
    public function isUsedInSemantic(Tracker_FormElement_Field $field, array $card_fields)
    {
        return $this->isUsedInCardFieldSemantic($field, $card_fields) || $this->isUsedInBackgroundColorSemantic($field);
    }

    /**
     * @param array                     $card_fields
     *
     * @return bool
     */
    private function isUsedInCardFieldSemantic(Tracker_FormElement_Field $field, array $card_fields)
    {
        foreach ($card_fields as $card_field) {
            if ($card_field->getId() == $field->getId()) {
                return true;
            }
        }

        return false;
    }

    public function isUsedInBackgroundColorSemantic(Tracker_FormElement_Field $field)
    {
        return $this->color_dao->isFieldUsedAsBackgroundColor($field->getId());
    }
}
