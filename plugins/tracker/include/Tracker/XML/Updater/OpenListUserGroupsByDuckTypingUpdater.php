<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Tracker\XML\Updater;

use SimpleXMLElement;
use Tracker_FormElement_Field_OpenList;
use Tuleap\Project\UGroupRetriever;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\SearchUserGroupsValuesByFieldIdAndUserGroupId;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\SearchUserGroupsValuesById;
use Tuleap\Tracker\XML\Updater\MoveChangesetXMLUpdater;

final class OpenListUserGroupsByDuckTypingUpdater implements UpdateOpenListUserGroupsByDuckTyping
{
    public function __construct(
        private readonly SearchUserGroupsValuesById $search_user_groups_values_by_id,
        private readonly SearchUserGroupsValuesByFieldIdAndUserGroupId $search_user_groups_values_by_field_id_and_user_group_id,
        private readonly UGroupRetriever $group_retriever,
        private readonly UGroupRetriever $group_name_retriever,
        private readonly MoveChangesetXMLUpdater $XML_updater,
        private readonly \XML_SimpleXMLCDATAFactory $cdata_factory,
    ) {
    }

    public function updateUserGroupsForDuckTypingMove(SimpleXMLElement $changeset_xml, Tracker_FormElement_Field_OpenList $source_field, Tracker_FormElement_Field_OpenList $destination_field, int $index): void
    {
        $list_value_ids = $changeset_xml->field_change[$index]->value;
        if ($list_value_ids === null) {
            return;
        }

        $destination_values_ids = [];
        foreach ($list_value_ids as $value_id) {
            $bind_value_id     = (int) str_replace(Tracker_FormElement_Field_OpenList::BIND_PREFIX, "", (string) $value_id);
            $source_ugroup_raw = $this->search_user_groups_values_by_id->searchById($bind_value_id);
            if (! $source_ugroup_raw) {
                continue;
            }
            $source_ugroup = $this->group_retriever->getUGroup($source_field->getTracker()->getProject(), $source_ugroup_raw['ugroup_id']);
            if (! $source_ugroup) {
                continue;
            }

            $destination_ugroup = $this->group_name_retriever->getUGroupByName($destination_field->getTracker()->getProject(), $source_ugroup->getName());
            if (! $destination_ugroup) {
                continue;
            }

            $destination_value_raw = $this->search_user_groups_values_by_field_id_and_user_group_id->searchByFieldIdAndGroupId($destination_field->getId(), $destination_ugroup->getId());
            if (! $destination_value_raw) {
                continue;
            }

            $destination_values_ids[] =  $destination_value_raw['id'];
        }


        $this->XML_updater->deleteFieldChangeValueNode($changeset_xml, $index);

        if (empty($destination_values_ids)) {
            $this->cdata_factory->insertWithAttributes(
                $changeset_xml->field_change[$index],
                "value",
                $destination_field->getDefaultValue(),
                ['format' => "id"]
            );
            return;
        }

        foreach ($destination_values_ids as $value_id) {
            $this->cdata_factory->insertWithAttributes(
                $changeset_xml->field_change[$index],
                "value",
                Tracker_FormElement_Field_OpenList::BIND_PREFIX . $value_id,
                ['format' => "id"]
            );
        }
    }
}
