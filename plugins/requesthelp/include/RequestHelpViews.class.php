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
        $GLOBALS['HTML']->header(array('title'=>$title));
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
            $params        = $data['params'];
            $this->displayForm($params);
        } else {
            $c->addInfo($GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_ticket_submission_success'));
            $c->redirect('/my/');
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
        if ($user->isLoggedIn()) {
            $type        = RequestHelp::TYPE_SUPPORT;
            $severity    = RequestHelp::SEVERITY_MINOR;
            $summary     = '';
            $description =  '';
            if (is_array($params)) {
                if (isset($params['type'])) {
                    $type = $params['type'];
                }
                if (isset($params['severity'])) {
                    $severity = $params['severity'];
                }
                if (isset($params['summary'])) {
                    $summary = $params['summary'];
                }
                if (isset($params['description'])) {
                    $description = $params['description'];
                }
            }
            $p = PluginManager::instance()->getPluginByName('requesthelp');
            echo '<form name="request" class="cssform" action="'.$p->getPluginPath().'/" method="post" enctype="multipart/form-data">
             <fieldset >
                 <table>
                 	<tr>
                 		<label><i>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_explain_label').'</i></label>
                 	    <br><br><br>
                 	</tr>
                 	<tr>';
            echo '<td><label>Type:</label>&nbsp;<span class="highlight"><big>*</big></b></span></td>
                     <td><select name="type">
                      <option value="'.RequestHelp::TYPE_SUPPORT.'" ';
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
            echo '</td><td><label>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'severity').':</label>&nbsp;<span class="highlight"><big>*</big></b></span></td>
                             <td><select name="severity">
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
            echo '<tr><td><label>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'summary').':</label>&nbsp;<span class="highlight"><big>*</big></b></span></td>
                     <td><input type="text" name="request_summary" value="'.$summary.'" /></td></tr>';
            echo '<tr><td><label><span class="totop">Description:</span></label>&nbsp;<span class="highlight"><span class="totop"><big>*</big></b></span></span></td><td><textarea name="request_description">'.$description.'</textarea></td></tr>
            <tr><td></td><td><i><b><u>Note</u>: </b>'.$GLOBALS['Language']->getText('plugin_requesthelp', 'requesthelp_cc_note').'</i></td></tr>
            <tr><td><label>CC :</label></td><td><input id="requesthelp_cc" type="text" name="cc" /></td></tr>
            <tr><td></td><td><input name="action" type="hidden" value="submit_ticket" /></td></tr>
            <tr><td></td><td><input name="submit" type="submit" value="Submit" /></td></tr>
                </table>
            </fieldset>
        </form>';
            $js = "new UserAutoCompleter('requesthelp_cc', '".util_get_dir_image_theme()."', true);";
            $GLOBALS['Response']->includeFooterJavascriptSnippet($js);
        }
    }
    // }}}
}

?>