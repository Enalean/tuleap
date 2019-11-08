<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle;

use Cardwall_Column;
use Tracker;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElementFactory;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\EmptyMappedValues;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValues;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValuesInterface;

class FreestyleMappingFactory
{
    /** @var FreestyleMappingDao */
    private $dao;
    /** @var Tracker_FormElementFactory */
    private $form_element_factory;

    public function __construct(FreestyleMappingDao $dao, Tracker_FormElementFactory $form_element_factory)
    {
        $this->dao                  = $dao;
        $this->form_element_factory = $form_element_factory;
    }

    public function getMappedField(Tracker $milestone_tracker, Tracker $tracker): ?Tracker_FormElement_Field_Selectbox
    {
        $field_id = $this->dao->searchMappedField($milestone_tracker, $tracker);
        if ($field_id === null) {
            return null;
        }
        $field = $this->form_element_factory->getUsedListFieldById($tracker, $field_id);
        if ($field instanceof \Tracker_FormElement_Field_Selectbox) {
            return $field;
        }
        return null;
    }

    public function doesFreestyleMappingExist(Tracker $milestone_tracker, Tracker $tracker): bool
    {
        return $this->dao->doesFreestyleMappingExist($milestone_tracker, $tracker);
    }

    public function getValuesMappedToColumn(
        Tracker $milestone_tracker,
        Tracker $tracker,
        Cardwall_Column $column
    ): MappedValuesInterface {
        $rows            = $this->dao->searchMappedFieldValuesForColumn($milestone_tracker, $tracker, $column);
        if (empty($rows)) {
            return new EmptyMappedValues();
        }
        $field_value_ids = [];
        foreach ($rows as $row) {
            $field_value_ids[] = $row['value_id'];
        }
        return new MappedValues($field_value_ids);
    }
}
