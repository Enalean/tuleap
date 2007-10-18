<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
*
* 
*
* Docman_View_New
*/

require_once('Docman_View_Display.class.php');
require_once('Docman_View_ItemRanking.class.php');
require_once('Docman_View_PermissionsForItem.class.php');

/* abstract */ class Docman_View_New extends Docman_View_Display /* implements Visitor */ {
    var $newItem;

    /* protected abstract */ function _getEnctype() {
    }

    /* protected abstract */ function _getAction() {
    }

    /* protected abstract */ function _getActionText() {
    }

    /* protected abstract */ function _getForm() {
    }

    /* protected */ function _getSpecificProperties($params) {
        return '';
    }

    /* protected */ function _getCategories($params) {
        return '';
    }
    /* protected */ function _getJSDocmanParameters($params) {
        $doc_params = array();
        if (isset($params['force_permissions'])) {
            $doc_params['newItem'] = array(
               'hide_permissions'           => !$params['display_permissions'],
                'hide_news'                  => !$params['display_news'],
                'update_permissions_on_init' => false,
                'default_position'           => $params['force_ordering']
            );
        }
        return array_merge(
            parent::_getJSDocmanParameters($params),
            $doc_params
        );
    }

    function _getPropertiesFieldsDisplay($fields) {
        $html = '';
        $html .= '<table>';
        foreach($fields as $field) {
            $html .= '<tr>';
            $html .= '<td>'.$field->getLabel().'</td>';
            $html .= '<td>'.$field->getField().'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    function metadataToSkip() {
        $labels = array('owner'       => 'owner',
                        'create_date' => 'create_date',
                        'update_date' =>'update_date');
        return $labels;
    }

    function _getNewItem() {
        return null;
    }

    function setupNewItem($params) {
        $mdFactory = new Docman_MetadataFactory($params['group_id']);

        if(isset($params['force_item'])) {
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

    function _getPropertiesFields($params) {
        $get_fields = new Docman_View_GetFieldsVisitor($this->metadataToSkip());
        $fields = $this->newItem->accept($get_fields, array('form_name'  => $params['form_name'],
                                                            'theme_path' => $params['theme_path']));
        return $fields;
    }

    function _getGeneralProperties($params) {
        $html = '';
        $fields = $this->_getPropertiesFields($params);
        $html .= $this->_getPropertiesFieldsDisplay($fields);
        $html .= '<input type="hidden" name="item[parent_id]" value="'.$this->newItem->getParentId().'" />';
        return $html;
    }

    function _getGeneralPropertiesFieldset($params) {
        $html = '';
        $html .= '<div class="properties">'."\n";
        $html .= '<fieldset class="general_properties"><legend>'. $GLOBALS['Language']->getText('plugin_docman', 'new_generalproperties') .'</legend>';
        $html .= $this->_getGeneralProperties($params);
        $html .= '<p><span class="highlight">'.$GLOBALS['Language']->getText('plugin_docman', 'new_mandatory_help').'</span></p>';
        $html .= '<input type="hidden" name="action" value="'. $this->_getAction() .'" />';
        $html .= '</fieldset>';
        $html .= '</div>';
        return $html;
    }

    function _getDefaultValuesFieldset($params) {
        return '';
    }

    function _getSpecificPropertiesFieldset($params) {
        $html = '';
        $html .= '<fieldset class="specific_properties"><legend>'. $GLOBALS['Language']->getText('plugin_docman', 'new_specificproperties') .'</legend>';
        $html .= $this->_getSpecificProperties($params);
        $html .= '</fieldset>';
        return $html;
    }

    function _getLocationFieldset($params) {
        $html = '';
        $html .= '<fieldset class="location"><legend>'. $GLOBALS['Language']->getText('plugin_docman', 'new_location') .'</legend>';
        $itemRanking = new Docman_View_ItemRanking();
        $itemRanking->setSelectedValue($params['ordering']);
        $itemRanking->setDropDownName('ordering');
        $html .= $itemRanking->getDropDownWidget($params['item']);
        $html .= '</fieldset>';
        return $html;
    }

    function _getPermissionsFieldset($params) {
        $html = '';
        $html .= '<fieldset><legend>Permissions</legend>';
        $html .= '<div id="docman_new_permissions_panel">';
        $p =& new Docman_View_PermissionsForItem($this->_controller);
        $params['user_can_manage'] = $this->_controller->userCanWrite($this->newItem->getParentId());
        $html .= $p->fetch($this->newItem->getParentId(), $params);
        $html .= '</div>';
        $html .= '</fieldset>';
        return $html;
    }

    function _getNewsFieldset($params) {
        $html = '';
        $user =& $this->_controller->getUser();
        if ($user->isMember($params['item']->getGroupId(), 'A') || $user->isMember($params['item']->getGroupId(), 'N1') || $user->isMember($params['item']->getGroupId(), 'N2')) {
            $default_news_summary = '';
            $default_news_details = '';
            $default_news_private_check = '';
            $default_news_public_check = 'checked="checked"';
            if (isset($params['force_news'])) {
                $default_news_summary = isset($params['force_news']['summary']) ? $params['force_news']['summary'] : $default_news_summary;
                $default_news_details = isset($params['force_news']['details']) ? $params['force_news']['details'] : $default_news_details;
                if (isset($params['force_news']['is_private']) && $params['force_news']['is_private']) {
                    $default_news_private_check = $default_news_public_check;
                    $default_news_public_check = '';
                }
            }
            $html .= '<fieldset><legend>News</legend>';
            $html .= '<div id="docman_new_news_panel">';

            $html .= '<p>'. $GLOBALS['Language']->getText('plugin_docman', 'new_news_letblank') .'</p>';

            $html .= '<div>';
            $html .= '<b><label for="news_summary">'. $GLOBALS['Language']->getText('news_admin_index','subject') .':</label></b><br />';
            $html .= '<input type="text" name="news[summary]" id="news_summary" value="'. htmlentities($default_news_summary, ENT_QUOTES) .'" size="44" maxlength="60" /><br />';
            $html .= '</div>';

            $html .= '<div>';
            $html .= '<b><label for="news_details">'. $GLOBALS['Language']->getText('news_admin_index','details') .':</label></b><br />';
            $html .= '<textarea name="news[details]" rows="8" cols="50" wrap="soft">'. htmlentities($default_news_details, ENT_QUOTES) .'</textarea><br />';
            $html .= '</div>';

            $html .= '<table><tr style="vertical-align:top"><td><b>'. $GLOBALS['Language']->getText('news_submit','news_privacy') .'</b></td><td>';
            $html .= '<input type="radio" name="news[is_private]" id="news_is_private_no" value="0" '. $default_news_public_check .' />';
            $html .= '<label for="news_is_private_no">'. $GLOBALS['Language']->getText('news_submit','public_news') .'</label><br />';
            $html .= '<input type="radio" name="news[is_private]" id="news_is_private_yes" value="1" '. $default_news_private_check .' />';
            $html .= '<label for="news_is_private_yes">'. $GLOBALS['Language']->getText('news_submit','private_news') .'</label>';
            $html .= '</td></tr></table>';

            $html .= '</div>';
            $html .= '</fieldset>';
        }
        return $html;
    }

    function _content($params) {
        $params['form_name'] = 'new_item';

        $this->setupNewItem($params);

        $html  = '<br />';
        $html .= '<form name="'.$params['form_name'].'" id="docman_new_form" action="'. $params['default_url'] .'" method="POST" '. $this->_getEnctype() .' class="docman_form">';

        $html .= '<div class="docman_new_item">'."\n";

        $html .= $this->_getGeneralPropertiesFieldset($params);
        $html .= $this->_getDefaultValuesFieldset($params);
        $html .= $this->_getSpecificPropertiesFieldset($params);
        $html .= $this->_getLocationFieldset($params);
        $html .= $this->_getPermissionsFieldset($params);
        $html .= $this->_getNewsFieldset($params);

        $html .= '<div class="docman_new_submit">'."\n";
        if (isset($params['token']) && $params['token']) {
            $html .= '<input type="hidden" name="token" value="'. $params['token'] .'" />';
        }
        $html .= '<input type="submit" value="'. $this->_getActionText() .'" />';
        $html .= '<input type="submit" name="cancel" value="'. $GLOBALS['Language']->getText('global', 'btn_cancel') .'" />';
        $html .= '</div>'."\n";

        $html .= '</div>'."\n"; // "docman_new_item"

        $html .= '</form>';
        $html .= '<br />';
        echo $html;
    }
}

?>
