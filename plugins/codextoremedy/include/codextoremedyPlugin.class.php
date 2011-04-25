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
        require_once('CodexToRemedy.class.php');
        $controler = new CodexToRemedy();
        $controler->process();
    }
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
	
	?>   
        <form  name="request" style="width: 250px;" class="cssform" action="index.php" method="post" enctype="multipart/form-data">
             <fieldset >
                 <table>
                     <tr>
                     	<td><b>Type:</b></td>
                     	<td><select name="type">
                         <option value"support"><?php echo $GLOBALS['Language']->getText('plugin_codextoremedy', 'Support_request');?></option>
                         <option value"enhancement"><?php echo $GLOBALS['Language']->getText('plugin_codextoremedy', 'Enhancement_request');?></option>
                     		</select>
                     	<b><?php echo $GLOBALS['Language']->getText('plugin_codextoremedy', 'severity');?></b></td>
                         <td><select name="severity">
                             <option value"minor"><?php echo $GLOBALS['Language']->getText('plugin_codextoremedy', 'Minor');?></option>
                             <option value"serious"><?php echo $GLOBALS['Language']->getText('plugin_codextoremedy', 'Serious');?></option>
                             <option value"critical"><?php echo $GLOBALS['Language']->getText('plugin_codextoremedy', 'Critical');?></option>
                             </select>
                         </td>
                     </tr>
                     <tr><td><b><?php echo $GLOBALS['Language']->getText('plugin_codextoremedy', 'summary');?></b></td><td><input type="text" name="request_summary" /></td></tr>
                     <tr><td><b>Description:</b></td><td><textarea style="background: b" name="request_description"></textarea></td></tr>
                    <tr><td></td><td><input name="submit" type="submit" value="Submit" /></td></tr>
                </table>
            </fieldset>
        </form>
        <?php
    }
}
?>