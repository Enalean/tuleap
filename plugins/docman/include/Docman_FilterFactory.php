<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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

class Docman_FilterFactory //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    /**
     * @var string[]
     */
    public array $dynTextFields;
    public $groupId;

    public function __construct($groupId)
    {
        $this->dynTextFields = [];
        $this->groupId       = $groupId;
    }

    public function getGlobalSearchMetadata(): Docman_Metadata
    {
        // Special case for a fake metadata: generic text search
        $md = new Docman_Metadata();
        $md->setGroupId($this->groupId);
        $md->setName(dgettext('tuleap-docman', 'Global text search'));
        $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $md->setUseIt(PLUGIN_DOCMAN_METADATA_USED);
        $md->setLabel('global_txt');
        return $md;
    }

    public function getItemTypeSearchMetadata()
    {
        // Special case for a fake metadata: item type search
        $md = new Docman_ListMetadata();
        $md->setGroupId($this->groupId);
        $md->setName(dgettext('tuleap-docman', 'Item type'));
        $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $md->setUseIt(PLUGIN_DOCMAN_METADATA_USED);
        $md->setLabel('item_type');
        $md->setIsMultipleValuesAllowed(true);

        $row          = [];
        $values       = [];
        $item_factory = Docman_ItemFactory::instance($this->groupId);
        $all_types    = [
            PLUGIN_DOCMAN_ITEM_TYPE_FILE,
            PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
            PLUGIN_DOCMAN_ITEM_TYPE_EMPTY,
            PLUGIN_DOCMAN_ITEM_TYPE_LINK,
            PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
        ];
        foreach ($all_types as $type) {
            $row['value_id'] = $type;
            $row['name']     = $item_factory->getItemTypeAsText($type);
            $row['status']   = 'A';
            $love            = new Docman_MetadataListOfValuesElement();
            $love->initFromRow($row);
            $values[] = $love;
        }

        $md->setListOfValueElements($values);

        return $md;
    }

    public function createFromMetadata(Docman_Metadata $md, $advSearch): ?Docman_Filter
    {
        $filter = null;

        if ($md->getLabel() == 'owner') {
            $filter = new Docman_FilterOwner($md);
        } else {
            switch ($md->getType()) {
                case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
                case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
                    $filter = new Docman_FilterText($md);
                    if (Docman_MetadataFactory::isRealMetadata($md->getLabel())) {
                        $this->dynTextFields[] = $md->getLabel();
                    }
                    break;
                case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                    if ($advSearch) {
                        $filter = new Docman_FilterDateAdvanced($md);
                    } else {
                        $filter = new Docman_FilterDate($md);
                    }
                    break;
                case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                    if ($advSearch) {
                        $filter = new Docman_FilterListAdvanced($md);
                    } else {
                        $filter = new Docman_FilterList($md);
                    }
                    break;
            }
        }

        return $filter;
    }
}
