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
        require_once('CodexToRemedy.class.php');
        $controler = new CodexToRemedy();
        $controler->process();
    }

    /**
     * Display form to fill a request
     *
     * @param Array $params params of the hook
     *
     * @return Void
     */
    function displayForm($params) {
        ?>
        <form  name="request" action="index.php" method="post" enctype="multipart/form-data">
             <fieldset style="width:20%"><legend>Submit Help Request:</legend>
                 <table>
                     <tr><td><b>Type:</b></td>
                     <td><select name="type">
                         <option value"support">Support request</option>
                         <option value"enhancement">Enhancement request</option>
                     </select></td></tr>
                     <tr><td><b>Severity:</b></td><td><select name="severity">
                         <option value"minor">Minor</option>
                         <option value"serious">Serious</option>
                         <option value"critical">Critical</option>
                     </select></td></tr>
                     <tr><td><b>Summary:</b></td><td><input type="text" name="request_summary" /></td></tr>
                     <tr><td><b>Description:</b></td><td><textarea name="request_description" cols="60" rows="7"></textarea></td></tr>
                    <tr><td></td><td><input name="submit" type="submit" value="Submit" /></td></tr>
                </table>
            </fieldset>
        </form>
        <?php
    }
}
?>