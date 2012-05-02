<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet. 2008
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
 *
 */

require_once 'common/widget/Widget.class.php';
require_once 'common/include/Toggler.class.php';
require_once 'ProjectLinksDao.class.php';

/**
 * Display links from and to a project on the summary page.
 */
class ProjectLinks_Widget_HomePageLinks extends Widget {
    protected $pluginPath;
    protected $themePath;

    /**
     * Constructor
     * 
     * @param Plugin $plugin The plugin
     */
    function __construct(Plugin $plugin) {
        parent::__construct('projectlinkshomepage');
        $this->pluginPath = $plugin->getPluginPath();
        $this->themePath  = $plugin->getThemePath();
    }
    
    /**
     * Widget title
     * 
     * @see src/common/widget/Widget#getTitle()
     * @return String
     */
    function getTitle() {
        return $GLOBALS['Language']->getText('plugin_plinks', 'project_links');
    }

    /**
     * Tell if a widget can by used by a project
     *
     * This method is used only when a during project clone. As widgets are 
     * cloned before the plugins, at this time we don't know if the project links
     * are activated or not. So just say yes all the time.
     * 
     * @param Project $project
     */
    function canBeUsedByProject($project) {
        //$dao = $this->getProjectLinksDao();
        //return $dao->projectUsesProjectLinks($project->getID);
        return true;
    }

    /**
     * Widget content
     * 
     * @see src/common/widget/Widget#getContent()
     * @return String
     */
    function getContent() {
        $request = HTTPRequest::instance();
        $groupId = $request->get('group_id');

        $html = '';
        $html .= "\n<!-- PROJECT LINKS START -->\n";
        $html .= $this->getAllLinks($groupId);
        $html .= "\n<!-- PROJECT LINKS END -->\n";
        return $html;
    }

    /**
     * Get HTML display of all links from and to given project.
     * 
     * @param  Integer $groupId Group id 
     * @return String
     */
    function getAllLinks($groupId) {
        $dao = $this->getProjectLinksDao();
        $html = '';
        $html .= "<ul class=\"project_links_widget_linkslist\">\n";
        $html .= $this->getLinksByLinkType('links', $dao->searchForwardLinks($groupId));
        $html .= $this->getLinksByLinkType('back_links', $dao->searchBackLinks($groupId));
        $html .= "</ul>\n";
        return $html;
    }

    /**
     * Build the top list of link for the 2 ways (links and back_links).
     * 
     * @param  String $way Either 'links' or 'back_links'
     * @param  String $sql The SQL to get the links
     * @return String
     */
    function getLinksByLinkType($way, DataAccessResult $dar) {
        $html = '';
        if ($dar->rowCount() > 0) {
            $linkTypeCmdId   = 'plugin_project_links_type_'.$way;

            $cssClass = Toggler::getClassName($linkTypeCmdId);
            $titleSpan = "<span id=\"".$linkTypeCmdId."\" class=\"".$cssClass."\">".$GLOBALS['Language']->getText('plugin_plinks', $way).'</span>';

            $html .= "<li>".$titleSpan;
            $links = $this->getLinks($way, $dar);
            if($links != '') {
                $html .= "\n";
                $html .= "  <ul>\n";
                $html .= $links;
                $html .= "  </ul>\n";
            }
            $html .= "</li>\n";
        }
        return $html;
    }

    /**
     * Build the HTML for all the link with the same way (from or to the project)
     * 
     * It build either the list of all "forward" links or the list of all
     * "back links"
     * 
     * @param  String $way Either 'links' or 'back_links'
     * @param  String $res One row of link
     * @return String
     */
    function getLinks($way, DataAccessResult $dar) {
        $html = '';
        $previousLinkName = '';
        $ulClosed = true;
        foreach($dar as $row) {
            if($row['link_name'] != $previousLinkName) {
                if(!$ulClosed) {
                    // Do not close the list when the list is not started
                    $html .= "    </ul>\n";
                    $html .= "  </li>\n";
                    $ulClosed = true;
                }
                $spanId  = 'plugin_project_links_name_'.$way.'_'.$row['link_type_id'];
                $cssClass = Toggler::getClassName($spanId);
                
                // Link name title
                $html .= "  <li><span id=\"".$spanId."\" class=\"".$cssClass."\">".$row['link_name']."</span>\n";
                $html .= "    <ul>\n";
                $ulClosed = false;
            }
            
            $html .= "      <li>";
            $html .= $this->getOneLink($row);
            $html .= "  </li>\n";

            $previousLinkName = $row['link_name'];
        }

        if(!$ulClosed) {
            $html .= "    </ul>\n";
            $html .= "  </li>\n";
        }

        return $html;
    }

    /**
     * Build url for one link.
     * 
     * @param  array $row One row for a link
     * @return String
     */
    function getOneLink(array $row) {
        $url = str_replace('$projname', $row['unix_group_name'], $row['uri_plus']);
        $ic = '';
        if ($row['type'] == 2) {
            $path = $this->themePath."/images/template.png";
            $alt = $GLOBALS['Language']->getText('plugin_plinks', 'template_marker');
            $ic = '<img src="'.$path.'" alt="'.$alt.'" title="'.$alt.'" /> ';
        }
        $html = '<a href="'.$url.'">'.$ic.$row['group_name'].'</a>';
        return $html;
    }
    
    /**
     * Return ProjectLinksDao
     * 
     * @return ProjectLinksDao
     */
    function getProjectLinksDao() {
        include_once 'ProjectLinksDao.class.php';
        return new ProjectLinksDao(CodendiDataAccess::instance());
    }
}

?>
