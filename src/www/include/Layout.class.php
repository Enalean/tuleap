<?php   

//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//              
// 


require_once('common/include/Response.class.php');

require_once('common/event/EventManager.class.php');

require_once('common/include/CodeX_HTMLPurifier.class.php');


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
    var $feeds;
    
	// Constuctor
	function Layout($root) {
		GLOBAL $bgpri;
        
		// Constructor for parent class...
		$this->Response();
        
        $this->feeds = array();
        $this->javascript = array();
        
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
    
    function getChartColors() {
        return array(
            'lightsalmon',
            'palegreen',
            'paleturquoise',
            'lightyellow',
            'thistle',
            'steelblue1',
            'palevioletred1',
            'palegoldenrod',
            'wheat1',
            'gold',
            'olivedrab1',
            'lightcyan',
            'lightcyan3',
            'lightgoldenrod1',
            'rosybrown',
            'mistyrose',
            'silver',
            'aquamarine',
            'pink1',
            'lemonchiffon3',
            'skyblue',
            'mintcream',
            'lavender',
            'linen',
            'yellowgreen',
            'burlywood',
            'coral',
            'mistyrose3',
            'slategray1',
            'yellow1',
        );
    }
    
    function getChartBackgroundColor() {
        return "white";
    }
    
    function getChartMainColor() {
        return "#444444";
    }
    
    public function getGanttLateBarColor() {
        return 'salmon';
    }
    
    public function getGanttErrorBarColor() {
        return 'yellow';
    }
    
    public function getGanttGreenBarColor() {
        return 'darkgreen';
    }
    
    public function getGanttTodayLineColor() {
        return 'red';
    }
    
    public function getGanttHeaderColor() {
        return 'gray9';
    }
    
    public function getGanttBarColor() {
        return 'steelblue1';
    }
    
    public function getGanttMilestoneColor() {
        return 'orange';
    }
    
    function redirect($url) {
       $is_anon = session_hash() ? false : true;
       $fb = $GLOBALS['feedback'] || count($this->_feedback->logs);
       if (($is_anon && (headers_sent() || $fb)) || (!$is_anon && headers_sent())) {
            $this->header(array('title' => 'Redirection'));
            echo '<p>'. $GLOBALS['Language']->getText('global', 'return_to', array($url)) .'</p>';
            echo '<script type="text/javascript">';
            if (!$fb) {
                echo 'setTimeout(function() {';
            }
            echo " location.href = '". $url ."';";
            if (!$fb) {
                echo '}, 5000);';
            }
            echo '</script>';
            $this->footer(array());
        } else {
            if (!$is_anon && !headers_sent() && $fb) {
                $this->_serializeFeedback();
            }
            // Protect against CRLF injections,
            // This seems to be fixed in php 4.4.2 and 5.1.2 according to
            // http://php.net/header
            if(strpos($url, "\n")) {
                trigger_error('HTTP header injection detected. Abort.', E_USER_ERROR);
            } else {
                header('Location: '. $url);
            }
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
        echo '>';
        echo '<option value="beginning">'. $GLOBALS['Language']->getText('global', 'at_the_beginning') .'</option>';
        echo '<option value="end">'. $GLOBALS['Language']->getText('global', 'at_the_end') .'</option>';
        foreach($items as $i => $item) {
            if ($item['id'] != $id) {
                echo '<option value="'. ($item['rank']+1) .'" '. (isset($items[$i + 1]) && $items[$i + 1]['id'] == $id ? 'selected="selected"' : '') .'>'. $GLOBALS['Language']->getText('global', 'after', $item['name']) .'</option>';
            }
        }
        echo '</select>';
    }
    
    function includeJavascriptFile($file) {
        $this->javascript[] = array('file' => $file);
    }
    function includeJavascriptSnippet($snippet) {
        $this->javascript[] = array('snippet' => $snippet);
    }
    function includeCalendarScripts() {
        $this->includeJavascriptSnippet("var useLanguage = '". substr(UserManager::instance()->getCurrentUser()->getLocale(), 0, 2) ."';");
        $this->includeJavascriptFile("/scripts/datepicker/datepicker.js");
    }

    function addFeed($title, $href) {
        $this->feeds[] = array('title' => $title, 'href' => $href);
    }
    
    function _getFeedback() {
        $feedback = '';
        if (trim($GLOBALS['feedback']) !== '') {
            $feedback = '<H3><span class="feedback">'.$GLOBALS['feedback'].'</span></H3>';
        }
        return $feedback;
    }
    
    function widget(&$widget, $layout_id, $readonly, $column_id, $is_minimized, $display_preferences, $owner_id, $owner_type) {
        echo '<div class="widget" id="widget_'. $widget->id .'-'. $widget->getInstanceId() .'">';
        echo '<div class="widget_titlebar '. ($readonly?'':'widget_titlebar_handle') .'">';
        echo '<div class="widget_titlebar_title">'. $widget->getTitle() .'</div>';
        if (!$readonly) {
            echo '<div class="widget_titlebar_close"><a href="/widgets/updatelayout.php?owner='. $owner_type.$owner_id .'&amp;action=widget&amp;name['. $widget->id .'][remove]='. $widget->getInstanceId() .'&amp;column_id='. $column_id .'&amp;layout_id='. $layout_id .'">'. $this->getImage('ic/close.png', array('alt' => 'X')) .'</a></div>';
            if ($is_minimized) {
                echo '<div class="widget_titlebar_maximize"><a href="/widgets/updatelayout.php?owner='. $owner_type.$owner_id .'&amp;action=maximize&amp;name['. $widget->id .']='. $widget->getInstanceId() .'&amp;column_id='. $column_id .'&amp;layout_id='. $layout_id .'">'. $this->getImage($this->_getTogglePlusForWidgets(), array('alt' => '+')) .'</a></div>';
            } else {
                echo '<div class="widget_titlebar_minimize"><a href="/widgets/updatelayout.php?owner='. $owner_type.$owner_id .'&amp;action=minimize&amp;name['. $widget->id .']='. $widget->getInstanceId() .'&amp;column_id='. $column_id .'&amp;layout_id='. $layout_id .'">'. $this->getImage($this->_getToggleMinusForWidgets(), array('alt' => '-')) .'</a></div>';
            }
            if (strlen($widget->getPreferences())) {
                echo '<div class="widget_titlebar_prefs"><a href="/widgets/updatelayout.php?owner='. $owner_type.$owner_id .'&amp;action=preferences&amp;name['. $widget->id .']='. $widget->getInstanceId() .'&amp;layout_id='. $layout_id .'">'. $GLOBALS['Language']->getText('widget', 'preferences_title') .'</a></div>';
            }
        }
        if ($widget->hasRss()) {
            echo '<div class="widget_titlebar_rss"><a href="/widgets/widget.php?owner='. $owner_type.$owner_id .'&amp;action=rss&amp;name['. $widget->id .']='. $widget->getInstanceId() .'">rss</a></div>';
        }
        echo '</div>';
        $style = '';
        if ($is_minimized) {
            $style = 'display:none;';
        }
        echo '<div class="widget_content" style="'. $style .'">';
        if (!$readonly && $display_preferences) {
            echo '<div class="widget_preferences">'. $widget->getPreferencesForm($layout_id, $owner_id, $owner_type) .'</div>';
        }
        echo $widget->getContent() .'</div>';
        echo '</div>';
    }
    function _getTogglePlusForWidgets() {
        return 'ic/toggle_plus.png';
    }
    function _getToggleMinusForWidgets() {
        return 'ic/toggle_minus.png';
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
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $Language->getEncoding(); ?>" />
    <TITLE><?php echo $params['title']; ?></TITLE>
    <?php $this->displayJavascriptElements() ?>
        <SCRIPT language="JavaScript">
        <!--
        function help_window(helpurl) {
                HelpWin = window.open(helpurl,'HelpWindow','scrollbars=yes,resizable=yes,toolbar=no,height=740,width=1000');
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
        $em = EventManager::instance();
            $em->processEvent("cssstyle", null);
        ?>
        //-->
        </style>
        
<?php
            $em->processEvent("cssfile", null);
        
?>
        <link rel="SHORTCUT ICON" href="<? echo util_get_image_theme("favicon.ico"); ?>">
        <link rel="alternate" title="<? echo $GLOBALS['sys_name']. ' - ' .$Language->getText('include_layout','latest_news_rss'); ?>" href="<? echo $sys_url; ?>/export/rss_sfnews.php" type="application/rss+xml">
        <link rel="alternate" title="<? echo $GLOBALS['sys_name']. ' - ' .$Language->getText('include_layout','newest_releases_rss'); ?>" href="<? echo $sys_url; ?>/export/rss_sfnewreleases.php" type="application/rss+xml">
        <link rel="alternate" title="<? echo $GLOBALS['sys_name']. ' - ' .$Language->getText('include_layout','newest_projects_rss'); ?>" href="<? echo $sys_url; ?>/export/rss_sfprojects.php?type=rss&option=newest" type="application/rss+xml">
<?php
                // If in a project page, add a project news feed
                if ($GLOBALS['group_id']) {
                    $project=project_get_object($GLOBALS['group_id']);
                    if (isset($params['toptab'])) {
                        $this->warning_for_services_which_configuration_is_not_inherited($GLOBALS['group_id'], $params['toptab']);
                    }
                    $project_feed='        <link rel="alternate" title="'.$project->getPublicName().' '.$Language->getText('include_layout','latest_news_rss').'" href="'.$sys_url.'/export/rss_sfnews.php?group_id='.$GLOBALS['group_id'].'" type="application/rss+xml">';
                }
                if (isset($project_feed)) {
                    echo $project_feed;
                }
        //Add additionnal feeds
        $hp =& CodeX_HTMLPurifier::instance();
        foreach($this->feeds as $feed) {
            echo '<link rel="alternate" title="'. $hp->purify($feed['title']) .'" href="'. $feed['href'] .'" type="application/rss+xml">';
        }
	}
    
    function displayJavascriptElements() {
        $em =& EventManager::instance();
        $em->processEvent("javascript_file", null);
        
        foreach ($this->javascript as $js) {
            reset($js);
            list($type, $content) = each($js);
            if ($type == 'file') {
                echo '<script type="text/javascript" src="'. $content .'"></script>'."\n";
            } else {
                echo '<script type="text/javascript">'. $content .'</script>';
            }
        }
        echo '<script type="text/javascript">
        ';
        $em->processEvent("javascript", null);
        echo '
        </script>';
    }
    
    function getDatePicker($id, $name, $value, $size = 10, $maxlength = 10) {
        $hp = CodeX_HTMLPurifier::instance();
        return '<input type="text" 
                       class="highlight-days-67 format-y-m-d divider-dash no-transparency" 
                       id="'.  $hp->purify($id, CODEX_PURIFIER_CONVERT_HTML)  .'" 
                       name="'. $hp->purify($name, CODEX_PURIFIER_CONVERT_HTML) .'" 
                       size="'. $hp->purify($size, CODEX_PURIFIER_CONVERT_HTML) .'" 
                       maxlength="'. $hp->purify($maxlength, CODEX_PURIFIER_CONVERT_HTML) .'" 
                       value="'. $hp->purify($value, CODEX_PURIFIER_CONVERT_HTML) .'">';
    }
    
    function warning_for_services_which_configuration_is_not_inherited($group_id, $service_top_tab) {
        $project=project_get_object($group_id);
        if ($project->isTemplate()) {
            switch($service_top_tab) {
            case 'admin':
            case 'forum':
            case 'docman':
            case 'cvs':
            case 'svn':
            case 'file':
            case 'tracker':
            case 'wiki':
            case 'salome':
                break;
            default:
                $this->addFeedback('warning', $GLOBALS['Language']->getText('global', 'service_conf_not_inherited'));
                break;
            }
        }
    }
    
	function generic_header_end($params) {
	?>
   </HEAD>
<?php
	}

	function generic_footer($params) {

        global $Language;
        include($Language->getContent('layout/footer'));
        	
        if ( user_ismember(1,'A') && $GLOBALS['DEBUG_MODE'] ) {
                $debug_compute_tile=microtime(true) - $GLOBALS['debug_time_start'];
                echo '<span class="debug">'.$Language->getText('include_layout','query_count').": ";
                echo $GLOBALS['DEBUG_DBPHP_QUERY_COUNT'] + $GLOBALS['DEBUG_DAO_QUERY_COUNT'];
                echo " (". $GLOBALS['DEBUG_DBPHP_QUERY_COUNT'] ." + ". $GLOBALS['DEBUG_DAO_QUERY_COUNT'] .")<br>";
                echo "Page generated in ".$debug_compute_tile." seconds</debug>";
        }
          
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
            if ((string)$short_name == "admin") {
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
            $hp =& CodeX_HTMLPurifier::instance();
            $tabs[] = array('link'        => $link,
                            'icon'        => null,
                            'label'       => $hp->purify($service_data['label']),
                            'enabled'     => $enabled,
                            'description' => $hp->purify($service_data['description']));
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
        if ($GLOBALS['sys_use_snippet'] != 0) {
            $output .= "\t<OPTION value=\"snippets\"".( ($type_of_search == "snippets" || $is_snippet_page) ? " SELECTED" : "" ).">".$Language->getText('include_menu','code_snippets')."</OPTION>\n";
        }
        $output .= "\t<OPTION value=\"people\"".( $type_of_search == "people" ? " SELECTED" : "" ).">".$Language->getText('include_menu','people')."</OPTION>\n";

        $search_type_entry_output = '';
        $em =& EventManager::instance();
        $eParams = array('type_of_search' => $type_of_search,
                         'output'         => &$search_type_entry_output);
        $em->processEvent('search_type_entry', $eParams);      
        $output .= $search_type_entry_output;

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
        
        $output .= '<INPUT TYPE="text" SIZE="16" NAME="words" VALUE="'. htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8') .'">';
        $output .= "\t<BR>\n";
        $output .= "\t<INPUT TYPE=\"submit\" NAME=\"Search\" VALUE=\"".$Language->getText('include_menu','search')."\">\n";
        $output .= "\t</FORM>\n";
        $output .= "\t</CENTER>\n";
        echo $output;
    }
    
    //diplaying search box in body
    function bodySearchBox() {
    	$this->searchBox();
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
