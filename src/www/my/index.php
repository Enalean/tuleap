<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('my_utils.php');
require_once('common/event/EventManager.class.php');
require_once('common/widget/WidgetLayoutManager.class.php');

$Language->loadLanguageMsg('my/my');
$em =& EventManager::instance();
$em->processEvent('plugin_load_language_file', null);

// define undefined vars
if (!isset($hide_item_id)) {
    $hide_item_id = '';
}
if (!isset($hide_forum)) {
    $hide_forum = '';
}
if (!isset($hide_bug)) {
    $hide_bug = '';
}
//
if (user_isloggedin()) {

    // If it's super user and license terms have not yet been agreed then redirect
    // to license agreement page
    if (user_is_super_user() && !license_already_displayed()) {
        session_redirect("/admin/approve_license.php");
    }

    // Make sure this page is not cached because
    // it uses the exact same URL for all user's
    // personal page
    header("Cache-Control: no-cache, must-revalidate"); // for HTTP 1.1
    header("Pragma: no-cache");  // for HTTP 1.0
    
    if (browser_is_netscape4()) {
        $feedback.= $Language->getText('my_index', 'err_badbrowser');
    }
    $title = $Language->getText('my_index', 'title', array(user_getrealname(user_getid()).' ('.user_getname().')'));
    $GLOBALS['HTML']->includeJavascriptFile('/scripts/prototype/prototype.js');
    $GLOBALS['HTML']->includeJavascriptFile('/scripts/scriptaculous/scriptaculous.js');
    my_header(array('title'=>$title));

    echo '<p>'. $Language->getText('my_index', 'message') .'</p>';

    $lm =& new WidgetLayoutManager();
    $lm->displayLayout(user_getid(), $lm->OWNER_TYPE_USER);
    
    echo show_priority_colors_key();
    ?>
    </span>
<?php
    $request =& HTTPRequest::instance();
    if ($request->get('pv') == 2) {
        $GLOBALS['Response']->pv_footer(array());
    } else {
        site_footer(array());
    }
} else {

    exit_not_logged_in();

}
?>
