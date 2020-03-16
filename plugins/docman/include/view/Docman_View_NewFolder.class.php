<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*
* Docman_View_NewFolder
*/

require_once('Docman_View_New.class.php');
require_once('Docman_View_GetFieldsVisitor.class.php');
require_once(dirname(__FILE__) . '/../Docman_MetadataFactory.class.php');

class Docman_View_NewFolder extends Docman_View_New
{

    public function _getTitle($params)
    {
        return dgettext('tuleap-docman', 'New folder');
    }

    public function _getAction()
    {
        return 'createFolder';
    }

    public function _getActionText()
    {
        return dgettext('tuleap-docman', 'Create folder');
    }

    public function _getNewItem()
    {
        $i = new Docman_Folder();
        return $i;
    }

    public function _getGeneralProperties($params)
    {
        $html = '';
        $html .= parent::_getGeneralProperties($params);
        $html .= '<input type="hidden" name="item[item_type]" value="' . PLUGIN_DOCMAN_ITEM_TYPE_FOLDER . '" />';
        return $html;
    }

    public function _getDefaultValuesFields($params)
    {
        $mdFactory = new Docman_MetadataFactory($this->newItem->getGroupId());
        $inheritableMda = $mdFactory->getInheritableMdLabelArray(true);

        $mdIter = $this->newItem->getMetadataIterator();

        $mdHtmlFactory = new Docman_MetadataHtmlFactory();
        return $mdHtmlFactory->buildFieldArray($mdIter, $inheritableMda, true, $params['form_name'], $params['theme_path']);
    }

    public function _getDefaultValuesFieldset($params)
    {
        $html = '';

        $html .= '<div class="properties">' . "\n";
        $html .= '<h3>' . dgettext('tuleap-docman', 'Default Values') . '</h3>';
        $html .= '<p>' . dgettext('tuleap-docman', 'Define the default properties values for the item that will be created within this folder.') . '</p>';
        $fields = $this->_getDefaultValuesFields($params);
        $html .= $this->_getPropertiesFieldsDisplay($fields);
        $html .= '</div>';

        return $html;
    }

    public function _getSpecificPropertiesFieldset($params)
    {
        return '';
    }
}
