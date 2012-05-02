<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');

/**
 * CodeReviewViews
 */
class CodeReviewViews extends Views {

    /**
     *
     * @var PluginController
     */
    protected $controller;

    /**
     *
     * @var HTTPRequest
     */
    protected $request;

    /**
     * Class constructor
     *
     * @param CodeReview $controller Plugin controller
     *
     * @return Void
     */
    public function __construct($controller) {
        $this->controller = $controller;
        $this->request    = $controller->getRequest();
    }

    /**
     * Display header
     *
     * @return Void
     */
    function header() {
        $title = 'codereview';
        $GLOBALS['HTML']->header(array('title' => $this->_getTitle(), 'group' => $this->request->get('group_id'), 'toptab' => 'my'));
    }

    /**
     * Display footer
     *
     * @return Void
     */
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }

    /**
     * Retrieve plugin title
     *
     * @return String
     */
    function _getTitle() {
        return $GLOBALS['Language']->getText('plugin_codereview', 'title');
    }

    /**
     * Displays Review board frame
     *
     * @return Void
     */
    function displayFrame() {
        $pluginInfo = PluginManager::instance()->getPluginByName('codereview')->getPluginInfo();
        $url        = $pluginInfo->getPropertyValueForName('reviewboard_site');
        echo '<div id="codereview_iframe_div">';
        $GLOBALS['HTML']->iframe($url, array('id' => 'codereview_iframe', 'class' => 'iframe_service'));
        echo '</div>';
    }

    /**
     * Display review request creation form
     *
     * @return Void
     */
    function reviewSubmission() {
        $project_manager = ProjectManager::instance();
        $project = $project_manager->getProject($this->request->get('group_id'));
        $form  = " <form id=\"reviewAdd\" name=\"reviewAction\" method=\"POST\" action=\"/plugins/codereview/?group_id=".$this->request->get('group_id')."&action=submit_review\">";
        $form .= " <p>";
        $form .= "   <label for=\"codereview_server\">Server</label><br>";
        $form .= "   <input id=\"codereview_server_url\" name=\"codereview_server_url\" type=\"text\" size=\"32\" />";
        $form .= "  </p>";
        $form .= "  <p>";
        $form .= "   <label for=\"codereview_repository\">Repository url</label><br>";
        $form .= "   <input id=\"codereview_repository_url\" name=\"codereview_repository_url\" type=\"text\" size=\"64\" />";
        $form .= "  </p>";
        $form .= " <p>";
        $form .= "   <label for=\"codereview_revision\">Revision range</label><br>";
        $form .= "   <span class=\"legend\">Specifies a revision \"REVISION\" or a range of revisions \"STARTREV:STOPREV\" used to generate the diff</span><br>";
        $form .= "   <input id=\"codereview_revision_range\" name=\"codereview_revision_range\" type=\"text\" size=\"22\" />";
        $form .= "  </p>";
        $form .= "  <p>";
        $form .= "   <label for=\"codereview_target\">Target people</label><br>";
        $form .= "   <input id=\"codereview_target_people\" name=\"codereview_target_people\" type=\"text\" size=\"32\" />";
        $form .= "  </p>";
        $form .= "  <p>";
        $form .= "   <label for=\"codereview_summary\">Summary</label><br>";
        $form .= "   <input id=\"codereview_summary\" name=\"codereview_summary\" type=\"text\" size=\"64\" />";
        $form .= "  </p>";
        $form .= "  <p>";
        $form .= "   <label for=\"codereview_description\">Description</label><br>";
        $form .= "   <textarea rows=\"4\" cols=\"60\" id=\"codereview_description\" name=\"codereview_description\">Write something meaningful here</textarea>";
        $form .= "  </p>";
        $form .= "   <input type=\"submit\" value=\"Add review request\" />";
        $form .= " </form>";
        print $form;
    }

}
?>