<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */


require_once('common/widget/Widget.class.php');
require_once('common/user/UserManager.class.php');
require_once('Docman_ApprovalTableFactory.class.php');

/**
* Docman_Widget_MyDocman
*/
class Docman_Widget_MyDocman extends Widget {
    var $pluginPath;
    function Docman_Widget_MyDocman($pluginPath) {
        $this->Widget('plugin_docman_mydocman');
        $this->pluginPath = $pluginPath;
    }
    function getTitle() {
        return $GLOBALS['Language']->getText('plugin_docman', 'my_reviews');
    }
    
    function getContent() {
        $html = '';
        $html .= '<script type="text/javascript">';
        $html .= "
        function plugin_docman_approval_toggle(what, save) {
            if ($(what).visible()) {
                $(what+'_icon').src = '". util_get_dir_image_theme() ."pointer_right.png';
                $(what).hide();
                if (save) {
                    new Ajax.Request('/plugins/docman/?action='+what+'&hide=1');
                }
            } else {
                $(what+'_icon').src = '". util_get_dir_image_theme() ."pointer_down.png';
                $(what).show();
                if (save) {
                    new Ajax.Request('/plugins/docman/?action='+what+'&hide=0');
                }
            }
        }
        </script>";
        $html .= $this->_getReviews(true);
        $html .= $this->_getReviews(false);
        
        return $html;
    }
    function _getReviews($reviewer = true) {
        $hp = Codendi_HTMLPurifier::instance();
        require_once('www/my/my_utils.php');
        $html = '';
        
        $content_html_id = 'plugin_docman_approval_'. ($reviewer ? 'reviewer' : 'requester');
        $html .= '<div style="font-weight:bold;">';
        $html .= $GLOBALS['HTML']->getImage(
            'pointer_down.png', 
            array(
                'id' => $content_html_id .'_icon',
                'onclick' => "plugin_docman_approval_toggle('$content_html_id', true)"
            )
        ).' ';
        if ($reviewer) {
            $html .= $GLOBALS['Language']->getText('plugin_docman', 'my_reviews_reviewer');
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_docman', 'my_reviews_requester');
        }
        $html .= '</div>';
        $html .= '<div id="'. $content_html_id .'" style="padding-left:20px;">';
        
        $um =& UserManager::instance();
        $user =& $um->getCurrentUser();
        
        if($reviewer) {
            $reviewsArray = Docman_ApprovalTableReviewerFactory::getAllPendingReviewsForUser($user->getId());
        } else {
            $reviewsArray = Docman_ApprovalTableReviewerFactory::getAllApprovalTableForUser($user->getId());
        }

        if(count($reviewsArray) > 0) {
            $request =& HTTPRequest::instance();
            // Get hide arguments
            $hideItemId = (int) $request->get('hide_item_id');
            $hideApproval = null;
            if($request->exist('hide_plugin_docman_approval')) {
                $hideApproval = (int) $request->get('hide_plugin_docman_approval');
            }

            $prevGroupId = -1;
            $hideNow = false;
            $i = 0;

            $html .= '<table style="width:100%">';
            //$html .= '<TR><TD colspan="2">Reviewer - Requester</TD></TR>';
            foreach($reviewsArray as $review) {
                if($review['group_id'] != $prevGroupId) {
                    list($hideNow,$count_diff,$hideUrl) = 
                        my_hide_url('plugin_docman_approval',$review['group_id'], $hideItemId, 1, $hideApproval);
                    $docmanUrl = $this->pluginPath.'/?group_id='.$review['group_id'];
                    $docmanHref = '<a href="'.$docmanUrl.'">'.$review['group'].'</a>';
                    if($prevGroupId != -1) {
                        if($reviewer) {
                            $colspan = 2;
                        } else {
                            $colspan = 3;
                        }
                        $html .= '<tr class="boxitem"><td colspan="'.$colspan.'">';
                    }
                    $html .= '<strong>'.$hideUrl.$docmanHref.'</strong></td></tr>';
                    $i = 0;
                }

                if(!$hideNow) {
                    $html .= '<tr class="'. util_get_alt_row_color($i++).'">';
                    // Document
                    $html .= '<td align="left">';
                    $html .= '<a href="'.$review['url'].'">'. $hp->purify($review['title'], CODENDI_PURIFIER_CONVERT_HTML) .'</a>';
                    $html .= '</td>';

                    // For requester, precise the status
                    if(!$reviewer) {
                        $html .= '<td align="right">';
                        $html .= $review['status'];
                        $html .= '</td>';
                    }

                    // Date
                    $html .= '<td align="right">';
                    $html .= util_timestamp_to_userdateformat($review['date'], true);
                    $html .= '</td>';
                
                    $html .= '</tr>';
                }

                $prevGroupId = $review['group_id'];
            }
            $html .= '</table>';
        } else {
            if($reviewer) {
                $html .= $GLOBALS['Language']->getText('plugin_docman', 'my_no_review');
            } else {
                $html .= $GLOBALS['Language']->getText('plugin_docman', 'my_no_request');
            }
        }
        $html .= '</div>';
        if (user_get_preference('hide_plugin_docman_approval_'. ($reviewer ? 'reviewer' : 'requester'))) {
            $html .= '<script type="text/javascript">';
            $html .= "document.observe('dom:loaded', function() 
                {
                    plugin_docman_approval_toggle('$content_html_id', false);
                }
            );
            </script>";
        }
        return $html;
    }
    function isAjax() {
        return true;
    }
    function getCategory() {
        return 'plugin_docman';
    }
    
    function getDescription() {
        return $GLOBALS['Language']->getText('plugin_docman','widget_description_my_docman');
    }
}

?>