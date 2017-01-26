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
require_once('../admin/admin_utils.php');

$hp = Codendi_HTMLPurifier::instance();
if (user_isloggedin()) {
    Tuleap\Instrument\Collect::increment('service.my.accessed');

    // Make sure this page is not cached because
    // it uses the exact same URL for all user's
    // personal page
    header("Cache-Control: no-cache, no-store, must-revalidate"); // for HTTP 1.1
    header("Pragma: no-cache");  // for HTTP 1.0

    $title = $Language->getText('my_index', 'title', array( $hp->purify(user_getrealname(user_getid()), CODENDI_PURIFIER_CONVERT_HTML) .' ('.user_getname().')'));
    my_header(array('title'=>$title, 'body_class' => array('widgetable')));

    if (user_is_super_user()) {
        echo site_admin_warnings();
    }

    echo '<p>'. $Language->getText('my_index', 'message') .'</p>';

    $lm = new WidgetLayoutManager();
    $lm->displayLayout(user_getid(), WidgetLayoutManager::OWNER_TYPE_USER);

    if (! $current_user->getPreference(Tuleap_Tour_WelcomeTour::TOUR_NAME)) {
        $GLOBALS['Response']->addTour(new Tuleap_Tour_WelcomeTour($current_user));
    }

    ?>
    </span>
<?php
    $request = HTTPRequest::instance();
    if ($request->get('pv') == 2) {
        $GLOBALS['Response']->pv_footer(array());
    } else {
        site_footer(array());
    }
} else {
    exit_not_logged_in();
}
