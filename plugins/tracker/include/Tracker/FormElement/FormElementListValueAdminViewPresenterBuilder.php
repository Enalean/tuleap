<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use Tracker_FormElement_Field;
use Tracker_FormElement_Field_List;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStaticValueDao;
use Tracker_FormElement_Field_List_OpenValue;
use Tracker_FormElement_Field_List_Value;
use Tuleap\Tracker\Colorpicker\ColorpickerMountPointPresenter;

class FormElementListValueAdminViewPresenterBuilder
{
    /**
     * @var BindStaticValueDao
     */
    private $value_dao;

    public function __construct(
        BindStaticValueDao $value_dao,
    ) {
        $this->value_dao = $value_dao;
    }

    public function buildPresenter(
        \Tracker_FormElement_Field $field,
        Tracker_FormElement_Field_List_Value $value,
        ?ColorpickerMountPointPresenter $decorator,
        bool $is_custom_value,
    ): FormElementListValueAdminViewPresenter {
        $value_can_be_hidden  = $this->canValueBeHidden($value, $field);
        $value_can_be_deleted = $this->canValueBeDeleted($value, $field);

        return new FormElementListValueAdminViewPresenter(
            $value,
            $decorator,
            $value_can_be_hidden,
            $value_can_be_deleted,
            $is_custom_value
        );
    }

    public function canValueBeHidden(Tracker_FormElement_Field_List_Value $value, Tracker_FormElement_Field $field): bool
    {
        if ($value instanceof Tracker_FormElement_Field_List_OpenValue) {
            return true;
        }

        return $value->getId() !== Tracker_FormElement_Field_List::NONE_VALUE
            && $this->value_dao->canValueBeHidden($field, $value->getId());
    }

    public function canValueBeDeleted(Tracker_FormElement_Field_List_Value $value, Tracker_FormElement_Field $field): bool
    {
        if ($value instanceof Tracker_FormElement_Field_List_OpenValue) {
            return false;
        }

        return $value->getId() !== Tracker_FormElement_Field_List::NONE_VALUE
            && $this->value_dao->canValueBeDeleted($field, $value->getId());
    }
}
