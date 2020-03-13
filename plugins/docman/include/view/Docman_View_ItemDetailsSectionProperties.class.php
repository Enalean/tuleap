<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Docman\View\DocmanViewURLBuilder;

class Docman_View_ItemDetailsSectionProperties extends Docman_View_ItemDetailsSection
{
    public $user_can_write;
    public $force;
    public $theme_path;
    public $formName;
    public $inheritableMetadataArray;

    public function __construct($item, $url, $theme_path, $user_can_write = false, $force = null)
    {
        $this->user_can_write = $user_can_write;
        $this->force = $force;
        $this->theme_path = $theme_path;
        $this->formName = '';
        $this->inheritableMetadataArray = null;
        $id = 'properties';
        $title = dgettext('tuleap-docman', 'Properties');
        parent::__construct($item, $url, $id, $title);
    }

    public function _getPropertyRow($label, $value)
    {
        $html = '';
        $html .= '<tr style="vertical-align:top;">';
        $html .= '<td class="label">' . $label . '</td>';
        $html .= '<td class="value">' . $value . '</td>';
        $html .= '</tr>';
        return $html;
    }

    public function _getPropertyField($field)
    {
        return $this->_getPropertyRow(
            $this->_getFieldLabel($field),
            $this->_showField($field)
        );
    }

    public function _getDefaultValuePropertyField($field)
    {
        return $this->_getPropertyRow(
            $this->_getFieldLabel($field),
            $this->_showField($field)
        );
    }

    public function _getItemIdField()
    {
        return "<input type='hidden' value='" . $this->item->getId() . "' data-test='docman_root_id'>" .
            $this->_getPropertyRow(
                'Id:',
                $this->item->getId()
            );
    }

    public function _getDirectLinkField()
    {
        $html = '';
        $itemFactory = new Docman_ItemFactory();
        if ($itemFactory->getItemTypeForItem($this->item) != PLUGIN_DOCMAN_ITEM_TYPE_FOLDER) {
            $dpm = Docman_PermissionsManager::instance($this->item->getGroupId());
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
            $href = '';
            if (!$this->item->isObsolete() || ($this->item->isObsolete() && $dpm->userCanAdmin($user))) {
                $url = DocmanViewURLBuilder::buildActionUrl(
                    $this->item,
                    ['default_url' => $this->url],
                    ['action' => 'show', 'id' => $this->item->getId()]
                );
                $href = '<a href="' . $url . '">' . dgettext('tuleap-docman', 'Click to open the document') . '</a>';
            }
            $html = $this->_getPropertyRow(
                dgettext('tuleap-docman', 'Direct link:'),
                $href
            );
        }
        return $html;
    }

    public function _getPropertiesFields($params)
    {
        $html = '';

        // Lock details
        $html .= $this->_getlockInfo();

        $params['theme_path'] = $this->theme_path;
        $get_fields = new Docman_View_GetFieldsVisitor();
        $fields = $this->item->accept($get_fields, $params);

        $html .= '<table class="docman_item_details_properties">';

        // Item Id
        $html .= $this->_getItemIdField();

        // Item properties
        foreach ($fields as $field) {
            $html .= $this->_getPropertyField($field);
        }

        // Item link
        $html .= $this->_getDirectLinkField();

        $html .= '</table>';
        return $html;
    }

    public function getContent($params = [])
    {
        $html  = '';

        $defaultValuesToManage = false;
        if (is_a($this->item, 'Docman_Folder') && count($this->_getInheritableMetadata()) > 0) {
            $defaultValuesToManage = true;
        }

        if ($defaultValuesToManage) {
            $html .= '<h3>' . dgettext('tuleap-docman', 'Folder Properties') . '</h3>';
        }
        $html .= $this->_getPropertiesFields($params);
        if ($defaultValuesToManage) {
            $html .= $this->_getDefaultValuesFields();
        }

        $html .= $this->_getAdditionalRows();
        return $html;
    }

    public function _getFieldLabel($field)
    {
        return $field->getLabel(false);
    }

    public function _showField($field)
    {
        return $field->getValue();
    }

    public function _getAdditionalRows()
    {
        $html = '';

        if ($this->user_can_write) {
            $html .= '<p><a href="' . $this->url . '&amp;action=edit&amp;id=' . $this->item->getid() . '">' . dgettext('tuleap-docman', 'Edit properties') . '</a></p>';
        }
        return $html;
    }

    public function _getInheritableMetadata()
    {
        if ($this->inheritableMetadataArray === null) {
            $mdFactory = new Docman_MetadataFactory($this->item->getGroupId());
            $inheritableMda = $mdFactory->getInheritableMdLabelArray(true);

            $mdIter = $this->item->getMetadataIterator();

            $mdHtmlFactory = new Docman_MetadataHtmlFactory();
            $this->inheritableMetadataArray = $mdHtmlFactory->buildFieldArray($mdIter, $inheritableMda, true, $this->formName, $this->theme_path);
        }
        return $this->inheritableMetadataArray;
    }

    public function _getDefaultValuesTableHeader()
    {
        return '';
    }

    public function _getDefaultValues()
    {
        $html = '';
        $fields = $this->_getInheritableMetadata();
        $html .= '<table class="docman_item_details_properties">';
        $html .= $this->_getDefaultValuesTableHeader();
        foreach ($fields as $field) {
            $html .= $this->_getDefaultValuePropertyField($field);
        }
        $html .= '</table>';
        return $html;
    }

    public function _getDefaultValuesFields()
    {
        $html = '';
        $html .= '<h3>' . dgettext('tuleap-docman', 'Default Values') . '</h3>';
        $html .= '<p>' . dgettext('tuleap-docman', 'Define the default properties values for the item that will be created within this folder.') . '</p>';
        $html .= $this->_getDefaultValues();
        return $html;
    }

    public function _getlockInfo()
    {
        $html = '';
        $dpm = Docman_PermissionsManager::instance($this->item->getGroupId());
        if ($dpm->getLockFactory()->itemIsLocked($this->item)) {
            $lockInfos = $dpm->getLockFactory()->getLockInfoForItem($this->item);
            $locker = UserHelper::instance()->getLinkOnUserFromUserId($lockInfos['user_id']);
            $lockDate = format_date($GLOBALS['Language']->getText('system', 'datefmt'), $lockInfos['lock_date']);
            $html .= '<p>';
            $html .= sprintf(dgettext('tuleap-docman', '%1$s <strong>locked</strong> this document on %2$s.'), $locker, $lockDate);
            if (!$this->user_can_write) {
                $html .= dgettext('tuleap-docman', 'You cannot modify it until the lock owner or a document manager release the lock.');
            }
            $html .= '</p>';
        }
        return $html;
    }
}
