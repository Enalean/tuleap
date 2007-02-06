<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/* The correct theme.php must be included by this point -- Geoffrey */

  //$Language->loadLanguageMsg('include/include');

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
	$HTML->menu_entry('/admin/approve_pending_users.php',$Language->getText('include_menu','pending_users'));
    }
    $HTML->menu_entry('/news/admin',$Language->getText('include_menu','site_news_approve'));
    $HTML->menu_entry('/admin/massmail.php',$Language->getText('include_menu','mass_mail'));
    $HTML->menu_entry('/admin/trove/trove_cat_list.php',$Language->getText('include_menu','trove_cat_list'));
    $HTML->menu_entry('/admin/trove/trove_cat_add.php',$Language->getText('include_menu','trove_cat_add'));
    $HTML->menu_entry('/people/admin',$Language->getText('include_menu','people_skill'));
    
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
    $HTML->menu_entry('/softwaremap/',$Language->getText('include_menu','software_map'));
    $HTML->menu_entry('/new/',$Language->getText('include_menu','new_releases'));
    // LJ No mirror		$HTML->menu_entry('/mirrors/',$Language->getText('include_menu','other_site_mirrors'));
    $HTML->menu_entry('/snippet/',$Language->getText('include_menu','code_snippet_lib'));
    $HTML->menuhtml_bottom();
}

function menu_site() {
  GLOBAL $HTML,$Language;
    $HTML->menuhtml_top($Language->getText('include_layout','Help'));
    $HTML->menu_entry('/documentation/user_guide/html/en_US/','<b>'.$Language->getText('include_menu','help_index').'</b>');
    $HTML->menu_entry('/plugins/docman/?group_id=1',$Language->getText('include_menu','site_doc'));
    $HTML->menu_entry('/mail/?group_id=1',$Language->getText('include_menu','dev_channel'));
    $HTML->menu_entry('/forum/?group_id=1',$Language->getText('include_menu','discussion_forum'));
    print '<P>';
    print '<P>';
    $HTML->menu_entry('/contact.php',$Language->getText('include_menu','contact_us'));
    $HTML->menuhtml_bottom();
}

function menu_foundry_links() {
  GLOBAL $HTML,$Language;
    $HTML->menuhtml_top($Language->getText('include_menu','sf_foundries'));
    $HTML->menu_entry('/about_foundries.php', $Language->getText('include_menu','about_foudries'));
    echo '<P>
';
    $HTML->menu_entry('/foundry/'. strtolower(group_getunixname(6771)), '3D');
    $HTML->menu_entry('/foundry/'. strtolower(group_getunixname(6772)), $Language->getText('include_menu','games'));
    $HTML->menu_entry('/foundry/'. strtolower(group_getunixname(6770)), 'Java');
    $HTML->menu_entry('/foundry/'. strtolower(group_getunixname(1872)), $Language->getText('include_menu','printing'));
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
    $HTML->menuhtml_top($Language->getText('include_menu','proj').': ' . group_getname($grp));
    $HTML->menu_entry('/projects/'. group_getunixname($grp) .'/',$Language->getText('include_menu','proj_summary'));
    print '<P>';
    $HTML->menu_entry('/project/admin/?group_id='.$grp,$Language->getText('include_menu','proj_admin'));
    $HTML->menuhtml_bottom();
}

function menu_foundry($grp) {
  GLOBAL $HTML,$Language;
    $unix_name=strtolower(group_getunixname($grp));
    $HTML->menuhtml_top($Language->getText('include_menu','foundry').': ' . group_getname($grp));
    $HTML->menu_entry('/foundry/'. $unix_name .'/',$Language->getText('include_menu','summary_page'));
    print '<P>';
    $HTML->menu_entry('/foundry/'. $unix_name .'/admin/', $Language->getText('include_menu','foundry_admin'));
    $HTML->menuhtml_bottom();
}

function menu_foundry_guides($grp) {
  GLOBAL $HTML,$Language;
    /*
      Show list of projects in this portal
    */
    $HTML->menuhtml_top($Language->getText('include_menu','foundry_guides'));

    $sql="SELECT db_images.width,db_images.height,db_images.id ".
	"FROM db_images,foundry_data ".
	"WHERE db_images.id=foundry_data.guide_image_id ".
	"AND foundry_data.foundry_id='$grp'";
    $result=db_query($sql);
    $rows=db_numrows($result);
	
    if (!$result || $rows < 1) {
	//		echo $Language->getText('include_features_boxes','no_projects');
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
	echo $Language->getText('include_features_boxes','no_projects');
	echo db_error();
    } else {
	for ($i=0; $i<$rows; $i++) {
	    $HTML->menu_entry('/users/'. db_result($result,$i,'user_name').'/', db_result($result,$i,'realname'));
	}
    }
    $HTML->menuhtml_bottom();

}

function menu_loggedin($page_title) {
  GLOBAL $HTML,$Language;
    /*
      Show links appropriate for someone logged in, like account maintenance, etc
    */
    $HTML->menuhtml_top($Language->getText('include_menu','logged_in').': '.user_getname());
    $HTML->menu_entry('/account/logout.php',$Language->getText('include_menu','logout'));
    $HTML->menu_entry('/project/register.php',$Language->getText('include_menu','register_new_proj'));
    $HTML->menu_entry('/account/',$Language->getText('include_menu','account_maintenance'));
    print '<P>';
    //LJ No theme		$HTML->menu_entry('/themes/','Change My Theme');
    $HTML->menu_entry('/my/',$Language->getText('include_menu','my_perso_page'));

    if (!$GLOBALS['HTTP_POST_VARS']) {
	$bookmark_title = urlencode( str_replace($GLOBALS['sys_name'].': ', '', $page_title));
	print '<P>';
	$HTML->menu_entry('/my/bookmark_add.php?bookmark_url='.urlencode($GLOBALS['REQUEST_URI']).'&bookmark_title='.$bookmark_title,$Language->getText('include_menu','bookmark_this_page'));
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
    
    
    if (isset($params['group']) && $params['group']) {
        $grp = project_get_object($params['group']);
        if (!$grp->isFoundry()) {
            //this is a project page
            //sf global choices
            //echo menu_project ($params['group']);
            echo menu_software();
            echo menu_site();
        } else {
            //this is a foundry page
            echo menu_foundry_guides($params['group']);
            echo menu_foundry($params['group']);
        }
    } else {
	echo menu_software();
	echo menu_site();
    }

    //Foundry Links
    //(LJ) We do not want the foundry stuff
    //(LJ)	echo menu_foundry_links();

    //search menu
    echo menu_search();

	?>
	<div align="center">
	     <?php echo $GLOBALS['HTML']->getOsdnNavDropdown(); ?>
	     </div>
		   <?php
		   }
?>
