<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
require_once('DocmanController.class.php');
require_once('DocmanActions.class.php');
class Docman extends DocmanController {

    function Docman(&$plugin, $pluginPath, $themePath) {
        $this->DocmanController($plugin, $pluginPath, $themePath, HTTPRequest::instance());
    }


    /* protected */ function _checkBrowserCompliance() {
        if($this->request->browserIsNetscape4()) {
            $this->feedback->log('warning', $GLOBALS['Language']->getText('plugin_docman', 'docman_browserns4'));
        }
    }

    /* protected */ function _includeView() {
        $className = 'Docman_View_'. $this->view;
        require_once('view/'. $className .'.class.php');
        return $className;
    }
    /* protected */ function _set_deleteView_errorPerms() {
        $this->view = 'Details';
    }
    /* protected */ function _set_redirectView() {
        if ($redirect_to = Docman_Token::retrieveUrl($this->request->get('token'))) {
            $this->_viewParams['redirect_to'] = $redirect_to;
        }
        $this->view = 'RedirectAfterCrud';
    }
    /* protected */ function _setView($view) {
    	   $this->view = $view;
    }
    /* protected */ function _set_moveView_errorPerms() {
        $this->view = 'Details';
    }
    /* protected */ function _set_createItemView_errorParentDoesNotExist(&$item, $get_show_view) {
    	   $this->view = $item->accept($get_show_view, $this->request->get('report'));
    }
    /* protected */ function _set_createItemView_afterCreate($view) {
        if ($view == 'createFolder') {
            $this->view = 'NewFolder';
        } else {
            $this->view = 'NewDocument';
        }
    }

    function displayMyPageBox() {
        require_once('www/my/my_utils.php');

        $html = '';

        $html .= $GLOBALS['HTML']->box1_top($this->txt('my_reviews'), 0);

        $user =& $this->getUser();
        $atf = new Docman_ApprovalTableFactory(null);
        $reviewsArray = $atf-> getAllPendingReviewsForUser($user->getId(), PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);

        if(count($reviewsArray) > 0) {
            // Get hide arguments
            $request =& HTTPRequest::instance();
            $hideItemId = (int) $request->get('hide_item_id');
            $hideApproval = null;
            if($request->exist('hide_plugin_docman_approval')) {
                $hideApproval = (int) $request->get('hide_plugin_docman_approval');
            }

            $prevGroupId = -1;
            $hideNow = false;
            $i = 0;

            //$html .= '<TR><TD colspan="2">Reviewer - Requester</TD></TR>';
            foreach($reviewsArray as $review) {
                if($review['group_id'] != $prevGroupId) {
                    list($hideNow,$count_diff,$hideUrl) = 
                        my_hide_url('plugin_docman_approval',$review['group_id'], $hideItemId, 1, $hideApproval);
                    $docmanUrl = $this->pluginPath.'/?group_id='.$review['group_id'];
                    $docmanHref = '<a href="'.$docmanUrl.'">'.$review['group'].'</a>';
                    if($prevGroupId != -1) {
                        $html .= '<tr class="boxitem"><td colspan="2">';
                    }
                    $html .= '<strong>'.$hideUrl.$docmanHref.'</strong></td></tr>';
                    $i = 0;
                }

                if(!$hideNow) {
                    $html .= '<tr class="'. util_get_alt_row_color($i++).'">';
                    // Document
                    $html .= '<td align="left">';
                    $html .= '<a href="'.$review['url'].'">'.$review['title'].'</a>';
                    $html .= '</td>';
                
                    // Date
                    $html .= '<td align="right">';
                    $html .= util_timestamp_to_userdateformat($review['date'], true);
                    $html .= '</td>';
                
                    $html .= '</tr>';
                }

                $prevGroupId = $review['group_id'];
            }
        } else {
            $html .= $this->txt('my_no_doc').'</td></tr>';
        }

        $html .= '<td><td colspan="2">&nbsp;</td></tr>';
        $html .= $GLOBALS['HTML']->box1_bottom(0);
        
        echo $html;
    }
}

?>