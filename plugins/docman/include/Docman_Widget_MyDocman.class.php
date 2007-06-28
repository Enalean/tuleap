<?php

require_once('common/widget/Widget.class.php');
require_once('common/include/UserManager.class.php');
require_once('Docman_ApprovalTableFactory.class.php');

/**
* Docman_Widget_MyDocman
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Docman_Widget_MyDocman extends Widget {
    var $pluginPath;
    function Docman_Widget_MyDocman($pluginPath) {
        $this->Widget('mydocman');
        $this->pluginPath = $pluginPath;
    }
    function getTitle() {
        return $GLOBALS['Language']->getText('plugin_docman', 'my_reviews');
    }
    
    function getContent() {
        require_once('www/my/my_utils.php');

        $html = '';

        $um =& UserManager::instance();
        $user =& $um->getCurrentUser();
        
        $atf = new Docman_ApprovalTableFactory(null);
        $reviewsArray = $atf->getAllPendingReviewsForUser($user->getId(), PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET);

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

            $html .= '<table style="width:100%">';
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
            $html .= '</table>';
        } else {
            $html .= $GLOBALS['Language']->getText('plugin_docman', 'my_no_doc');
        }
        return $html;
    }
}

?>