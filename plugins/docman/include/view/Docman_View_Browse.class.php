<?php
/**
 * Copyright © Enalean, 2011 - Present. All Rights Reserved.
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

use Tuleap\Docman\View\DocmanViewURLBuilder;

/* abstract */ class Docman_View_Browse extends Docman_View_Display
{

    /**
     * @access: protected
     */
    function _getTitle($params)
    {
        // If a title is defined in the report, use it instead of the  default one
        $title = $this->getTitleWhenFilterIsSet($params);
        if ($title === false) {
            return parent::_getTitle($params);
        }

        return $title;
    }

    protected function getUnconvertedTitle($params)
    {
        // If a title is defined in the report, use it instead of the
        // default one
        $title = $this->getTitleWhenFilterIsSet($params);
        if ($title === false) {
            return parent::getUnconvertedTitle($params);
        }

        return $title;
    }

    private function getTitleWhenFilterIsSet(array $params)
    {
        if (isset($params['filter']) || $params['filter'] === null) {
            return false;
        }

        if ($params['filter']->getTitle() === null && trim($params['filter']->getTitle()) === '') {
            return false;
        }

        return htmlentities($params['filter']->getTitle(), ENT_COMPAT, 'UTF-8');
    }

    /* protected */ function _mode($params)
    {
        $html = '';
        // No mode selector in printer version
        if (isset($params['pv']) && $params['pv'] > 0) {
            // Close table opened in method 'breadCrumbs' in 'Display' class.
            $html .= '</tr>';
            $html .= '</table>';
            echo $html;
            return;
        }

        $html .= '<td align="right">';
        $html .= '<form action="'. $params['default_url'] .'" method="POST">';
        $html .= '<span id="docman_browse_viewas">';
        $html .= $GLOBALS['Language']->getText('plugin_docman', 'browse_viewas') .' ';
        $actual = Docman_View_Browse::getViewForCurrentUser($params['group_id']);
        $views  = Docman_View_Browse::getDefaultViews();
        foreach ($views as $val => $view) {
            $html .= '<input type="image" 
            				 name="selected_view['. $val .']" 
            				 src="'. $this->_controller->plugin->getThemePath() .'/images/ic/view-'. $view .'.png" 
            				 title="'. $GLOBALS['Language']->getText('plugin_docman', 'browse_viewas') .' '. $GLOBALS['Language']->getText('plugin_docman', 'view_'. $view) .'"
            				 alt="'. $GLOBALS['Language']->getText('plugin_docman', 'view_'. $view) .'"
            				 /> ';
            //($actual == $val ? '&gt;&nbsp;' : '&nbsp;&nbsp;');
        }
        $html .= '</span>';
        $html .= '<input type="hidden" name="action" value="change_view" />';
        $html .= '<input type="hidden" name="id" value="'. $params['item']->getId() .'" />';
        $html .= '</form>';
        $html .= '</td>';

        // Close table opened in method 'breadCrumbs' in 'Display' class.
        $html .= '</tr>';
        $html .= '</table>';

        echo $html;
    }

    var $dfltParams = null;
    function _getDefaultUrlParams($params)
    {
        if ($this->dfltParams === null) {
            $this->dfltParams = array('action' => 'search',
                                      'id'     => $params['item']->getId());

            $this->_initSearchAndSortParams($params);
        }
        return array_merge($this->dfltParams, $this->dfltSearchParams, $this->dfltSortParams);
    }

    function _buildSearchUrl($params, $extraParams = array())
    {
        $parameters = array_merge($this->_getDefaultUrlParams($params), $extraParams);
        return DocmanViewURLBuilder::buildActionUrl($params['item'], $params, $parameters);
    }

    /**
     * @access: protected
     */
    function _filter($params)
    {
        $html = '';

        // No filter in printer version
        if (isset($params['pv']) && $params['pv'] > 0) {
            return;
        }

        $html .= "<!-- Filters -->\n";
        $html .= '<div class="docman_filters">';
        if (isset($params['filter']) && $params['filter'] !== null) {
            $htmlReport = new Docman_ReportHtml($params['filter'], $this, $params['default_url']);
            $html .= $htmlReport->toHtml($params);
        }
        $html .= "</div>\n";

        echo $html;
    }

    function getActionOnIconForFolder()
    {
        return 'show';
    }
    function getClassForFolderLink()
    {
        return '';
    }

    /* static */ function getItemClasses($params)
    {
        $li_classes = 'docman_item';
        if (isset($params['is_last']) && $params['is_last']) {
            $li_classes .= '_last';
        }
        return $li_classes;
    }

    public static function isViewAllowed($view)
    {
        //List is part of SOAP api
        return in_array($view, array_merge(array_keys(Docman_View_Browse::getDefaultViews()), array('List')));
    }
    /* static */ function getViewForCurrentUser($group_id, $report = '')
    {
        if ($report != '') {
            $pref = $report;
        } else {
            $pref = user_get_preference(PLUGIN_DOCMAN_VIEW_PREF .'_'. $group_id);
            if (!$pref) {
                $sBo = Docman_SettingsBo::instance($group_id);
                $pref = $sBo->getView();
            }
        }
        if (!$pref || !Docman_View_Browse::isViewAllowed($pref)) {
            $pref = 'Tree';
        }
        return $pref;
    }

    public static function getDefaultViews()
    {
        return array('Tree'   => 'Tree',
                     'Icons'  => 'Icons',
                     'Table'  => 'Table',
        );
    }
}
