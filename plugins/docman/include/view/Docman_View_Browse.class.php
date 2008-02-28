<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* 
*
* Docman_View_Browse
*/

require_once('Docman_View_Display.class.php');

require_once(dirname(__FILE__).'/../Docman_SettingsBo.class.php');
require_once(dirname(__FILE__).'/../Docman_ReportHtml.class.php');

/* abstract */ class Docman_View_Browse extends Docman_View_Display {
    
    /**
     * @access: protected
     */
    function _getTitle($params) {
        // If a title is defined in the report, use it instead of the
        // default one
        if(isset($params['filter']) && $params['filter'] !== null) {
            if($params['filter']->getTitle() !== null && trim($params['filter']->getTitle()) != '') {
                return htmlentities($params['filter']->getTitle());
            }
        } 
        return parent::_getTitle($params);
    }

    /* protected */ function _mode($params) {
        $html = '';
        // No mode selector in printer version
        if(isset($params['pv']) && $params['pv'] > 0) {
            // Close table opened in method 'breadCrumbs' in 'Display' class.
            $html .= '</tr>';
            $html .= '</table>';
            echo $html;
            return;
        }

        $html .= '<td align="right">';
        $html .= '<form action="'. $params['default_url'] .'" method="POST">';
        $html .= '<select name="selected_view" onchange="this.form.submit()" style="font-family:monospace;">';
        $html .= '<option value="-1">'. $GLOBALS['Language']->getText('plugin_docman', 'browse_viewas') .'</option>';
        $actual = Docman_View_Browse::getViewForCurrentUser($params['group_id']);
        $views  = Docman_View_Browse::getDefaultViews();
        foreach($views as $val => $view) {
            $html .= '<option value="'. $val .'">'. ($actual == $val ? '&gt;&nbsp;' : '&nbsp;&nbsp;');
            $html .= $GLOBALS['Language']->getText('plugin_docman', 'view_'.$view);
            $html .= '</option>';
        }
        $html .= '</select>';
        $html .= '<input type="hidden" name="action" value="change_view" />';
        $html .= '<input type="hidden" name="id" value="'. $params['item']->getId() .'" />';
        $html .= '<noscript><input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></noscript>';
        $html .= '</form>';
        $html .= '</td>';

        // Close table opened in method 'breadCrumbs' in 'Display' class.
        $html .= '</tr>';
        $html .= '</table>';

        echo $html;
    }

    /**
     * 
     */
    var $dfltParams = null;
    function _getDefaultUrlParams($params) {
        if($this->dfltParams === null) {
            $this->dfltParams = array('action' => 'search',
                                      'id'     => $params['item']->getId());

            $this->_initSearchAndSortParams($params);
        }
        return array_merge($this->dfltParams, $this->dfltSearchParams, $this->dfltSortParams);
    }

    /**
     * 
     */
    function _buildSearchUrl($params, $extraParams = array()) {
        $parameters = array_merge($this->_getDefaultUrlParams($params), $extraParams);
        return $this->buildActionUrl($params, $parameters);
    }
        
    /**
     * @access: protected
     */
    function _filter($params) {
        $html = '';

        // No filter in printer version
        if(isset($params['pv']) && $params['pv'] > 0) {
            return;
        }

        $html .= "<!-- Filters -->\n";
        $html .= '<div class="docman_filters">';
        if(isset($params['filter']) && $params['filter'] !== null) {
            $htmlReport = new Docman_ReportHtml($params['filter'], $this, $params['default_url']);
            $html .= $htmlReport->toHtml($params);
        }
        $html .= "</div>\n";

        echo $html;
    }

    function getActionOnIconForFolder() {
        return 'show';
    }
    function getClassForFolderLink() {
        return '';
    }
    
    /* static */ function getItemClasses($params) {
        $li_classes = 'docman_item';
        if (isset($params['is_last']) && $params['is_last']) {
            $li_classes .= '_last';
        }
        return $li_classes;
    }
    /* static */ function isViewAllowed($view) {
        //List is part of SOAP api
        return in_array($view, array_merge(array_keys(Docman_View_Browse::getDefaultViews()), array('List')));
    }
    /* static */ function getViewForCurrentUser($group_id, $report = '') {
        if($report != '') {
            $pref = $report;
        }
        else {
            $pref = user_get_preference(PLUGIN_DOCMAN_VIEW_PREF .'_'. $group_id);
            if (!$pref) {
                $sBo =& Docman_SettingsBo::instance($group_id);
                $pref = $sBo->getView();            
            }
        }
        if (!$pref || !Docman_View_Browse::isViewAllowed($pref)) {
            $pref = 'Tree';
        }
        return $pref;
    }
    
    /**
     * @access: static
     */
    function getDefaultViews() { 
        return array('Tree'   => 'Tree',
                     'Icons'  => 'Icons',
                     'Table'  => 'Table');
    }
}

?>
