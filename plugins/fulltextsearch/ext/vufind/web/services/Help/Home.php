<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */
 

require_once 'Action.php';

class Home extends Action
{
    function launch()
    {
        global $interface;
        global $configArray;

        // Sanitize the topic name to include only alphanumeric characters
        // or underscores.
        $safe_topic = preg_replace('/[^\w]/', '', $_GET['topic']);
        
        // Construct three possible template names -- the help screen in the current 
        // selected language, help in the site's default language, and help in English 
        // (most likely to exist).  The code will attempt to display most appropriate
        // help screen that actually exists.
        $tpl_user = 'Help/' . $interface->getLanguage() . "/{$safe_topic}.tpl";
        $tpl_site = "Help/{$configArray['Site']['language']}/{$safe_topic}.tpl";
        $tpl_en = 'Help/en/' . $safe_topic . '.tpl';
        
        // Best case -- help is available in the user's chosen language
        if ($interface->template_exists($tpl_user)) {
            $interface->setTemplate($tpl_user);

        // Compromise -- help is available in the site's default language
        } else if ($interface->template_exists($tpl_site)) {
            $interface->setTemplate($tpl_site);
            $interface->assign('warning', true);

        // Last resort -- help is available in English
        } else if ($interface->template_exists($tpl_en)) {
            $interface->setTemplate($tpl_en);
            $interface->assign('warning', true);

        // Error -- help isn't available at all!
        } else {
            PEAR::raiseError(new PEAR_Error('Unknown Help Page'));
        }

        $interface->display('Help/help.tpl');
    }
}

?>
