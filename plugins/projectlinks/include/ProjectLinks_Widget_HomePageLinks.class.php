<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet. 2008
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;

/**
 * Display links from and to a project on the summary page.
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class ProjectLinks_Widget_HomePageLinks extends Widget
{
    protected $pluginPath;
    protected $themePath;
    /**
     * @var Codendi_HTMLPurifier
     */
    private $html_purifier;

    /**
     * Constructor
     *
     * @param Plugin $plugin The plugin
     */
    public function __construct(Plugin $plugin, Codendi_HTMLPurifier $html_purifier)
    {
        parent::__construct('projectlinkshomepage');
        $this->pluginPath    = $plugin->getPluginPath();
        $this->themePath     = $plugin->getThemePath();
        $this->html_purifier = $html_purifier;
    }

    /**
     * Widget title
     *
     * @see src/common/Widget/Widget#getTitle()
     * @return String
     */
    public function getTitle()
    {
        return $GLOBALS['Language']->getText('plugin_plinks', 'project_links');
    }

    public function getDescription()
    {
        return $GLOBALS['Language']->getText('plugin_plinks', 'descriptor_description');
    }

    /**
     * Widget content
     *
     * @see src/common/Widget/Widget#getContent()
     * @return String
     */
    public function getContent()
    {
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
     * @param int $groupId Group id
     */
    private function getAllLinks($groupId): string
    {
        $dao      = $this->getProjectLinksDao();
        $html     = '';
        $forward  = $this->getLinksByLinkType('links', $dao->searchForwardLinks($groupId));
        $backward = $this->getLinksByLinkType('back_links', $dao->searchBackLinks($groupId));
        if ($forward === '' && $backward === '') {
            $html .= "<div>" . $GLOBALS['Language']->getText('plugin_plinks', 'no_links_found') . "</div>";
        } else {
            $html .= "<ul class=\"project-link-list project-link-list-content\">\n";
            $html .= $forward;
            $html .= $backward;
            $html .= "</ul>\n";
        }

        return $html;
    }

    /**
     * Build the top list of link for the 2 ways (links and back_links).
     *
     * @param  String $way Either 'links' or 'back_links'
     * @param  String $sql The SQL to get the links
     */
    private function getLinksByLinkType($way, \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface $dar): string
    {
        $html = '';
        if ($dar->rowCount() > 0) {
            $linkTypeCmdId   = 'plugin_project_links_type_' . $way;

            $title = $GLOBALS['Language']->getText('plugin_plinks', 'links');
            if ($way === 'back_links') {
                $title = $GLOBALS['Language']->getText('plugin_plinks', 'back_links');
            }

            $cssClass = Toggler::getClassName($linkTypeCmdId);
            $titleSpan = "<span id=\"" . $this->html_purifier->purify($linkTypeCmdId) .
                "\" class=\"" . $this->html_purifier->purify($cssClass) . "\">" .
                $this->html_purifier->purify($title) .
                '</span>';

            $html .= "<li>" . $titleSpan;
            $links = $this->getLinks($way, $dar);
            if ($links != '') {
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
     */
    private function getLinks($way, \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface $dar): string
    {
        $html = '';
        $previousLinkName = '';
        $ulClosed = true;
        foreach ($dar as $row) {
            if ($row['link_name'] != $previousLinkName) {
                if (!$ulClosed) {
                    // Do not close the list when the list is not started
                    $html .= "    </ul>\n";
                    $html .= "  </li>\n";
                    $ulClosed = true;
                }
                $spanId  = 'plugin_project_links_name_' . $way . '_' . $row['link_type_id'];
                $cssClass = Toggler::getClassName($spanId);

                // Link name title
                $html     .= "  <li class='project-link-list'><span id=\""
                    . $this->html_purifier->purify($spanId) .
                    "\" class=\"" . $this->html_purifier->purify($cssClass) . "\">" .
                    $this->html_purifier->purify($row['link_name']) . "</span>\n";
                $html     .= "    <ul class='project-link-list'>\n";
                $ulClosed = false;
            }

            $html .= "      <li class='project-link-list'>";
            $html .= $this->getOneLink($row);
            $html .= "  </li>\n";

            $previousLinkName = $row['link_name'];
        }

        if (!$ulClosed) {
            $html .= "    </ul>\n";
            $html .= "  </li>\n";
        }

        return $html;
    }

    /**
     * Build url for one link.
     *
     * @param  array $row One row for a link
     */
    private function getOneLink(array $row): string
    {
        $url = str_replace('$projname', $row['unix_group_name'], $row['uri_plus']);
        $ic = '';
        if ($row['type'] == 2) {
            $path = $this->html_purifier->purify($this->themePath . "/images/template.png");
            $alt  = $this->html_purifier->purify($GLOBALS['Language']->getText('plugin_plinks', 'template_marker'));
            $ic   = '<img src="' . $path . '" alt="' . $alt . '" title="' . $alt . '" /> ';
        }
        return '<a href="' . $this->html_purifier->purify($url) . '">' . $ic . $this->html_purifier->purify($row['group_name']) . '</a>';
    }

    /**
     * Return ProjectLinksDao
     *
     * @return ProjectLinksDao
     */
    private function getProjectLinksDao()
    {
        include_once 'ProjectLinksDao.class.php';
        return new ProjectLinksDao(CodendiDataAccess::instance());
    }

    public function getStylesheetDependencies()
    {
        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/projectlinks',
            '/assets/projectlinks'
        );
        return new CssAssetCollection([new CssAssetWithoutVariantDeclinaisons($include_assets, 'projectlinks')]);
    }
}
