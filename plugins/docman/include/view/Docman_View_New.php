<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

abstract class Docman_View_New extends Docman_View_Display /* implements Visitor */ //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public $newItem;

    /* protected abstract */ public function _getEnctype() //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /* protected abstract */ public function _getAction() //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /* protected abstract */ public function _getActionText() //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /* protected abstract */ public function _getForm() //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
    }

    /* protected */ public function _getSpecificProperties($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return '';
    }

    /* protected */ public function _getCategories($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return '';
    }

    /* protected */ #[Override]
    public function _getJSDocmanParameters($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $doc_params = [];
        if (isset($params['force_permissions'])) {
            $doc_params['newItem'] = [
                'hide_permissions'           => ! $params['display_permissions'],
                'update_permissions_on_init' => false,
                'default_position'           => $params['force_ordering'],
            ];
        }
        return array_merge(
            parent::_getJSDocmanParameters($params),
            $doc_params
        );
    }

    public function _getPropertiesFieldsDisplay($fields) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $html  = '';
        $html .= '<table>';
        foreach ($fields as $field) {
            $html .= '<tr>';
            $html .= '<td>' . $field->getLabel() . '</td>';
            $html .= '<td>' . $field->getField() . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    public function metadataToSkip()
    {
        $labels = ['owner'       => 'owner',
            'create_date' => 'create_date',
            'update_date' => 'update_date',
        ];
        return $labels;
    }

    public function _getNewItem() //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return null;
    }

    public function setupNewItem($params)
    {
        $mdFactory = new Docman_MetadataFactory($params['group_id']);

        if (isset($params['force_item'])) {
            $this->newItem = $params['force_item'];
        } else {
            $this->newItem = $this->_getNewItem();
            $this->newItem->setParentId($params['item']->getId());
            $this->newItem->setGroupId($params['group_id']);
            $mdFactory->appendItemMetadataList($this->newItem);

            // Get default values
            $mdFactory->appendDefaultValuesToItem($this->newItem);
        }

        // Append, for list Metadata the list of values associated in the DB
        // (content of select box)
        $mdFactory->appendAllListOfValuesToItem($this->newItem);
    }

    public function _getPropertiesFields($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $get_fields = new Docman_View_GetFieldsVisitor($this->metadataToSkip());
        $fields     = $this->newItem->accept($get_fields, ['form_name'  => $params['form_name'],
            'theme_path' => $params['theme_path'],
        ]);
        return $fields;
    }

    public function _getGeneralProperties($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $html   = '';
        $fields = $this->_getPropertiesFields($params);
        $html  .= $this->_getPropertiesFieldsDisplay($fields);
        $html  .= '<input type="hidden" name="item[parent_id]" value="' . $this->newItem->getParentId() . '" />';
        return $html;
    }

    public function _getGeneralPropertiesFieldset($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $html  = '';
        $html .= '<div class="properties">' . "\n";
        $html .= '<h3>' . dgettext('tuleap-docman', 'Properties') . '</h3>';
        $html .= $this->_getGeneralProperties($params);
        $html .= '<p><span class="highlight">' . dgettext('tuleap-docman', '* Mandatory field') . '</span></p>';
        $html .= '<input type="hidden" name="action" value="' . $this->_getAction() . '" />';
        $html .= '</div>';
        return $html;
    }

    public function _getDefaultValuesFieldset($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        return '';
    }

    public function _getSpecificPropertiesFieldset($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $html  = '';
        $html .= '<h3>' . dgettext('tuleap-docman', 'Document type') . '</h3>';
        $html .= $this->_getSpecificProperties($params);
        return $html;
    }

    public function _getLocationFieldset($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $html        = '';
        $html       .= '<h3>' . dgettext('tuleap-docman', 'Location') . '</h3>';
        $itemRanking = new Docman_View_ItemRanking();

        if (isset($params['ordering'])) {
            $itemRanking->setSelectedValue($params['ordering']);
            $itemRanking->setDropDownName('ordering');
        }

        $html .= $itemRanking->getDropDownWidget($params['item']);
        return $html;
    }

    public function _getPermissionsFieldset($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $html                      = '';
        $html                     .= '<h3>Permissions</h3>';
        $html                     .= '<div id="docman_new_permissions_panel">';
        $p                         = new Docman_View_PermissionsForItem($this->_controller);
        $params['user_can_manage'] = $this->_controller->userCanWrite($this->newItem->getParentId());
        $html                     .= $p->fetch($this->newItem->getParentId(), $params);
        $html                     .= '</div>';
        return $html;
    }

    #[Override]
    public function _content($params) //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $params['form_name'] = 'new_item';

        $this->setupNewItem($params);

        $html  = '';
        $html .= '<form name="' . $params['form_name'] . '" data-test="docman_new_form" action="' . $params['default_url'] . '" method="POST" ' . $this->_getEnctype() . ' class="docman_form">';

        $html .= '<div class="docman_new_item" data-test="docman_new_item">' . "\n";

        $html .= $this->_getGeneralPropertiesFieldset($params);
        $html .= $this->_getDefaultValuesFieldset($params);
        $html .= $this->_getSpecificPropertiesFieldset($params);
        $html .= $this->_getLocationFieldset($params);
        $html .= $this->_getPermissionsFieldset($params);

        $html .= '<div class="docman_new_submit">' . "\n";
        if (isset($params['token']) && $params['token']) {
            $html .= '<input type="hidden" name="token" value="' . $params['token'] . '" />';
        }
        $html .= '<input type="submit" data-test="docman_create" value="' . $this->_getActionText() . '" />';
        $html .= '<input type="submit" name="cancel" value="' . $GLOBALS['Language']->getText('global', 'btn_cancel') . '" />';
        $html .= '</div>' . "\n";

        $html .= '</div>' . "\n";

        $html .= '</form>';
        $html .= '<br />';
        echo $html;
    }
}
