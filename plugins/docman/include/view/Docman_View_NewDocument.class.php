<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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


class Docman_View_NewDocument extends Docman_View_New
{

    public function _getTitle($params)
    {
        return dgettext('tuleap-docman', 'New document');
    }
    public function _getEnctype()
    {
        return ' enctype="multipart/form-data" ';
    }
    public function _getAction()
    {
        return 'createDocument';
    }
    public function _getActionText()
    {
        return dgettext('tuleap-docman', 'Create document');
    }

    public function _getSpecificProperties($params)
    {
        $html = '';
        $currentItemType = null;
        if (isset($params['force_item'])) {
            $currentItemType = Docman_ItemFactory::getItemTypeForItem($params['force_item']);
        }
        $specifics = array(
            array(
                'type'    =>  PLUGIN_DOCMAN_ITEM_TYPE_EMPTY,
                'label'   => dgettext('tuleap-docman', 'Empty document'),
                'obj'     => isset($params['force_item']) && ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_EMPTY) ? $params['force_item'] : new Docman_Empty(),
                'checked' => ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_EMPTY)
            ),
            array(
                'type'    =>  PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                'label'   => dgettext('tuleap-docman', 'Link'),
                'obj'     => isset($params['force_item']) && ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_LINK) ? $params['force_item'] : new Docman_Link(),
                'checked' => ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_LINK)
                ));
        $wikiAvailable = true;
        if (isset($params['group_id'])) {
            $pm = ProjectManager::instance();
            $go = $pm->getProject($params['group_id']);
            $wikiAvailable = $go->usesWiki();
        }
        if ($wikiAvailable) {
            $specifics[] = array(
                'type'    =>  PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
                'label'   => dgettext('tuleap-docman', 'Wiki Page'),
                'obj'     => isset($params['force_item']) && ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_WIKI) ? $params['force_item'] : new Docman_Wiki(),
                'checked' => ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_WIKI)
                );
        }

        $specifics[] = array(
                'type'    =>  PLUGIN_DOCMAN_ITEM_TYPE_FILE,
                'label'   => dgettext('tuleap-docman', 'File'),
                'obj'     => isset($params['force_item']) && ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_FILE) ? $params['force_item'] : new Docman_File(),
                'checked' => ($currentItemType !== null) ? ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_FILE) : true
                );

        if ($this->_controller->getProperty('embedded_are_allowed')) {
            $specifics[] = array(
                'type'    =>  PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
                'label'   => dgettext('tuleap-docman', 'Embedded File'),
                'obj'     => isset($params['force_item']) && ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE) ? $params['force_item'] : new Docman_EmbeddedFile(),
                'checked' => ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE)
            );
        }
        $get_specific_fields = new Docman_View_GetSpecificFieldsVisitor();

        foreach ($specifics as $specific) {
            $html .= '<div><label class="docman-create-doctype radio" for="item_item_type_' . $specific['type'] . '">';
            $html .= '<input type="radio" name="item[item_type]" value="' . $specific['type'] . '" id="item_item_type_' . $specific['type'] . '" ' . ($specific['checked'] ? 'checked="checked"' : '') . '/>';
            $html .= '<b>' . $specific['label'] . '</b></label></div>';
            $html .= '<div style="padding-left:20px" id="item_item_type_' . $specific['type'] . '_specific_properties">';
            $fields = $specific['obj']->accept($get_specific_fields, array('request' => $this->_controller->request));
            $html .= '<table>';
            foreach ($fields as $field) {
                $html .= '<tr style="vertical-align:top;"><td><label>' . $field->getLabel() . '</label></td><td>' . $field->getField() . '</td></tr>';
            }
            $html .= '</table>';
            $html .= '</div>';
        }
        return $html;
    }

    public function _getNewItem()
    {
        $i = new Docman_Document();
        return $i;
    }
}
