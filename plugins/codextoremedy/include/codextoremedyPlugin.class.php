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

require_once('common/plugin/Plugin.class.php');
require_once('common/system_event/SystemEvent.class.php');
require_once('CodexToRemedy.class.php');

class codextoremedyPlugin extends Plugin {

    /**
     * Constructor
     *
     * @param Integer $id id of the plugin
     *
     * @return void
     */
    function codextoremedyPlugin($id) {
        $this->Plugin($id);
        $this->_addHook('cssfile', 'cssFile', false);
        $this->_addHook('site_help', 'displayForm', false);
    }

    /**
     * Retrieve plugin info
     *
     * @return CodexToRemedyPluginInfo
     */
    function getPluginInfo() {
        if (!$this->pluginInfo instanceof CodexToRemedyPluginInfo) {
            include_once('CodexToRemedyPluginInfo.class.php');
            $this->pluginInfo = new CodexToRemedyPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /**
     * Launch the controller
     *
     * @return void
     */
    function process() {
        $controler = new CodexToRemedy();
        $controler->process();
    }

     /**
     * Set the right style sheet for CodexToRemedy form
     *
     * @param Array $params params of the hook
     *
     * @return void
     */
    function cssFile($params) {
        echo '<link rel="stylesheet" type="text/css" href="'.$this->getThemePath().'/css/style.css" />'."\n";
    }

    /**
     * Display form to fill a request
     *
     * @param Array $params params of the hook
     *
     * @return Void
     */
    function displayForm($params) {
        $um = UserManager::instance();
        $user = $um->getCurrentUser();
        if ($user->isLoggedIn()) {
            echo '<form name="request" class="cssform" action="'.$this->getPluginPath().'/" method="post" enctype="multipart/form-data">
             <fieldset >
                 <table>
                     <tr>';
            echo '<td><label>Type:</label></td>
                     <td><select name="type">
                      <option value="'.CodexToRemedy::TYPE_SUPPORT.'">'.$GLOBALS['Language']->getText('plugin_codextoremedy', 'Support_request').'</option>
                         <option value="'.CodexToRemedy::TYPE_ENHANCEMENT.'">'.$GLOBALS['Language']->getText('plugin_codextoremedy', 'Enhancement_request').'</option>
                     </select>';
            echo '</td><td><label>'.$GLOBALS['Language']->getText('plugin_codextoremedy', 'severity').':</label></td>
                             <td><select name="severity">
                             <option value="'.CodexToRemedy::SEVERITY_MINOR.'">'.$GLOBALS['Language']->getText('plugin_codextoremedy', 'Minor').'</option>
                             <option value="'.CodexToRemedy::SEVERITY_SERIOUS.'">'.$GLOBALS['Language']->getText('plugin_codextoremedy', 'Serious').'</option>
                             <option value="'.CodexToRemedy::SEVERITY_CRITICAL.'">'.$GLOBALS['Language']->getText('plugin_codextoremedy', 'Critical').'</option>
                             </select>
                         </td>
                     </tr>';
            echo '<tr><td><label>'.$GLOBALS['Language']->getText('plugin_codextoremedy', 'summary').':</label></td>
                     <td><input type="text" name="request_summary" /></td></tr>';
            echo '<tr><td><label style="top:-45px;">Description:</label></td><td><textarea name="request_description"></textarea></td></tr>
            <tr><td></td><td><input name="action" type="hidden" value="submit_ticket" /></td></tr>
            <tr><td></td><td><input name="submit" type="submit" value="Submit" /></td></tr>
                </table>
            </fieldset>
        </form>';
        }
    }

    /**
     * Retreive a param config giving its name
     *
     * @param String $name
     *
     * @return String
     */
    public function getProperty($name) { 
        $info =$this->getPluginInfo();
        return $info->getPropertyValueForName($name);
    }
}
?>