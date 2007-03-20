<?php   

//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//              
// $Id$


require_once('common/include/Response.class.php');

require_once('common/event/EventManager.class.php');



//$Language->loadLanguageMsg('include/include');
include($Language->getContent('layout/osdn_sites'));
            
/*

	Extends the basic Error class to add HTML functions for displaying all site dependent HTML, while allowing extendibility/overriding by themes via the Theme class.

	Make sure browser.php is included _before_ you create an instance of this object.

	Geoffrey Herteg, August 29, 2000

*/



class Layout extends Response {

    
	//Define all the icons for this theme
	var $icons = array('Summary' => 'ic/anvil24.png',
		'Homepage' => 'ic/home.png',
		'Forums' => 'ic/notes.png',
		'Bugs' => 'ic/bug.png',
		'Support' => 'ic/support.png',
		'Patches' => 'ic/patch.png',
		'Lists' => 'ic/mail.png',
		'Tasks' => 'ic/index.png',
		'Docs' => 'ic/docman.png',
		'Surveys' => 'ic/survey.png',
		'News' => 'ic/news.png',
		'CVS' => 'ic/convert.png',
		'Files' => 'ic/save.png',
		'Trackers' => 'ic/tracker20w.png'
		);

	var $bgpri = array();
    
	// Constuctor
	function Layout($root) {
		GLOBAL $bgpri;
        
        
		// Constructor for parent class...
		$this->Response();
        
        $this->javascript_files = array();
        
		/*
	        Set up the priority color array one time only
		*/
		$bgpri[1] = 'priora';
		$bgpri[2] = 'priorb';
		$bgpri[3] = 'priorc';
		$bgpri[4] = 'priord';
		$bgpri[5] = 'priore';
		$bgpri[6] = 'priorf';
		$bgpri[7] = 'priorg';
		$bgpri[8] = 'priorh';
		$bgpri[9] = 'priori';

	}

    
    function redirect($url) {
        if (session_hash()) {
            $this->_serializeFeedback();
            if (headers_sent()) {
                echo '<a href="'. $url .'">'. $url .'</a>';
            }
            header('Location: '. $url);
        } else {
            $this->header(array('title' => 'Redirection'));
            echo '<p>'. $GLOBALS['Language']->getText('global', 'return_to', array($url)) .'</p>';
            echo '<script type="text/javascript">';
            echo 'setTimeout(function() {';
            echo " location.href = '". $url ."';";
            echo '}, 5000);';
            echo '</script>';
            $this->footer(array());
        }
        exit();
    }
    
    function iframe($url, $html_options = array()) {
        $html = '';
        $html .= '<div class="iframe_showonly"><a href="'. $url .'" title="Show only this frame">Show only this frame '. $this->getImage('ic/plain-arrow-down.png') .'</a></div>';
        $args = ' src="'. $url .'" ';
        foreach($html_options as $key => $value) {
            $args .= ' '. $key .'="'. $value .'" ';
        }
        $html .= '<iframe '. $args .'></iframe>';
        echo $html;
    }
    
    function selectRank($id, $rank, $items, $html_options) {
        echo '<select ';
        foreach($html_options as $key => $value) {
            echo $key .'="'. $value .'"';
        }
        echo '<option value="beginning">'. $GLOBALS['Language']->getText('global', 'at_the_beginning') .'</option>';
        echo '<option value="end">'. $GLOBALS['Language']->getText('global', 'at_the_end') .'</option>';
        foreach($items as $item) {
            if ($item['id'] != $id) {
                echo '<option value="'. ($item['rank']+1) .'" '. ($rank == $item['rank']+1 ? 'selected="selected"' : '') .'>'. $GLOBALS['Language']->getText('global', 'after', $item['name']) .'</option>';
            }
        }
        echo '</select>';
    }
    
    function includeJavascriptFile($file) {
        $this->javascript_files[] = $file;
    }
    
    function _getFeedback() {
        $feedback = '';
        if (trim($GLOBALS['feedback']) !== '') {
            $feedback = '<H3><span class="feedback">'.$GLOBALS['feedback'].'</span></H3>';
        }
        return $feedback;
    }
    
	// Box Top, equivalent to html_box1_top()
	function box1_top($title,$echoout=1,$bgcolor='',$cols=2){
        	$return = '<TABLE class="boxtable" cellspacing="1" cellpadding="5" width="100%" border="0">
                        <TR class="boxtitle" align="center">
                                <TD colspan="'.$cols.'"><SPAN class=titlebar>'.$title.'</SPAN></TD>
                        </TR>
                        <TR class="boxitem">
                                <TD colspan="'.$cols.'">';
	        if ($echoout) {
        	        print $return;
	        } else {
                	return $return;
        	}
	}

	// Box Middle, equivalent to html_box1_middle()
	function box1_middle($title,$bgcolor='',$cols=2) {
        	return '
                                </TD>
                        </TR>
    
                        <TR class="boxtitle">
                                <TD colspan="'.$cols.'"><SPAN class=titlebar>'.$title.'</SPAN></TD>
                        </TR>
                        <TR class="boxitem">
                                <TD colspan="'.$cols.'">';
	}

	// Box Bottom, equivalent to html_box1_bottom()
	function box1_bottom($echoout=1) {
        	$return = '
                </TD>
                        </TR>
        </TABLE>
';
	        if ($echoout) {
        	        print $return;
	        } else {
                	return $return;
        	}
	}

	function generic_header_start($params) {

            global $G_USER, $G_SESSION,$group_id,$Language;

	        if (!$params['title']) {
        	        $params['title'] = $GLOBALS['sys_name'];
	        } else {
        	        $params['title'] = $GLOBALS['sys_name'].": " . $params['title'];
	        }
                $sys_url=get_server_url();

        	?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
        "http://www.w3.org/TR/REC-html40/loose.dtd">

<html lang="en">
  <head>
    <TITLE><?php echo $params['title']; ?></TITLE>
    <?php
        $em =& EventManager::instance();
        $em->processEvent("javascript_file", null);
        
        foreach ($this->javascript_files as $file) {
            echo '<script type="text/javascript" src="'. $file .'"></script>'."\n";
        }
    ?>
    <script type="text/javascript">
    <?php
        $em->processEvent("javascript", null);
    ?>
    </script>
        <SCRIPT language="JavaScript">
        <!--
        function help_window(helpurl) {
                HelpWin = window.open(helpurl,'HelpWindow','scrollbars=yes,resizable=yes,toolbar=no,height=640,width=800');
		HelpWin.focus();
        }
        // -->
        </SCRIPT>
        <link rel="stylesheet" type="text/css" href="<? echo util_get_css_theme(); ?>">
<?php
              if(isset($params['stylesheet']) && is_array($params['stylesheet'])) {
                  foreach($params['stylesheet'] as $css) {
                      print '<link rel="stylesheet" type="text/css" href="'.$css.'" />';
                      print "\n";
                  }
              }
?>
        <style type="text/css">
        <!--
        <?php
            $em->processEvent("cssstyle", null);
        ?>
        //-->
        </style>
        
<?php
            $em->processEvent("cssfile", null);
        
?>
        <link rel="SHORTCUT ICON" href="<? echo util_get_image_theme("favicon.ico"); ?>">
        <link rel="alternate" title="<? echo $GLOBALS['sys_name'].$Language->getText('include_layout','latest_news_rss'); ?>" href="<? echo $sys_url; ?>/export/rss_sfnews.php" type="application/rss+xml">
        <link rel="alternate" title="<? echo $GLOBALS['sys_name'].$Language->getText('include_layout','newest_releases_rss'); ?>" href="<? echo $sys_url; ?>/export/rss_sfnewreleases.php" type="application/rss+xml">
        <link rel="alternate" title="<? echo $GLOBALS['sys_name'].$Language->getText('include_layout','newest_projects_rss'); ?>" href="<? echo $sys_url; ?>/export/rss_sfprojects.php?type=rss&option=newest" type="application/rss+xml">
<?php
                // If in a project page, add a project news feed
                if ($GLOBALS['group_id']) {
                    $project=project_get_object($GLOBALS['group_id']);
                    $project_feed='        <link rel="alternate" title="'.$project->getPublicName().' '.$Language->getText('include_layout','latest_news_rss').'" href="'.$sys_url.'/export/rss_sfnews.php?group_id='.$GLOBALS['group_id'].'" type="application/rss+xml">';
                }
                if (isset($project_feed)) {
                    echo $project_feed;
                }
	}

	function generic_header_end($params) {
	?>
   </HEAD>
<?php
	}

	function generic_footer($params) {

        global $IS_DEBUG,$QUERY_COUNT,$Language;
        if ($IS_DEBUG && user_ismember(1,'A')) {
                echo "<CENTER><B><span class=\"highlight\">'.$Language->getText('include_layout','query_count').': $QUERY_COUNT</span></B></CENTER>";
                echo "<P>$GLOBALS[G_DEBUGQUERY]";
        }
        include($Language->getContent('layout/footer'));
        echo '</body>';
        echo '</html>';
	}

        function pv_header($params) {
            global $sys_datefmt;
	        $this->generic_header_start($params); 
                $this->generic_header_end($params); 
                echo '
<body class="bg_help">
';
                if(isset($params['pv']) && $params['pv'] < 2) {
                if (isset($params['title']) && $params['title']) {
                    echo '
<H2>'.$params['title'].' - '.format_date($sys_datefmt,time()).'</H2>
<HR>
';
                }
                }
        }

        function pv_footer($params) {
?>
</BODY>
</HTML>
<?php
}


	function header($params) {
	global $Language;
	
	        $this->generic_header_start($params); 

        	//themable someday?
	        $site_fonts='verdana,arial,helvetica,sans-serif';

        $this->generic_header_end($params); 
?>

<body leftmargin="0" rightmargin="0" topmargin="0" bottommargin="0" marginwidth="0" marginheight="0">
<?php


/*

        OSDN NAV BAR

*/
echo $this->getOsdnNavBar();

echo html_blankimage(5,100);
?>
<br>
<!-- start page body -->
<div align="center">
<table cellpadding="0" cellspacing="0" border="0" width="97%">

<!-- First line with borders and corners -->
           <tr>
               <td background="<? echo util_get_image_theme("upper_left_corner.png"); ?>" width="1%" height="26"><img src="<? echo util_get_image_theme("upper_left_corner.png"); ?>" width="16" height="26" alt=" "></td>
                <td background="<? echo util_get_image_theme("top_border.png"); ?>" align="left" colspan="3" width="99%"><a href="/"><img src="<? echo util_get_image_theme("codex_banner_lc.png"); ?>" border="0" alt="<?php echo $GLOBALS['sys_name'].' '.$Language->getText('include_layout','banner'); ?>"></a></td>
                <td><img src="<? echo util_get_image_theme("upper_right_corner.png"); ?>" width="16" height="26" alt=" "></td>
        </tr>


<!-- Second line with menus and content -->
        <tr>

                <td background="<? echo util_get_image_theme("left_border.png"); ?>" align="left" valign="bottom" alt=""><img src="<? echo util_get_image_theme("bottom_left_corner.png"); ?>" width="16" height="16" alt=""></td>

                <td colspan="3" >
<!-- start main body cell -->


        <div align="left">
        <table style=menus cellpadding="0" cellspacing="0" border="0" width="100%">

                <tr>
                    <td class="menuframe">

        <!-- VA Linux Stats Counter -->
        <?php
        if (!session_issecure()) {
                print '<IMG src="'.util_get_image_theme("clear.png").'" width=140 height=1 alt="'.$Language->getText('include_layout','counter').'"><BR>';
        } else {
                print html_blankimage(1,140) . '<br>';
        }
        ?>


        <!-- Company Logo here -->
        <P>
	<?php
	print '<center><IMG src="'.util_get_image_theme("organization_logo.png").'" alt="'.$GLOBALS['sys_org_name'].' '.$Language->getText('include_layout','logo').'"></center><BR>';
	?>

        <!-- menus -->
        <?php
        // html_blankimage(1,140);
        menu_print_sidebar($params);
        ?>
        <P>
        </TD>

        <td width="15" background="<? echo util_get_image_theme("fade.png"); ?>" nowrap>&nbsp;</td>
    
        <td class="contenttable">
        <BR>
<?php
        if (isset($params['group']) && $params['group']) {
            echo $this->project_tabs($params['toptab'],$params['group']);
        }
        echo $this->_getFeedback();
        $this->_feedback->display();
	}

    function feedback($feedback) {
        return '';
    }
    
	function footer($params) {
        if (!isset($params['showfeedback']) || $params['showfeedback']) {
            echo $this->_getFeedback();
        }
        ?>
        </div>
        <!-- end content -->
        </tr>
<!-- New row added for the thin black line at the bottom of the array -->
<tr><td background="<? echo util_get_image_theme("black.png"); ?>" colspan="4" align="center"><img src="<? echo util_get_image_theme("clear.png"); ?>" width="2" height="2" alt=" "></td> </tr>
        </table>

                </td>

                <td background="<? echo util_get_image_theme("right_border.png"); ?>" valign="bottom"><img src="<? echo util_get_image_theme("bottom_right_corner.png"); ?>" width="16" height="16" alt=" "></td>
        </tr>

</table>
</div>
<!-- themed page footer -->
<?php 
	$this->generic_footer($params);
	}



	function menuhtml_top($title) {
        	/*
                	Use only for the top most menu
	        */
        ?>
<table class="menutable">
        <tr>
                <td class="menutitle"><?php echo $title; ?><br></td>
        </tr>
        <tr>
                <td class="menuitem">
        <?php
	}


	function menuhtml_bottom() {
	        /*
        	        End the table
	        */
        	print '
                        <BR>
                        </td>
                </tr>
        </table>
';
	}

	function menu_entry($link, $title) {
        	print "\t".'<A class="menus" href="'.$link.'">'.$title.'</A> &nbsp;<img src="'.util_get_image_theme("point1.png").'" alt=" " width="7" height="7"><br>';
	}

        /*!     @function tab_entry
                @abstract Prints out the a themed tab, used by project_tabs
                @param  $url is the URL to link to
			$icon is the image to use (if the theme uses it) NOT USED
			$title is the title to use in the link tags
			$selected is a boolean to test if the tab is 'selected'
                @result text - echos HTML to the screen directly
        */
	function tab_entry($url, $icon='', $title='Home', $selected=0, $description=null) {
        	print '
                <A ';
	        if ($selected){
        	        print 'class=tabselect ';
	        } else {
        	        print 'class=tabs ';
	        }
                if (substr($url, 0, 1)!="/") {
                    // Absolute link -> open new window on click
                    print "target=_blank ";
                }
                if ($description) {
                    print "title=\"$description\" ";
                }
        	print 'href="'. $url .'">' . $title . '</A>&nbsp;|&nbsp;';
	}

	/*!     @function project_tabs
	        @abstract Prints out the project tabs, contained here in case
			we want to allow it to be overriden
	        @param 	$toptab is the tab currently selected ('short_name' of the service)
			$group is the group we should look up get title info
        	@result text - echos HTML to the screen directly
	*/
	function project_tabs($toptab,$group_id) {
		
	  global $sys_default_domain,$Language;
            
            // get group info using the common result set
            $project=project_get_object($group_id);
            if ($project->isError()) {
                //wasn't found or some other problem
                return;
            }

            print '<H2>'. $project->getPublicName() .' - ';
            
            if (isset($project->service_data_array[$toptab])) {
                echo $project->service_data_array[$toptab]['label'];
            }
            print '</H2>';

	    print '
        <P>
	<HR SIZE="1" NoShade>';
            $tabs = $this->_getProjectTabs($toptab, $project);
            foreach($tabs as $tab) {
                $this->tab_entry($tab['link'],$tab['icon'],$tab['label'],$tab['enabled'],$tab['description']);
            }

        	print '<HR SIZE="1" NoShade><P>';
	}

    function _getProjectTabs($toptab,&$project) {
      global $sys_default_domain;
        $tabs = array();
        $group_id = $project->getGroupId();
        reset($project->service_data_array);
        while (list($short_name,$service_data) = each($project->service_data_array)) {
            if ($short_name == "admin") {
                // for the admin service, we will check if the user is allowed to use the service
                // it means : 1) to be a super user, or
                //            2) to be project admin
                if (!user_is_super_user()) {
                    if (!user_isloggedin()) {
                        continue;   // we don't include the service in the $tabs
                    } else {
                        if (!user_ismember($group_id, 'A')) {
                            continue;   // we don't include the service in the $tabs
                        }
                    }
                }
            }
            
            if (!$service_data['is_used']) continue;
            if (!$service_data['is_active']) continue;
            // Get URL, and eval variables
            //$project->services[$short_name]->getUrl(); <- to use when service will be fully served by satellite
            if ($service_data['is_in_iframe']) {
                $link = '/service/?group_id='. $group_id .'&amp;id='. $service_data['service_id'];
            } else {
                $link = $service_data['link'];
            }
            if ($group_id==100) {
                if (strstr($link,'$projectname')) {
                    // NOTE: if you change link variables here, change them also in src/common/project/RegisterProjectStep_Confirmation.class.php and src/www/project/admin/servicebar.php
                    // Don't check project name if not needed.
                    // When it is done here, the service bar will not appear updated on the current page
                    $link=str_replace('$projectname',group_getunixname($group_id),$link);
                }
                $link=str_replace('$sys_default_domain',$GLOBALS['sys_default_domain'],$link);
                if ($GLOBALS['sys_force_ssl']) {
                    $sys_default_protocol='https'; 
                } else { $sys_default_protocol='http'; }
                $link=str_replace('$sys_default_protocol',$sys_default_protocol,$link);
                $link=str_replace('$group_id',$group_id,$link);
            }
            $enabled = (is_numeric($toptab) && $toptab == $service_data['service_id']) || ($short_name && ($toptab == $short_name));
            $tabs[] = array('link'        => $link,
                            'icon'        => null,
                            'label'       => $service_data['label'],
                            'enabled'     => $enabled,
                            'description' => $service_data['description']);
        }
        return $tabs;
    }
    
    /**
     * Echo the search box
     */
    function searchBox() {
        global $words,$forum_id,$group_id,$is_bug_page,$is_support_page,$Language,
            $is_pm_page,$is_snippet_page,$exact,$type_of_search,$atid, $is_wiki_page;
        // if there is no search currently, set the default
        if ( ! isset($type_of_search) ) {
            $exact = 1;
        }
        
        $output = "\t<CENTER>\n";
        $output .= "\t<FORM action=\"/search/\" method=\"post\">\n";
        
        $output .= "\t<SELECT name=\"type_of_search\">\n";
        if ($is_bug_page && $group_id) {
            $output .= "\t<OPTION value=\"bugs\"".( $type_of_search == "bugs" ? " SELECTED" : "" ).">".$Language->getText('include_menu','bugs')."</OPTION>\n";
        } else if ($is_pm_page && $group_id) {
            $output .= "\t<OPTION value=\"tasks\"".( $type_of_search == "tasks" ? " SELECTED" : "" ).">".$Language->getText('include_menu','tasks')."</OPTION>\n";
        } else if ($is_support_page && $group_id) {
            $output .= "\t<OPTION value=\"support\"".( $type_of_search == "support" ? " SELECTED" : "" ).">".$Language->getText('include_menu','supp_requ')."</OPTION>\n";
        } else if ($group_id && $forum_id) {
            $output .= "\t<OPTION value=\"forums\"".( $type_of_search == "forums" ? " SELECTED" : "" ).">".$Language->getText('include_menu','this_forum')."</OPTION>\n";
        } else if ($group_id && $atid) {
            $output .= "\t<OPTION value=\"tracker\"".( $type_of_search == "tracker" ? " SELECTED" : "" ).">".$Language->getText('include_menu','this_tracker')."</OPTION>\n";
        } else if ($group_id && $is_wiki_page) {
            $output .= "\t<OPTION value=\"wiki\"".( $type_of_search == "wiki" ? " SELECTED" : "" ).">".$Language->getText('include_menu','this_wiki')."</OPTION>\n";
        }
        
        $output .= "\t<OPTION value=\"soft\"".( $type_of_search == "soft" ? " SELECTED" : "" ).">".$Language->getText('include_menu','software_proj')."</OPTION>\n";
        $output .= "\t<OPTION value=\"snippets\"".( ($type_of_search == "snippets" || $is_snippet_page) ? " SELECTED" : "" ).">".$Language->getText('include_menu','code_snippets')."</OPTION>\n";
        $output .= "\t<OPTION value=\"people\"".( $type_of_search == "people" ? " SELECTED" : "" ).">".$Language->getText('include_menu','people')."</OPTION>\n";

        $em =& EventManager::instance();
        $GLOBALS['search_type_entry_output'] = '';
        $em->processEvent('search_type_entry', 
                          array('type_of_search' => $type_of_search));        
        $output .= $GLOBALS['search_type_entry_output'];

        $output .= "\t</SELECT>\n";
        
        $output .= "\t<BR>\n";
        $output .= "\t<INPUT TYPE=\"CHECKBOX\" NAME=\"exact\" VALUE=\"1\"".( $exact ? " CHECKED" : " UNCHECKED" )."> ".$Language->getText('include_menu','require_all_words')." \n";
        
        $output .= "\t<BR>\n";
        if ( isset($atid) ) {
            $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$atid\" NAME=\"atid\">\n";
        } 
        if ( isset($forum_id) ) {
            $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$forum_id\" NAME=\"forum_id\">\n";
        } 
        if ( isset($is_bug_page) ) {
           $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$is_bug_page\" NAME=\"is_bug_page\">\n";
        }
        if ( isset($is_support_page) ) {
           $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$is_support_page\" NAME=\"is_support_page\">\n";
        }
        if ( isset($is_pm_page) ) {
           $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$is_pm_page\" NAME=\"is_pm_page\">\n";
        }
        if ( isset($is_snippet_page) ) {
            $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$is_snippet_page\" NAME=\"is_snippet_page\">\n";
        }
	if ( isset($is_wiki_page) ) {
            $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$is_wiki_page\" NAME=\"is_wiki_page\">\n";
        }
        if ( isset($group_id) ) {
           $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$group_id\" NAME=\"group_id\">\n";
        }
        
        $output .= "\t<INPUT TYPE=\"text\" SIZE=\"16\" NAME=\"words\" VALUE=\"$words\">\n";
        $output .= "\t<BR>\n";
        $output .= "\t<INPUT TYPE=\"submit\" NAME=\"Search\" VALUE=\"".$Language->getText('include_menu','search')."\">\n";
        $output .= "\t</FORM>\n";
        $output .= "\t</CENTER>\n";
        echo $output;
    }
    
    function getOsdnNavBar() {
        $output = '
        <!-- OSDN navbar -->
        <div class="osdnnavbar">
        ';
        
        $motd = $GLOBALS['Language']->getContent('others/motd');
        if (!strpos($motd,"empty.txt")) { # empty.txt returned when no motd file found
            include($motd);
        } else {
            // MN : Before displaying the osdn nav drop down, we verify that the osdn_sites array exists
            if (isset($GLOBALS['osdn_sites'])) {
                $output .= '<span class="osdn">'.$GLOBALS['Language']->getText('include_layout','network_gallery').'&nbsp;:&nbsp;';
                // if less than 5 sites are defined, we only display the min number
                $output .= $this->_getOsdnRandpick($GLOBALS['osdn_sites'], min(5, count($GLOBALS['osdn_sites'])));
                $output .= '</span>';
            }
        }

        $output .= '</div>
        <!-- End OSDN NavBar -->
        ';
        return $output;
    }
    
    function _getOsdnRandpick($sitear, $num_sites = 1) {
        $output = '';
        shuffle($sitear);
        reset($sitear);
        $i = 0;
        while ( ( $i < $num_sites ) && (list($key,$val) = each($sitear)) ) {
            list($key,$val) = each($val);
            $output .= "&nbsp;&middot;&nbsp;<a href='$val' class='osdntext'>$key</a>\n";
            $i++;
        }
        $output .= '&nbsp;&middot;&nbsp;';
        return $output;
    }
    
    function getOsdnNavDropdown() {
        $output = '
        <!-- OSDN navdropdown -->
	    <script type="text/javascript">
	    function handle_navbar(index,form) {
	        if ( index > 1 ) {
	            window.location=form.options[index].value;
	        }
	    }
	    </script>';
        $output .= '<a href="'.get_server_url().'" class="osdn_codex_logo">';
        $output .= $this->getImage("codex_logo.png", array("width"=>"135", "height"=>"33", "hspace"=>"10", "alt"=>$GLOBALS['sys_default_domain'], "border"=>"0"));
        $output .= '<br /></a>';
        // MN : Before displaying the osdn nav drop down, we verify that the osdn_sites array exists
        if (isset($GLOBALS['osdn_sites'])) {
            $output .= '<form name="form1"><div>';
            $output .= '<select name="navbar" onChange="handle_navbar(selectedIndex,this)">';
            $output .= '   <option>------------</option>';
            reset ($GLOBALS['osdn_sites']);
            while (list ($key, $val) = each ($GLOBALS['osdn_sites'])) {
                list ($key, $val) = each ($val);
                $output .= '   <option value="'.$val.'">'.$key.'</option>';
            }
            $output .= '</select>';
            $output .= '</div></form>';
        }
        $output .= '<!-- end OSDN navdropdown -->';
        
        return $output;
    }
    
    function getImage($src,$args = array()) {
        GLOBAL $img_size;
        $return = '<img src="'.util_get_dir_image_theme().$src.'"';
        reset($args);
        while(list($k,$v) = each($args)) {
            $return .= ' '.$k.'="'.$v.'"';
        }
        
        // ## insert a border tag if there isn't one
        if (!isset($args['border']) || !$args['border']) $return .= (" border=0");
        
        // ## if no height AND no width tag, insert em both
        if ((!isset($args['height']) || !$args['height']) && 
                (!isset($args['width'])  || !$args['width'])) {
            /* Check to see if we've already fetched the image data */
            if($img_size){
                        if((!isset($img_size[$src]) || !$img_size[$src]) && is_file($GLOBALS['sys_urlroot'].util_get_dir_image_theme().$src)){
                    $img_size[$src] = @getimagesize($GLOBALS['sys_urlroot'].util_get_dir_image_theme().$src);
                }
            } else {
                if(is_file($GLOBALS['sys_urlroot'].util_get_dir_image_theme().$src)){		
                    $img_size[$src] = @getimagesize($GLOBALS['sys_urlroot'].util_get_dir_image_theme().$src);
                }
            }
            $return .= ' ' . $img_size[$src];
        }
        
        // ## insert alt tag if there isn't one
        if (!isset($args['alt']) || !$args['alt']) $return .= " alt=\"$src\"";
        
        $return .= ('>');
        return $return;
    }
}
?>
