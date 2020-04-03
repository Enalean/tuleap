<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Project\Admin\DescriptionFields;

class DescriptionFieldLabelBuilder
{
    public static function getFieldTranslatedName(string $field_value): string
    {
        switch ($field_value) {
            case 'project_desc_name:full_desc':
                return $GLOBALS['Language']->getText('project_desc_name', 'full_desc');
            case 'project_desc_name:other_comments':
                return $GLOBALS['Language']->getText('project_desc_name', 'other_comments');
            case 'project_desc_name:req_soft':
                return $GLOBALS['Language']->getText('project_desc_name', 'req_soft');
            case 'project_desc_name:int_prop':
                return $GLOBALS['Language']->getText('project_desc_name', 'int_prop');
            default:
                return $field_value;
        }
    }

    public static function getFieldTranslatedDescription(string $field_value): string
    {
        switch ($field_value) {
            case 'project_desc_desc:full_desc':
                return $GLOBALS['Language']->getText('project_desc_desc', 'full_desc');
            case 'project_desc_desc:other_comments':
                return $GLOBALS['Language']->getText('project_desc_desc', 'other_comments');
            case 'project_desc_desc:req_soft':
                return $GLOBALS['Language']->getText('project_desc_desc', 'req_soft');
            case 'project_desc_desc:int_prop':
                return $GLOBALS['Language']->getText('project_desc_desc', 'int_prop');
            default:
                return $field_value;
        }
    }
}
