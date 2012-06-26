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
require_once('RepositoryManager.class.php');
require_once('CodeReviewActions.class.php');

/**
 * CodeReviewViews
 */
class CodeReviewViews extends Views {

    /**
     * @var PluginController
     */
    protected $controller;

    /**
     * @var HTTPRequest
     */
    protected $request;

    /**
     * @var User
     */
    protected $user;
    /**

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
        $this->user       = $controller->getUser();
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
     * Displays First Frame
     *
     * @return Void
     */
    function displayFirstFrame() {
        $pluginInfo = PluginManager::instance()->getPluginByName('codereview')->getPluginInfo();
        $url        = $pluginInfo->getPropertyValueForName('reviewboard_site')."/account/login/";
        echo '</br>';
        echo "<a href='/plugins/codereview/?group_id=".$this->request->get('group_id')."&action=add_review'>Create a new review request</a>";
        echo '</br>';
        echo '</br>';
        echo "<a href='/plugins/codereview/?group_id=".$this->request->get('group_id')."&action=publish_review'>Publish a  review request</a>";
        echo '</br>';
        echo '</br>';
        echo "<a href='/plugins/codereview/?group_id=".$this->request->get('group_id')."&action=dashboard'>Go to your Dashboard</a>";
        echo '</br>';
        echo '</br>';
        echo"<a href='/plugins/codereview/?group_id=".$this->request->get('group_id')."&action=create_patch'>Create your patch file</a>";
        $iframe  = "<iframe name=\"rb\" style=\"display:none; visibility:hidden\"/>";
        $form    = " <form id=\"form\" target=\"rb\" enctype=\"multipart/form-data\" name=\"reviewAction\" method=\"POST\" action=$url>";
        $form   .= "  <p>";
        $form   .= "   <input name=\"username\" value=".$this->user->getUserName()." type=\"hidden\" size=\"24\" />";
        $form   .= "  </p>";
        $form   .= "  <p>";
        $form   .= "   <input name=\"password\" value=".$this->user->getUserPw()." type=\"hidden\" size=\"24\" />";
        $form   .= "  </p>";
        $form   .= " </form>";
        $script .="<script type=\"text/javascript\">";
        $script .="function myfunc () {";
        $script .="var frm = document.getElementById(\"form\");";
        $script .="frm.submit();";
        $script .="}";
        $script .="window.onload = myfunc;";
        $script .="</script>";
        print $form;
        print $script;
        print $iframe;
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
     * Displays The Admin Interface of Review board
     *
     * @return Void
     */
    function displayFrameAdmin() {
        $pluginInfo = PluginManager::instance()->getPluginByName('codereview')->getPluginInfo();
        $url        = $pluginInfo->getPropertyValueForName('reviewboard_site')."/admin/";
        echo '<div id="codereview_iframe_div">';
        $GLOBALS['HTML']->iframe($url, array('id' => 'codereview_iframe', 'class' => 'iframe_service'));
        echo '</div>';
    }

    /**
     * Display published review request
     *
     * @return Void
     */
     function displayFramePublish() {
        $pluginInfo = PluginManager::instance()->getPluginByName('codereview')->getPluginInfo();
        $url        = $pluginInfo->getPropertyValueForName('reviewboard_site')."/r/".$this->request->get('review_id');
        echo '<div id="codereview_iframe_div">';
        $GLOBALS['HTML']->iframe($url, array('id' => 'codereview_iframe', 'class' => 'iframe_service'));
        echo '</div>';
    }

    /**
     * Display all review request
     *
     * @return Void
     */
    function displayFrameReviewRequest() {
        $action     = new CodeReviewActions($this->controller,null);
        $idrequest  = $action->getIdreviewrequest;
        $pluginInfo = PluginManager::instance()->getPluginByName('codereview')->getPluginInfo();
        $url        = $pluginInfo->getPropertyValueForName('reviewboard_site')."/r/".$idrequest."/";
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
	    $user               = UserManager::instance()->getCurrentUser();
		$username           = $user->getUserName();
        $project_manager    = ProjectManager::instance();
        $repository_manager = new RepositoryManager($this->controller->plugin, $this->request);
        $project            = $project_manager->getProject($this->request->get('group_id'));
        $form  = " <form id=\"reviewAdd\" name=\"reviewAction\" method=\"POST\" action=\"/plugins/codereview/?group_id=".$this->request->get('group_id')."&action=submit_review\">";
        $form .= "   <input id=\"codereview_server_url\" name=\"codereview_server_url\" Value=\"".$repository_manager->rbPath."\" type=\"hidden\"/>";
        //$form .= "   <input id=\"codereview_repository_url\" name=\"codereview_repository_url\" Value=\"".$repository_manager->svnPath."\" type=\"hidden\"/>";
        $form .= "   <input id=\"codereview_rb_user\" name=\"codereview_rb_user\" Value=\"".$repository_manager->rbUser."\" type=\"hidden\" size=\"64\"/>";
        $form .= "   <input id=\"codereview_rb_password\" name=\"codereview_rb_password\" Value=\"".$repository_manager->rbPassword."\" type=\"hidden\"/>";
        $form .= "  <p>";
        $form .= "   <label for=\"codereview_repository_url\">Repository url</label><br>";
        $form .= "   <input id=\"codereview_repository_url\" name=\"codereview_repository_url\" Value=\"".$repository_manager->svnPath."\" type=\"text\" size=\"64\" />";
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
        $form .= "   <label for=\"codereview_base_dir\">The absolute path in the repository the diff was generated in</label><br>";
        $form .= "   <input id=\"codereview_base_dir\" name=\"codereview_base_dir\" type=\"text\" size=\"64\" />";
        $form .= "  </p>";
        $form .= "  <p>";
        $form .= "   <label for=\"codereview_diff_path\">Path to the diff file</label><br>";
        $form .= "   <input id=\"codereview_diff_path\" name=\"codereview_diff_path\" type=\"text\" size=\"64\" />";
        $form .= "  </p>";
        $form .= "   <input id=\"codereview_submit_as\" name=\"codereview_submit_as\" type=type=\"hidden\" size=\"32\" value=\"".$username."\"/>";
		

        $formOptionalInput  = "  <p>";
        $formOptionalInput .= "   <label for=\"codereview_testing_done\">Testing done</label><br>";
        $formOptionalInput .= "   <input id=\"codereview_testing_done\" name=\"codereview_testing_done\" type=\"text\" size=\"32\" />";
        $formOptionalInput .= "  </p>";
        $formOptionalInput .= "  <p>";
        $formOptionalInput .= "   <label for=\"codereview_submit_as\">Submit as</label><br>";
        $formOptionalInput .= "   <input id=\"codereview_submit_as\" name=\"codereview_submit_as\" type=\"text\" size=\"32\" />";
        $formOptionalInput .= "  </p>";
        $formOptionalInput .= " <p>";
        $formOptionalInput .= "   <label for=\"codereview_revision\">Revision range</label><br>";
        $formOptionalInput .= "   <span class=\"legend\">Specifies a revision \"REVISION\" or a range of revisions \"STARTREV:STOPREV\" used to generate the diff</span><br>";
        $formOptionalInput .= "   <input id=\"codereview_revision_range\" name=\"codereview_revision_range\" type=\"text\" size=\"22\" />";
        $formOptionalInput .= "  </p>";
        $formOptionalInput .= "  <p>";
        $formOptionalInput .= "   <label for=\"codereview_description\">Description</label><br>";
        $formOptionalInput .= "   <textarea rows=\"4\" cols=\"60\" id=\"codereview_description\" name=\"codereview_description\">Write something meaningful here</textarea>";
        $formOptionalInput .= "  </p>";

        $form .= "   <input type=\"submit\" value=\"Add review request\" />";
        $form .= " </form>";
        print $form;
    }

    /**
     * Display publish review request form
     *
     * @return Void
     */
     function reviewPublishing() {
    $pluginInfo = PluginManager::instance()->getPluginByName('codereview')->getPluginInfo();
    $form  = " <form id=\"reviewPublish\" name=\"reviewAction\" method=\"POST\" action=\"/plugins/codereview/?group_id=".$this->request->get('group_id')."&action=submit_publish\">";
    $form .= "   <input id=\"codereview_server_url\" name=\"codereview_server_url\" Value=\"".$pluginInfo->getPropertyValueForName('reviewboard_site')."\" type=\"hidden\"/>";
    $form .= "  <p>";
    $form .= "   <label for=\"codereview_rb_user\">RB_User</label><br>";
    $form .= "   <input id=\"codereview_rb_user\" name=\"codereview_rb_user\" type=\"text\" size=\"24\" />";
    $form .= "  </p>";
    $form .= "  <p>";
    $form .= "   <label for=\"codereview_rb_password\">RB_PWD</label><br>";
    $form .= "   <input id=\"codereview_rb_password\" name=\"codereview_rb_password\" type=\"password\" size=\"24\" />";
    $form .= "  </p>";
    $form .= "  <p>";
    $form .= "   <label for=\"review_id\">Review_ID</label><br>";
    $form .= "   <input id=\"review_id\" name=\"review_id\" type=\"text\" size=\"24\" />";
    $form .= "  </p>";
    $form .= "   <input type=\"submit\" value=\"Publish the review\" />";
    $form .= " </form>";
    print $form;
    }

    /**
     * Display  Rb login 
     *
     * @return Void
     */
    function loginSubmission() {
        $pluginInfo = PluginManager::instance()->getPluginByName('codereview')->getPluginInfo();
        $url        = $pluginInfo->getPropertyValueForName('reviewboard_site')."/account/login/";
        $form  = " <form id=\"loginsubmission\" target=\"codereview_iframe\" name=\"reviewAction\" method=\"POST\" action=$url>";
        $form .= "  <p>";
        $form .= "   <input name=\"username\" value=".$this->user->getUserName()." type=\"hidden\" size=\"24\" />";
        $form .= "  </p>";
        $form .= "  <p>";
        $form .= "   <input name=\"password\" value=".$this->user->getUserPw()." type=\"hidden\" size=\"24\" />";
        $form .= "  </p>";
        $form .= "   <input type=\"submit\" value=\"Confirmer\" />";
        $form .= " </form>";
        print $form;
    }

    /**
     * Display patch file creation form
     *
     * @return Void
     */
    function createPatchFile() {
        $pluginInfo = PluginManager::instance()->getPluginByName('codereview')->getPluginInfo();
        $form  = " <form id=\"createPatch\" name=\"reviewAction\" method=\"POST\" action=\"/plugins/codereview/?group_id=".$this->request->get('group_id')."&action=submit_patch\">";
        $form .= "   <input id=\"codereview_server_url\" name=\"codereview_server_url\" Value=\"".$pluginInfo->getPropertyValueForName('reviewboard_site')."\" type=\"hidden\"/>";
        $form .= "  <p>";
        $form .= "   <label for=\"first_revision\">First Revision</label><br>";
        $form .= "   <input id=\"first_revision\" name=\"first_revision\" type=\"text\" size=\"24\" />";
        $form .= "  </p>";
        $form .= "  <p>";
        $form .= "   <label for=\"second_revision\">Second Revision</label><br>";
        $form .= "   <input id=\"second_revision\" name=\"second_revision\" type=\"text\" size=\"24\" />";
        $form .= "  </p>";
        $form .= "  <p>";
        $form .= "   <label for=\"target_directory\">Target Directory</label><br>";
        $form .= "   <input id=\"target_directory\" name=\"target_directory\" type=\"text\" size=\"24\" />";
        $form .= "  </p>";
        $form .= "   <label for=\"patch_path\">Patch Path</label><br>";
        $form .= "   <input id=\"patch_path\" name=\"patch_path\" type=\"text\" size=\"24\" />";
        $form .= "  </p>";
        $form .= "   <input type=\"submit\" value=\"Create the patch file\" />";
        $form .= " </form>";
        print $form;
    }

}

?>