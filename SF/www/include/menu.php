<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/* The correct theme.php must be included by this point -- Geoffrey */

// Menu entry  for all admin tasks when logged as site administor
function menu_site_admin() {
    
    GLOBAL $HTML;
    $HTML->menuhtml_top('Site Administration'); 
    $HTML->menu_entry('/admin/','Main Page');
    $HTML->menu_entry('/admin/grouplist.php','Group Admin');
    $HTML->menu_entry('/admin/userlist.php','User Admin');
    $HTML->menu_entry('/admin/approve-pending.php','Pending Projects');
    if ($GLOBALS['sys_user_approval']) {
	$HTML->menu_entry('/admin/approve_pending_users.php','Pending Users');
    }
    $HTML->menu_entry('/news/admin','Site News Approval');
    $HTML->menu_entry('/admin/massmail.php','Mass Mail');
    $HTML->menu_entry('/admin/trove/trove_cat_list.php','Trove Cat. List');
    $HTML->menu_entry('/admin/trove/trove_cat_add.php','Trove Cat. Add');
    $HTML->menuhtml_bottom();

}



function menu_show_search_box() {
    global $words,$forum_id,$group_id,$is_bug_page,$is_support_page,
	$is_pm_page,$is_snippet_page,$exact,$type_of_search,$atid;

    // if there is no search currently, set the default
    if ( ! isset($type_of_search) ) {
	$exact = 1;
    }

    print "\t<CENTER>\n";
    print "\t<FORM action=\"/search/\" method=\"post\">\n";

    print "\t<SELECT name=\"type_of_search\">\n";
    if ($is_bug_page && $group_id) {
	print "\t<OPTION value=\"bugs\"".( $type_of_search == "bugs" ? " SELECTED" : "" ).">Bugs</OPTION>\n";
    } else if ($is_pm_page && $group_id) {
	print "\t<OPTION value=\"tasks\"".( $type_of_search == "tasks" ? " SELECTED" : "" ).">Tasks</OPTION>\n";
    } else if ($is_support_page && $group_id) {
	print "\t<OPTION value=\"support\"".( $type_of_search == "support" ? " SELECTED" : "" ).">Support Requests</OPTION>\n";
    } else if ($group_id && $forum_id) {
	print "\t<OPTION value=\"forums\"".( $type_of_search == "forums" ? " SELECTED" : "" ).">This Forum</OPTION>\n";
    } else if ($group_id && $atid) {
	print "\t<OPTION value=\"tracker\"".( $type_of_search == "tracker" ? " SELECTED" : "" ).">This Tracker</OPTION>\n";
    }

    print "\t<OPTION value=\"soft\"".( $type_of_search == "soft" ? " SELECTED" : "" ).">Software Projects</OPTION>\n";
    print "\t<OPTION value=\"snippets\"".( ($type_of_search == "snippets" || $is_snippet_page) ? " SELECTED" : "" ).">Code Snippets</OPTION>\n";
    print "\t<OPTION value=\"people\"".( $type_of_search == "people" ? " SELECTED" : "" ).">People</OPTION>\n";
    print "\t</SELECT>\n";

    print "\t<BR>\n";
    print "\t<INPUT TYPE=\"CHECKBOX\" NAME=\"exact\" VALUE=\"1\"".( $exact ? " CHECKED" : " UNCHECKED" )."> Require All Words \n";

    print "\t<BR>\n";
    if ( isset($atid) ) {
	print "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$atid\" NAME=\"atid\">\n";
    } 
    if ( isset($forum_id) ) {
	print "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$forum_id\" NAME=\"forum_id\">\n";
    } 
    if ( isset($is_bug_page) ) {
	print "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$is_bug_page\" NAME=\"is_bug_page\">\n";
    }
    if ( isset($is_support_page) ) {
	print "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$is_support_page\" NAME=\"is_support_page\">\n";
    }
    if ( isset($is_pm_page) ) {
	print "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$is_pm_page\" NAME=\"is_pm_page\">\n";
    }
    if ( isset($is_snippet_page) ) {
	print "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$is_snippet_page\" NAME=\"is_snippet_page\">\n";
    }
    if ( isset($group_id) ) {
	print "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$group_id\" NAME=\"group_id\">\n";
    }

    print "\t<INPUT TYPE=\"text\" SIZE=\"16\" NAME=\"words\" VALUE=\"$words\">\n";
    print "\t<BR>\n";
    print "\t<INPUT TYPE=\"submit\" NAME=\"Search\" VALUE=\"Search\">\n";
    print "\t</FORM>\n";
    print "\t</CENTER>\n";
}

//depricated - theme wrapper
function menuhtml_top($title) {
    /*
      Use only for the top most menu
    */
    theme_menuhtml_top($title);
}

//deprecated - theme wrapper
function menuhtml_bottom() {
    theme_menuhtml_bottom();
}

function menu_software() {
    GLOBAL $HTML;
    $HTML->menuhtml_top('Software'); 
    $HTML->menu_entry('/softwaremap/','Software Map');
    $HTML->menu_entry('/new/','New Releases');
    // LJ No mirror		$HTML->menu_entry('/mirrors/','Other Site Mirrors');
    $HTML->menu_entry('/snippet/','Code Snippet Library');
    $HTML->menuhtml_bottom();
}

function menu_sourceforge() {
    GLOBAL $HTML;
    $HTML->menuhtml_top($GLOBALS['sys_name']);
    $HTML->menu_entry('/documentation/user_guide/html/en_US/','<b>Help Index</b>');
    $HTML->menu_entry('/docs/site/','Site Documentation');
    $HTML->menu_entry('/mail/?group_id=1','Developers Channels');
    $HTML->menu_entry('/forum/?group_id=1','Discussion Forums');
    $HTML->menu_entry('/people/','Project Help Wanted');
    print '<P>';
    print '<P>';
    $HTML->menu_entry('/contact.php','Contact Us');
    $HTML->menuhtml_bottom();
}

function menu_foundry_links() {
    GLOBAL $HTML;
    $HTML->menuhtml_top('Sourceforge Foundries');
    $HTML->menu_entry('/about_foundries.php', 'About Foundries');
    echo '<P>
';
    $HTML->menu_entry('/foundry/'. strtolower(group_getunixname(6771)), '3D');
    $HTML->menu_entry('/foundry/'. strtolower(group_getunixname(6772)), 'Games');
    $HTML->menu_entry('/foundry/'. strtolower(group_getunixname(6770)), 'Java');
    $HTML->menu_entry('/foundry/'. strtolower(group_getunixname(1872)), 'Printing');
    $HTML->menuhtml_bottom();
}

function menu_search() {
    GLOBAL $HTML;
    $HTML->menuhtml_top('Search');
    menu_show_search_box();
    $HTML->menuhtml_bottom();
}

function menu_project($grp) {
    GLOBAL $HTML;
    $HTML->menuhtml_top('Project: ' . group_getname($grp));
    $HTML->menu_entry('/projects/'. group_getunixname($grp) .'/','Project Summary');
    print '<P>';
    $HTML->menu_entry('/project/admin/?group_id='.$grp,'Project Admin');
    $HTML->menuhtml_bottom();
}

function menu_foundry($grp) {
    GLOBAL $HTML;
    $unix_name=strtolower(group_getunixname($grp));
    $HTML->menuhtml_top('Foundry: ' . group_getname($grp));
    $HTML->menu_entry('/foundry/'. $unix_name .'/','Summary Page');
    print '<P>';
    $HTML->menu_entry('/foundry/'. $unix_name .'/admin/', 'Foundry Admin');
    $HTML->menuhtml_bottom();
}

function menu_foundry_guides($grp) {
    GLOBAL $HTML;
    /*
      Show list of projects in this portal
    */
    $HTML->menuhtml_top('Foundry Guides');

    $sql="SELECT db_images.width,db_images.height,db_images.id ".
	"FROM db_images,foundry_data ".
	"WHERE db_images.id=foundry_data.guide_image_id ".
	"AND foundry_data.foundry_id='$grp'";
    $result=db_query($sql);
    $rows=db_numrows($result);
	
    if (!$result || $rows < 1) {
	//		echo 'No Projects';
	echo db_error();
    } else {
	echo '<IMG SRC="/dbimage.php?id='.db_result($result,$i,'id').'" HEIGHT="'.db_result($result,$i,'height').'" WIDTH="'.db_result($result,$i,'width').'"><BR>';
    }

    //echo html_image('foundry/'.$grp.'admin.png',array()).'<BR>';

    $sql = "SELECT user.realname,user.user_id,user.user_name ".
	"FROM user,user_group ".
	"WHERE user.user_id=user_group.user_id ".
	"AND user_group.admin_flags='A' ".
	"AND user_group.group_id='$grp'";

    $result=db_query($sql);
    $rows=db_numrows($result);

    if (!$result || $rows < 1) {
	echo 'No Projects';
	echo db_error();
    } else {
	for ($i=0; $i<$rows; $i++) {
	    $HTML->menu_entry('/users/'. db_result($result,$i,'user_name').'/', db_result($result,$i,'realname'));
	}
    }
    $HTML->menuhtml_bottom();

}

function menu_loggedin($page_title) {
    GLOBAL $HTML;
    /*
      Show links appropriate for someone logged in, like account maintenance, etc
    */
    $HTML->menuhtml_top('Logged In: '.user_getname());
    $HTML->menu_entry('/account/logout.php','Logout');
    $HTML->menu_entry('/register/','Register New Project');
    $HTML->menu_entry('/account/','Account Maintenance');
    print '<P>';
    //LJ No theme		$HTML->menu_entry('/themes/','Change My Theme');
    $HTML->menu_entry('/my/','My Personal Page');

    if (!$GLOBALS['HTTP_POST_VARS']) {
	$bookmark_title = urlencode( str_replace($GLOBALS['sys_name'].': ', '', $page_title));
	print '<P>';
	$HTML->menu_entry('/my/bookmark_add.php?bookmark_url='.urlencode($GLOBALS['REQUEST_URI']).'&bookmark_title='.$bookmark_title,'Bookmark This Page');
    }
    $HTML->menuhtml_bottom();
}

function menu_notloggedin() {
    GLOBAL $HTML;
    $HTML->menuhtml_top('Status:');
    echo '<h4><span class="highlight">NOT LOGGED IN</span></h4>';
    $HTML->menu_entry('/account/login.php','Login');
    $HTML->menu_entry('/account/register.php','New User');
    $HTML->menuhtml_bottom();
}

function menu_print_sidebar($params) {
    /*
      See if this is a project or a foundry
      and show the correct nav menus
    */
    if (!user_isloggedin()) {
	echo menu_notloggedin();
	if (!$GLOBALS['sys_allow_anon']) { return; }
    } else {
	echo menu_loggedin($params['title']);
    }
	
    // LJ Site Admin menu added here
    if (user_is_super_user()) {
	echo menu_site_admin();
    }

    $grp=project_get_object($params['group']);

    if ($params['group'] && $grp->isProject()) {
	//this is a project page
	//sf global choices
	echo menu_project ($params['group']);
	echo menu_software();
	echo menu_sourceforge();
    } else if ($params['group']) {
	//this is a foundry page
	echo menu_foundry_guides($params['group']);
	echo menu_foundry($params['group']);
    } else {
	echo menu_software();
	echo menu_sourceforge();
    }

    //Foundry Links
    //(LJ) We do not want the foundry stuff
    //(LJ)	echo menu_foundry_links();

    //search menu
    echo menu_search();

	?>
	<div align="center">
	     <?php osdn_nav_dropdown(); ?>
	     </div>
		   <?php
		   }
?>
