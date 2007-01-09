<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_New
*/

require_once('Docman_View_Display.class.php');
require_once('Docman_View_ParentsTree.class.php');
require_once('Docman_View_PermissionsForItem.class.php');

/* abstract */ class Docman_View_New extends Docman_View_Display /* implements Visitor */ {
    
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

    function _content($params) {
        $params['form_name'] = 'new_item';

        $html  = '<br />';
        $html .= '<form name="'.$params['form_name'].'" id="docman_new_form" action="'. $params['default_url'] .'" method="POST" '. $this->_getEnctype() .'>';                

        $html .= '<div class="docman_new_item">'."\n";

        //{{{ General Properties
        $html .= '<div class="properties">'."\n";
        $html .= '<fieldset class="general_properties"><legend>'. $GLOBALS['Language']->getText('plugin_docman', 'new_generalproperties') . help_button('DocumentManager') .'</legend>';
        $html .= $this->_getGeneralProperties($params);
        $html .= '<p><span class="highlight"><em>'.$GLOBALS['Language']->getText('plugin_docman', 'new_mandatory_help').'</em></span></p>';
        $html .= '<input type="hidden" name="action" value="'. $this->_getAction() .'" />';        
        $html .= '</fieldset>';
        $html .= '</div>';
        //}}}
        
        //{{{ Specific Properties
        $specific = $this->_getSpecificProperties($params);
        if (trim($specific)) {
            $html .= '<fieldset class="specific_properties"><legend>'. $GLOBALS['Language']->getText('plugin_docman', 'new_specificproperties') . help_button('DocumentManager') .'</legend>';
            $html .= $specific;
            $html .= '</fieldset>';
        }
        //}}}
        
        //{{{ Location
        $html .= '<fieldset class="location"><legend>'. $GLOBALS['Language']->getText('plugin_docman', 'new_location') . help_button('DocumentManager') .'</legend>';
        
        $potential_parent_id = isset($params['force_item']) ? $params['force_item']->getParentId() : $params['item']->getId();
        $potential_parent_id = $this->_controller->userCanWrite($potential_parent_id) ? $potential_parent_id : $params['hierarchy']->getId();
        
        $parents_tree =& new Docman_View_ParentsTree($this->_controller);
        $html .= $parents_tree->fetch(array(
            'docman_icons' => $this->_getDocmanIcons($params),
            'current'      => $potential_parent_id,
            'hierarchy'    => $params['hierarchy']
        ));
        
        $html .= '</fieldset>';
        //}}}        
        
        //{{{ Permissions
        $html .= '<fieldset><legend>Permissions</legend>';
        $html .= '<div id="docman_new_permissions_panel">';
        $p =& new Docman_View_PermissionsForItem($this->_controller);
        $params['user_can_manage'] = $this->_controller->userCanWrite($potential_parent_id);
        $html .= $p->fetch($potential_parent_id, $params);
        $html .= '</div>';
        $html .= '</fieldset>';
        //}}}
	
        //{{{ News
        $user =& $this->_controller->getUser();
        if ($user->isMember($params['item']->getGroupId(), 'A')) {
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
        //}}}	
        
        $html .= '<div class="docman_new_submit">'."\n";
        $html .= '<input type="submit" value="'. $this->_getActionText() .'" />';
        $html .= '</div>'."\n";

        $html .= '</div>'."\n"; // "docman_new_item"

        $html .= '</form>';
        echo $html;
    }
}

?>
