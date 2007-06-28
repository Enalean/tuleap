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
require_once('common/widget/WidgetLayout.class.php');
require_once('common/widget/Widget.class.php');

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

    $sql = 'SELECT l.* FROM layouts AS l INNER JOIN user_layouts AS u ON(l.id = u.layout_id) WHERE u.user_id = '. user_getid() .' AND u.is_default = 1';
    $req = db_query($sql);
    if ($data = db_fetch_array($req)) {
        echo '<a href="/my/widgets.php?layout_id='. $data['id'] .'">[Add widget]</a>';
        $layout =& new WidgetLayout($data['id'], $data['name'], $data['description'], $data['scope']);
        $sql = 'SELECT * FROM layouts_rows WHERE layout_id = '. $layout->id .' ORDER BY rank';
        $req_rows = db_query($sql);
        while ($data = db_fetch_array($req_rows)) {
            $row =& new WidgetLayout_Row($data['id'], $data['rank']);
            $sql = 'SELECT * FROM layouts_rows_columns WHERE layout_row_id = '. $row->id;
            $req_cols = db_query($sql);
            while ($data = db_fetch_array($req_cols)) {
                $col =& new WidgetLayout_Row_Column($data['id'], $data['width']);
                $sql = 'SELECT * FROM user_layouts_contents WHERE user_id = '. user_getid() .' AND column_id = '. $col->id .' ORDER BY rank';
                $req_content = db_query($sql);
                while ($data = db_fetch_array($req_content)) {
                    $c =& Widget::getInstance($data['name']);
                    if ($c !== null) {
                        $c->loadContent($data['content_id']);
                        $col->add($c, $data['is_minimized'], $data['display_preferences']);
                    }
                    unset($c);
                }
                $row->add($col);
                unset($col);
            }
            $layout->add($row);
            unset($row);
        }
        $layout->display();
    }
    echo show_priority_colors_key();
    ?>
    </span>
<?php
    site_footer(array());

} else {

    exit_not_logged_in();

}
?>
