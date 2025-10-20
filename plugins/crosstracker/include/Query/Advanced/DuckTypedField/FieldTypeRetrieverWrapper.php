<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\DuckTypedField;

use Tracker_FormElement_Field_List_Bind_Ugroups;
use Tracker_FormElement_Field_List_Bind_Users;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\FormElement\Field\List\Bind\Static\ListFieldStaticBind;
use Tuleap\Tracker\FormElement\Field\List\ListField;
use Tuleap\Tracker\FormElement\RetrieveFieldType;
use Tuleap\Tracker\FormElement\TrackerFormElement;

final readonly class FieldTypeRetrieverWrapper implements RetrieveFieldType
{
    public const string FIELD_DATETIME_TYPE    = 'datetime';
    public const string FIELD_STATIC_LIST_TYPE = 'static_list';
    public const string FIELD_UGROUP_LIST_TYPE = 'ugroup_list';
    public const string FIELD_USER_LIST_TYPE   = 'user_list';
    public const string UNKNOWN_FIELD_TYPE     = 'unknown';

    public function __construct(private RetrieveFieldType $wrapper)
    {
    }

    #[\Override]
    public function getType(TrackerFormElement $form_element): string
    {
        if ($form_element instanceof DateField && $form_element->isTimeDisplayed()) {
            return self::FIELD_DATETIME_TYPE;
        }

        if ($form_element instanceof ListField) {
            switch ($form_element->getBind()->getType()) {
                case ListFieldStaticBind::TYPE:
                    return self::FIELD_STATIC_LIST_TYPE;
                case Tracker_FormElement_Field_List_Bind_Ugroups::TYPE:
                    return self::FIELD_UGROUP_LIST_TYPE;
                case Tracker_FormElement_Field_List_Bind_Users::TYPE:
                    return self::FIELD_USER_LIST_TYPE;
            }
        }

        return $this->wrapper->getType($form_element);
    }
}
