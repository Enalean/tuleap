<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('mvc/PluginView.class.php');
require_once('common/include/HTTPRequest.class.php');

/**
 * RequestHelpViews
 */
class RequestHelpViews extends PluginView {

    /**
     * Display header
     *
     * @return void
     */
    function header() {
        $title = $GLOBALS['Language']->getText('plugin_requesthelp', 'title');
        $GLOBALS['HTML']->header(array('title'=>$title, 'selected_top_tab' => 'site'));
        include($GLOBALS['Language']->getContent('help/site'));
    }

    /**
     * Display footer
     *
     * @return void
     */
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }

    // {{{ Views
    /**
    * Redirect after ticket submission
    *
    * @return void
    */
    function remedyPostSubmission() {
        $c = $this->getController();
        $data = $c->getData();
        $requestStatus = $data['status'];
        if (!$requestStatus) {
            $params = $data['params'];
            $this->displayForm($params);
        } else {
            $this->displayForm();
        }
    }

    /**
     * Display form to fill a request
     *
     * @param Array $params params of the hook
     *
     * @return Void
     */
    function displayForm($params = null) {
        $um = UserManager::instance();
        $user = $um->getCurrentUser();
        $ignoreLabs = $this->getController()->getPlugin()->getProperty('ignore_labs');
        if ($user->isLoggedIn() && ($ignoreLabs || $user->useLabFeatures())) {
            $type        = RequestHelp::TYPE_SUPPORT;
            $severity    = RequestHelp::SEVERITY_MINOR;
            $summary     = '';
            $description =  $GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_default_description');
            $cc = '';
            if (is_array($params)) {
                $valid = new Valid_UInt();
                if (isset($params['type']) && $valid->validate($params['type'])) {
                    $type = $params['type'];
                }
                if (isset($params['severity']) && $valid->validate($params['severity'])) {
                    $severity = $params['severity'];
                }
                $valid = new Valid_String();
                if (isset($params['summary']) && $valid->validate($params['summary'])) {
                    $summary = $params['summary'];
                }
                $valid = new Valid_Text();
                if (isset($params['description']) && $valid->validate($params['description'])) {
                    $description = $params['description'];
                }
                $valid = new Valid_String();
                if (isset($params['cc']) && $valid->validate($params['cc'])) {
                    $cc = $params['cc'];
                }
            }
            $p = PluginManager::instance()->getPluginByName('requesthelp');
             echo '<fieldset class="requesthelp_fieldset">
             <legend><b>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_explain_label').'</b></legend>
             <form name="request" class="requesthelp_cssform" action="'.$p->getPluginPath().'/" method="post" enctype="multipart/form-data">
                 <table>
                     <tr>';
            echo '<td><b><a class="tooltip" href="#" title="'.$GLOBALS['Language']->getText('plugin_requesthelp', 'tooltip_type').
                 '">Type:</a></b>&nbsp;<span class="highlight"><big>*</big></b></span></td><td><select name="type"><option value="'.RequestHelp::TYPE_SUPPORT.'" ';
            if ($type == RequestHelp::TYPE_SUPPORT) {
                echo 'selected';
            }
            echo '>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'Support_request').'</option>
                         <option value="'.RequestHelp::TYPE_ENHANCEMENT.'" ';
            if ($type == RequestHelp::TYPE_ENHANCEMENT) {
                echo 'selected';
            }
            echo '>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'Enhancement_request').'</option>
                     </select>';
            echo '</td><td align="right"><b><a class="tooltip" href="#" title="'.$GLOBALS['Language']->getText('plugin_requesthelp', 'tooltip_severity').'">'.
                 $GLOBALS['Language']->getText('plugin_requesthelp', 'severity').':</a></b>&nbsp;<span class="highlight"><big>*</big></b></span>
                             <select name="severity">
                             <option value="'.RequestHelp::SEVERITY_MINOR.'" ';
            if ($severity == RequestHelp::SEVERITY_MINOR) {
                echo 'selected';
            }
            echo '>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'Minor').'</option>
                             <option value="'.RequestHelp::SEVERITY_SERIOUS.'" ';
            if ($severity == RequestHelp::SEVERITY_SERIOUS) {
                echo 'selected';
            }
            echo '>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'Serious').'</option>
                             <option value="'.RequestHelp::SEVERITY_CRITICAL.'" ';
            if ($severity == RequestHelp::SEVERITY_CRITICAL) {
                echo 'selected';
            }
            echo '>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'Critical').'</option>
                             </select>
                         </td>
                     </tr>';
            echo '<tr><td><b><a class="tooltip" href="#" title="'.$GLOBALS['Language']->getText('plugin_requesthelp', 'tooltip_summary').'">'.$GLOBALS['Language']->getText('plugin_requesthelp', 'summary').
                 ':</a></b>&nbsp;<span class="highlight"><big>*</big></span></td>
                     <td colspan="3"><input type="text" name="request_summary" value="'.$summary.'" /></td></tr>';
            echo '<tr><td><b><a class="tooltip" href="#" title="'.$GLOBALS['Language']->getText('plugin_requesthelp', 'tooltip_description').'"><span class="requesthelp_totop">Description:</span></a></b>&nbsp;<span class="highlight"><span class="requesthelp_totop"><big>*</big></b></span></span></td><td  colspan="3"><textarea id="request_description" name="request_description">'.$description.'</textarea></td></tr>
            <tr><td></td><td colspan="3"><i><b><u>Note</u>: </b>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_cc_note').'</i></td></tr>
            <tr><td><label>CC :</label></td><td  colspan="3"><input id="requesthelp_cc" type="text" name="cc" value="'.$cc.'" /></td></tr>
            <tr><td><input name="action" type="hidden" value="submit_ticket" /></td><td><input name="submit" type="submit" value="Submit" /></td></tr>
                </table>
            </form>
        </fieldset>';
            $js = "$('request_description').defaultValueActsAsHint();
                   options = new Array();
                   options['defaultValueActsAsHint'] = false;
                   new UserAutoCompleter('requesthelp_cc', '".util_get_dir_image_theme()."', true, options);";
            $GLOBALS['Response']->includeFooterJavascriptSnippet($js);
        }
    }
    // }}}
}

?>