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
        
        $atf = new Docman_ApprovalTableFactory(null);
        if($reviewer) {
            $reviewsArray = $atf->getAllPendingReviewsForUser($user->getId());
        } else {
            $reviewsArray = $atf->getAllApprovalTableForUser($user->getId());
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
}

?>