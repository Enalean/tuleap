<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

namespace Tuleap\Project\Admin\DescriptionFields;

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\Project\ProjectDescriptionUsageRetriever;

class DescriptionFieldAdminPresenterBuilder
{
    public const SHORT_DESCRIPTION_FIELD_ID = 'short_description';

    /**
     * @return FieldPresenter[]
     */
    public function build(
        LegacyDataAccessResultInterface $description_fields_infos
    ): array {
        $field_presenters = [];

        $field_presenters[] = new FieldPresenter(
            self::SHORT_DESCRIPTION_FIELD_ID,
            _('Short description'),
            _('What is the purpose of your project?'),
            ProjectDescriptionUsageRetriever::isDescriptionMandatory(),
            $this->getTranslatedRequiredLabel(true),
            "",
            "",
            0,
            true
        );

        foreach ($description_fields_infos as $field) {
            $field_presenters[] = new FieldPresenter(
                $field['group_desc_id'],
                DescriptionFieldLabelBuilder::getFieldTranslatedName($field['desc_name']),
                DescriptionFieldLabelBuilder::getFieldTranslatedDescription($field['desc_description']),
                $field['desc_required'],
                $this->getTranslatedRequiredLabel($field['desc_required']),
                $field['desc_type'],
                $this->getTranslatedTypeLabel($field['desc_type']),
                $field['desc_rank'],
                false
            );
        }

        return $field_presenters;
    }

    private function getTranslatedRequiredLabel($is_required)
    {
        return ($is_required)
            ? $GLOBALS['Language']->getText('admin_desc_fields', 'desc_yes')
            : $GLOBALS['Language']->getText('admin_desc_fields', 'desc_no');
    }

    private function getTranslatedTypeLabel($type)
    {
        return ($type == 'line')
            ? $GLOBALS['Language']->getText('admin_desc_fields', 'desc_line')
            : $GLOBALS['Language']->getText('admin_desc_fields', 'desc_text');
    }
}
