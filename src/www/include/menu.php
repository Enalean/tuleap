<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

/* The correct theme.php must be included by this point -- Geoffrey */


// Menu entry  for all admin tasks when logged as site administor
function menu_site_admin() {
  global $Language;
    
    GLOBAL $HTML;
    $HTML->menuhtml_top($Language->getText('include_menu','site_admin')); 
    $HTML->menu_entry('/admin/',$Language->getText('include_menu','main_page'));
    $HTML->menu_entry('/admin/grouplist.php',$Language->getText('include_menu','group_admin'));
    $HTML->menu_entry('/admin/userlist.php',$Language->getText('include_menu','user_admin'));
    $HTML->menu_entry('/admin/approve-pending.php',$Language->getText('include_menu','pending_projects'));
    if ($GLOBALS['sys_user_approval']) {
	$HTML->menu_entry('/admin/approve_pending_users.php?page=pending',$Language->getText('include_menu','pending_users'));
    }
    $HTML->menu_entry('/news/admin',$Language->getText('include_menu','site_news_approve'));
    $HTML->menu_entry('/admin/massmail.php',$Language->getText('include_menu','mass_mail'));
    if ($GLOBALS['sys_use_trove'] != 0) {
        $HTML->menu_entry('/admin/trove/trove_cat_list.php',$Language->getText('include_menu','trove_cat_list'));
        $HTML->menu_entry('/admin/trove/trove_cat_add.php',$Language->getText('include_menu','trove_cat_add'));
    }

    $em =& EventManager::instance();
    $params = array();
    $params['HTML'] =& $HTML;
    $em->processEvent('site_admin_menu_hook', $params);
    $HTML->menuhtml_bottom();

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
  GLOBAL $HTML,$Language;
    $HTML->menuhtml_top($Language->getText('include_menu','software')); 
    if ($GLOBALS['sys_use_trove'] != 0) {
        $HTML->menu_entry('/softwaremap/',$Language->getText('include_menu','software_map'));
    }
    $HTML->menu_entry('/new/',$Language->getText('include_menu','new_releases'));
    // LJ No mirror		$HTML->menu_entry('/mirrors/',$Language->getText('include_menu','other_site_mirrors'));
    if ($GLOBALS['sys_use_snippet'] != 0) {
        $HTML->menu_entry('/snippet/',$Language->getText('include_menu','code_snippet_lib'));
    }
    $HTML->menuhtml_bottom();
}

function menu_site() {
  GLOBAL $HTML,$Language;
    $HTML->menuhtml_top($Language->getText('include_layout','Help'));
    $HTML->menu_entry('/doc/'.UserManager::instance()->getCurrentUser()->getShortLocale().'/','<b>'.$Language->getText('include_menu','help_index').'</b>');
    $HTML->menu_entry('/plugins/docman/?group_id=1',$Language->getText('include_menu','site_doc'));
    $HTML->menu_entry('/mail/?group_id=1',$Language->getText('include_menu','dev_channel'));
    $HTML->menu_entry('/forum/?group_id=1',$Language->getText('include_menu','discussion_forum'));
    print '<P>';
    print '<P>';
    $HTML->menu_entry('/contact.php',$Language->getText('include_menu','contact_us'));
    $HTML->menuhtml_bottom();
}

function menu_search() {
  GLOBAL $HTML,$Language;
    $HTML->menuhtml_top($Language->getText('include_menu','search'));
    $HTML->searchBox();
    $HTML->menuhtml_bottom();
}

function menu_project($grp) {
  GLOBAL $HTML,$Language;
    $pm = ProjectManager::instance();
    $HTML->menuhtml_top($Language->getText('include_menu','proj').': ' . $pm->getProject($grp)->getPublicName());
    $pm = ProjectManager::instance();
    $HTML->menu_entry('/projects/'. $pm->getProject($grp)->getUnixName() .'/',$Language->getText('include_menu','proj_summary'));
    print '<P>';
    $HTML->menu_entry('/project/admin/?group_id='.$grp,$Language->getText('include_menu','proj_admin'));
    $HTML->menuhtml_bottom();
}


function menu_loggedin($page_title) {
  GLOBAL $HTML,$Language;
    /*
      Show links appropriate for someone logged in, like account maintenance, etc
    */
    $HTML->menuhtml_top($Language->getText('include_menu','logged_in').': '.user_getname());
    $HTML->menu_entry('/account/logout.php',$Language->getText('include_menu','logout'));
    
    if((isset($GLOBALS['sys_use_project_registration']) && $GLOBALS['sys_use_project_registration'] ==1) || !isset($GLOBALS['sys_use_project_registration'])) {
                $HTML->menu_entry('/project/register.php',$Language->getText('include_menu','register_new_proj'));
    }
    print '<P>';
    //LJ No theme		$HTML->menu_entry('/themes/','Change My Theme');
    $HTML->menu_entry('/my/',$Language->getText('include_menu','my_perso_page'));

    if (!$GLOBALS['HTTP_POST_VARS']) {
	$bookmark_title = urlencode( str_replace($GLOBALS['sys_name'].': ', '', $page_title));
	print '<P>';
	$HTML->menu_entry('/my/bookmark_add.php?bookmark_url='.urlencode($_SERVER['REQUEST_URI']).'&bookmark_title='.$bookmark_title,$Language->getText('include_menu','bookmark_this_page'));
    }
    print '<P>';
	$em =& EventManager::instance();
    $params = array();
    $params['HTML'] =& $HTML;
    $em->processEvent('usermenu', $params);
    $HTML->menuhtml_bottom();
}

function menu_notloggedin() {
  GLOBAL $HTML,$Language;
    $HTML->menuhtml_top($Language->getText('global','status').':');
    echo '<h4><span class="highlight">'.$Language->getText('include_menu','not_logged_in').'</span></h4>';
    $HTML->menu_entry('/account/login.php',$Language->getText('include_menu','login'));

    $em =& EventManager::instance();
    $display_new_user = true;
    $params = array('allow' => &$display_new_user);
    $em->processEvent('display_newaccount', $params);
    if ($display_new_user) {
        $HTML->menu_entry('/account/register.php',$Language->getText('include_menu','new_user'));
    }
    $HTML->menuhtml_bottom();
}

function menu_print_sidebar($params) {
    if (!user_isloggedin()) {
	echo menu_notloggedin();
	if (! ForgeConfig::areAnonymousAllowed()) { return; }
    } else {
	echo menu_loggedin($params['title']);
    }
	
    // LJ Site Admin menu added here
    if (user_is_super_user()) {
	echo menu_site_admin();
    }
    
    
    echo menu_software();
    echo menu_site();

    //search menu
    echo menu_search();

	?>
	<div align="center">
	     <?php echo $GLOBALS['HTML']->getOsdnNavDropdown(); ?>
	     </div>
		   <?php
		   }
?>
