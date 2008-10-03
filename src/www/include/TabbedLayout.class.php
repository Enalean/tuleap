<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * TabbedLayout
 */
require_once('Layout.class.php');
$GLOBALS['Language']->loadlanguageMsg('themes/gforge-compat');
class TabbedLayout extends Layout {


	/**
	 * The root location for images
	 *
	 * @var		string	$imgroot
	 */
	var $imgroot;
	
	/**
	 * Layout() - Constructor
	 */
	function TabbedLayout($root) {
		$this->Layout($root);
        $this->imgroot = $root.'/images/';
	}

	/**
	 *	createLinkToUserHome() - Creates a link to a user's home page	
	 * 
	 *	@param	string	The user's user_name
	 *	@param	string	The user's realname
	 */
	function createLinkToUserHome($user_name, $realname) {
        $hp = CodeX_HTMLPurifier::instance();
		return '<a href="/users/'.$user_name.'/">'. $hp->purify($realname, CODEX_PURIFIER_CONVERT_HTML) .'</a>';
	}
    
    function getBodyHeader($params) {
        $output = '
        <table cellpadding="0" cellspacing="0" border="0" width="100%">
            <tr>
                <td class="header_osdn">'.$this->getOsdnNavBar().'</td>
                <td class="header_actions">
                    <ul>';
        if (user_isloggedin()) {
            
            $output .= '<li class="header_actions_nolink">'.$GLOBALS['Language']->getText('include_menu','logged_in').': '.user_getname().'</li>';
            $output .= '<li><a href="/account/logout.php">'.$GLOBALS['Language']->getText('include_menu','logout').'</a></li>';
            $output .= '<li><a href="/project/register.php">'.$GLOBALS['Language']->getText('include_menu','register_new_proj').'</a></li>';
            $request = HTTPRequest::instance();
            if (!$request->isPost()) {
                $bookmark_title = urlencode( str_replace($GLOBALS['sys_name'].': ', '', $params['title']));
                $output .= '<li class="bookmarkpage"><a href="/my/bookmark_add.php?bookmark_url='.urlencode($_SERVER['REQUEST_URI']).'&bookmark_title='.$bookmark_title.'">'.$GLOBALS['Language']->getText('include_menu','bookmark_this_page').'</a></li>';
            }
        } else {
            $output .= '<li class="header_actions_nolink highlight">'.$GLOBALS['Language']->getText('include_menu','not_logged_in').'</li>';
            $output .= '<li><a href="/account/login.php">'.$GLOBALS['Language']->getText('include_menu','login').'</a></li>';

            $em =& EventManager::instance();
            $display_new_user = true;
            $params = array('allow' => &$display_new_user);
            $em->processEvent('display_newaccount', $params);
            if ($display_new_user) {
                $output .= '<li><a href="/account/register.php">'.$GLOBALS['Language']->getText('include_menu','new_user').'</a></li>';
            }
        
        }
        $output .= '</ul>
                </td>
            </tr>
            <tr>
                <td class="header_logo">
                    <a  class="header_logo" href="/"><img src="'.$this->imgroot.'codex_banner_lc.png" /></a>
                </td>
                <td class="header_searchbox"><br />'.$this->getSearchBox().'</td>
            </tr>
        </table>';
        return $output;
    }
	/**
	 *	header() - "steel theme" top of page
	 *
	 * @param	array	Header parameters array
	 */
	function header($params) {
		global $Language;

		if (!$params['title']) {
			$params['title'] =  $GLOBALS['sys_name'];
		} else {
			$params['title'] =  $GLOBALS['sys_name'] . ': ' . $params['title'];
		}
		?>

<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="<?php echo $Language->getLanguageCode(); ?>">

  <head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $Language->getEncoding(); ?>" />
	<title><?php echo $params['title']; ?></title>
    <link rel="alternate" title="<? echo $GLOBALS['sys_name']. ' - ' .$Language->getText('include_layout','latest_news_rss'); ?>" href="<? echo get_server_url(); ?>/export/rss_sfnews.php" type="application/rss+xml">
    <link rel="alternate" title="<? echo $GLOBALS['sys_name']. ' - ' .$Language->getText('include_layout','newest_releases_rss'); ?>" href="<? echo get_server_url(); ?>/export/rss_sfnewreleases.php" type="application/rss+xml">
    <link rel="alternate" title="<? echo $GLOBALS['sys_name']. ' - ' .$Language->getText('include_layout','newest_projects_rss'); ?>" href="<? echo get_server_url(); ?>/export/rss_sfprojects.php?type=rss&option=newest" type="application/rss+xml">
    <?php
        //Add additionnal feeds
        $hp =& CodeX_HTMLPurifier::instance();
        foreach($this->feeds as $feed) {
            echo '<link rel="alternate" title="'. $hp->purify($feed['title']) .'" href="'. $feed['href'] .'" type="application/rss+xml">';
        }
    ?>
    <link rel="SHORTCUT ICON" href="<?php echo $this->imgroot; ?>favicon.ico">
    <?php
        $this->displayJavascriptElements();
    ?>
    <script language="JavaScript" type="text/javascript">
	<!--

	function help_window(helpurl) {
		HelpWin = window.open( helpurl,'HelpWindow','scrollbars=yes,resizable=yes,toolbar=no,height=740,width=1000');
	}
	// -->
	</script>
<?php
/*



	WARNING - changing this font call can affect
	INTERNATIONALIZATION


*/


		//gets font from Language Object
		$site_fonts=$GLOBALS['Language']->getFont();

	?>
    <link rel="stylesheet" href="<? echo util_get_css_theme(); ?>" type="text/css" />
    <!-- {{{ reimport style, only for netscape 4 compatibility -->
    <link rel="stylesheet" href="<? echo dirname(util_get_css_theme()).'/style.css'; ?>" type="text/css" />
    <!-- }}} -->
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
        $em->processEvent("cssstyle",null);
    ?>
	-->
    </style>
<?php $em->processEvent('cssfile',null); ?>
</head>

<body>
<div id="header"><?php echo $this->getBodyHeader($params); ?></div>

<table border="0" width="100%" cellspacing="0" cellpadding="0">

	<tr>
		<td>&nbsp;</td>
		<td colspan="3">

<?php echo $this->outerTabs($params); ?>

		</td>
		<td>&nbsp;</td>
	</tr>

	<tr class="row_below_outertabs">
		<td align="left" width="9"><img src="<?php echo $this->imgroot; ?>tabs/topleft.png" height="9" width="9" alt="" /></td>
		<td width="30"><img src="<?php echo $this->imgroot; ?>clear.png" width="30" height="1" alt="" /></td>
		<td><img src="<?php echo $this->imgroot; ?>clear.png" width="1" height="1" alt="" /></td>
		<td width="30"><img src="<?php echo $this->imgroot; ?>clear.png" width="30" height="1" alt="" /></td>
		<td align="right" width="9"><img src="<?php echo $this->imgroot; ?>tabs/topright.png" height="9" width="9" alt="" /></td>
	</tr>

	<tr class="outer_body_row">

		<!-- Outer body row -->

		<td ><img src="<?php echo $this->imgroot; ?>clear.png" width="10" height="1" alt="" /></td>
		<td valign="top" width="99%" colspan="3">

			<!-- Inner Tabs / Shell -->

			<table border="0" width="100%" cellspacing="0" cellpadding="0">
<?php


if (isset($params['group']) && $params['group']) {
    $this->warning_for_services_which_configuration_is_not_inherited($params['group'], $params['toptab']);
			?>
			<tr>
				<td>&nbsp;</td>
				<td>
				<?php

				echo $this->projectTabs($params['toptab'],$params['group']);

				?>
				</td>
				<td>&nbsp;</td>
			</tr>
			<?php

}

?>
			<tr class="start_main_body_row">
				<td align="left" width="9"><img src="<?php echo $this->imgroot; ?>tabs/topleft-inner.png" height="9" width="9" alt="" /></td>
				<td><img src="<?php echo $this->imgroot; ?>clear.png" width="1" height="1" alt="" /></td>
				<td align="right" width="9"><img src="<?php echo $this->imgroot; ?>tabs/topright-inner.png" height="9" width="9" alt="" /></td>
			</tr>

			<tr class="main_body_row">
				<td><img src="<?php echo $this->imgroot; ?>clear.png" width="10" height="1" alt="" /></td>
				<td valign="top" width="99%" class="contenttable">

	<?php
        $this->_feedback->display();
	}

	function footer($params) {

	?>

			<!-- end main body row -->


				</td>
				<td width="10"><img src="<?php echo $this->imgroot; ?>clear.png" width="2" height="1" alt="" /></td>
			</tr>
			<tr class="end_main_body_row">
				<td align="left"  width="9"><img src="<?php echo $this->imgroot; ?>tabs/bottomleft-inner.png" height="11" width="11" alt="" /></td>
				<td ><img src="<?php echo $this->imgroot; ?>clear.png" width="1" height="1" alt="" /></td>
				<td align="right" width="9"><img src="<?php echo $this->imgroot; ?>tabs/bottomright-inner.png" height="11" width="11" alt="" /></td>
			</tr>
            </table>

		<!-- end inner body row -->

		</td>
		<td width="10"><img src="<?php echo $this->imgroot; ?>clear.png" width="2" height="1" alt="" /></td>
	</tr>
	<tr class="end_inner_body_row">
		<td align="left" width="9"><img src="<?php echo $this->imgroot; ?>tabs/bottomleft.png" height="9" width="9" alt="" /></td>
		<td colspan="3"><img src="<?php echo $this->imgroot; ?>clear.png" width="1" height="1" alt="" /></td>
		<td align="right" width="9"><img src="<?php echo $this->imgroot; ?>tabs/bottomright.png" height="9" width="9" alt="" /></td>
	</tr>
    <?php echo $this->getCustomFooter(); ?>
</table>

<?php
        $this->generic_footer($params);
	}

    function getCustomFooter() {
        return '';
    }
    

	/**
	 * boxTop() - Top HTML box
	 *
	 * @param   string  Box title
	 * @param   bool	Whether to echo or return the results
	 * @param   string  The box background color
	 */
	function boxTop($title) {
        return '
		<!-- Box Top Start -->

		<table cellspacing="0" cellpadding="0" width="100%" border="0" class="boxtop">
		<tr align="center" class="boxtop_top">
			<td class="boxtop_left" valign="top" align="right" width="10" background="'.$this->imgroot.'box-topleft.png"><img src="'.$this->imgroot.'clear.png" width="10" height="20" /></td>
			<td class="boxtop_center" width="100%"><span class="titlebar">'.$title.'</span></td>
			<td class="boxtop_right" valign="top" width="10"><img src="'.$this->imgroot.'clear.png" width="10" height="20" /></td>
		</tr>
		<tr>
			<td colspan="3">
			<table class="boxtop_inner" cellspacing="0" cellpadding="2" width="100%" border="0">
				<tr align="left"">
					<td colspan="2" >

		<!-- Box Top End -->';
	}

	/**
	 * boxMiddle() - Middle HTML box
	 *
	 * @param   string  Box title
	 * @param   string  The box background color
	 */
	function boxMiddle($title) {
        return '
		<!-- Box Middle Start -->
					</td>
				</tr>
				<tr align="center" class="boxmiddle">
					<td colspan="2"><span class="titlebar">'.$title.'</span></td>
				</tr>
				<tr align="left" class="boxmiddle_inner">
					<td colspan="2">
		<!-- Box Middle End -->';
	}

	/**
	 * boxBottom() - Bottom HTML box
	 *
	 * @param   bool	Whether to echo or return the results
	 */
	function boxBottom() {
        return '
			<!-- Box Bottom Start -->
					</td>
				</tr>
			</table>
			</td>
		</tr>
		</table><br />
		<!-- Box Bottom End -->';
	}

	/**
	 * boxGetAltRowStyle() - Get an alternating row style for tables
	 *
	 * @param			   int			 Row number
	 */
	function boxGetAltRowStyle($i) {
		if ($i % 2 == 0) {
			return 'background="'.$this->imgroot.'vert-grad.png"';
		} else {
			return 'background="'.$this->imgroot.'box-grad.png"';
		}
	}

	function outerTabs($params) {
		global $Language,$sys_use_people;

		$TABS_DIRS[]='/';
		$TABS_TITLES[]=$Language->getText('menu','home');

		if (user_isloggedin()) {
            $TABS_DIRS[]='/my/';
            $TABS_TITLES[]=$Language->getText('menu','my_personal_page');
        }
        
        if ($GLOBALS['sys_use_trove'] != 0) {
            $TABS_DIRS[]='/softwaremap/';
            $TABS_TITLES[]=$Language->getText('menu','projectree');
        }

        if ($GLOBALS['sys_use_snippet'] != 0) {
            $TABS_DIRS[]='/snippet/';
            $TABS_TITLES[]=$Language->getText('menu','code_snippet');
        }

        if ($sys_use_people) {
            $TABS_DIRS[]='/people/';
            $TABS_TITLES[]=$Language->getText('menu','project_help_wanted');
        }
	if (user_ismember(1,'A')) {
            $TABS_DIRS[]='/admin/';
            $TABS_TITLES[]=$Language->getText('menu','admin');
	}

        $TABS_DIRS[]='/site/';
        $TABS_TITLES[]=$Language->getText('include_layout','Help');
        
        /*
		if (user_ismember($GLOBALS['sys_stats_group'])) {
			$TABS_DIRS[]='/reporting/';
			$TABS_TITLES[]=$Language->getText('menu','reporting');
		}
        */
        $selected_top_tab = isset($params['selected_top_tab']) ? $params['selected_top_tab'] : '';
		if(isset($params['group']) && $params['group']) {
			// get group info using the common result set
			$project = group_get_object($params['group']);
			if ($project && is_object($project)) {
				if ($project->isError()) {

				} else {
					$TABS_DIRS[]='/projects/'.$project->getUnixName().'/';
					$TABS_TITLES[]=$project->getPublicName();
					$selected=count($TABS_DIRS)-1;
				}
			}
		} elseif (strstr(getStringFromServer('REQUEST_URI'),'/my/')   ||
				strstr(getStringFromServer('REQUEST_URI'),'/themes/') ||
                strstr(getStringFromServer('REQUEST_URI'),'/account/') ) {
			$selected=array_search("/my/", $TABS_DIRS);
        } elseif (strstr(getStringFromServer('REQUEST_URI'),'softwaremap')) {
			$selected=array_search("/softwaremap/", $TABS_DIRS);
		} elseif (strstr(getStringFromServer('REQUEST_URI'),'/snippet/')) {
			$selected=array_search("/snippet/", $TABS_DIRS);
		} elseif (strstr(getStringFromServer('REQUEST_URI'),'/people/')) {
			$selected=array_search("/people/", $TABS_DIRS);
		} elseif (strstr(getStringFromServer('REQUEST_URI'),'/site/')) {
			$selected=array_search("/site/", $TABS_DIRS);
		} elseif (strstr(getStringFromServer('REQUEST_URI'),'/reporting/')) {
			$selected=array_search('/reporting/',$TABS_DIRS);
		} elseif ((strstr(getStringFromServer('REQUEST_URI'),'/admin/') || $selected_top_tab == 'admin') && user_ismember(1,'A')) {
			$selected=array_search('/admin/',$TABS_DIRS);;
		} else {
			$selected=0;
		}
		echo $this->tabGenerator($TABS_DIRS,$TABS_TITLES,false,$selected,null,'100%');

	}

	/**
	 *	projectTabs() - Prints out the project tabs, contained here in case
	 *		we want to allow it to be overriden
	 *
	 *	@param	string	Is the tab currently selected
	 *	@param	string	Is the group we should look up get title info
	 */
	function projectTabs($toptab,$group) {
        $this->project_tabs($toptab,$group);
    }
    
    function project_tabs($toptab,$group_id) {
        $project=project_get_object($group_id);
        if ($project->isError()) {
            //wasn't found or some other problem
            return;
        }
        $output   = '';
        $tabs     = $this->_getProjectTabs($toptab, $project);
        $nb       = count($tabs);
        $selected = false;
        $TABS_DIRS   = array();
        $TABS_TITLES = array();
        for($i = 0; $i < $nb ; $i++) {
            $TABS_DIRS[]   = $tabs[$i]['link'];
            $TABS_TITLES[] = $tabs[$i]['label'];
            if ($tabs[$i]['enabled'] === true) {
                $selected = $i;
            }
        }
        $output .= $this->tabGenerator($TABS_DIRS,$TABS_TITLES,true,$selected);
        echo $output;
	}

    /**
    * @param sel_tab_bgcolor DEPRECATED
    */
	function tabGenerator($TABS_DIRS,$TABS_TITLES,$nested=false,$selected=false,$sel_tab_bgcolor='WHITE',$total_width='100%') {

		$count=count($TABS_DIRS);
		$width=intval((100/$count));
		
		$return = '';
		
		$return .= '

		<!-- start tabs -->

		<table border="0" cellpadding="0" cellspacing="0" width="'.$total_width.'">
		<tr>';
		if ($nested) {
			$inner='bottomtab';
		} else {
			$inner='toptab';
		}
		$rowspan = '';
		for ($i=0; $i<$count; $i++) {
			if ($i == 0) {
				//
				//	this is the first tab, choose an image with end-name
				//
				$wassel=false;
				$issel=($selected==$i);
				$bgimg=(($issel)?'theme-'.$inner.'-selected-bg.png':'theme-'.$inner.'-notselected-bg.png');
		//		$rowspan=(($issel)?'rowspan="2" ' : '');

				$return .= '
					<td '.$rowspan.'valign="top" width="10" background="'.$this->imgroot . 'theme-'.$inner.'-end-'.(($issel) ? '' : 'not').'selected.png">'.
						'<img src="'.$this->imgroot . 'clear.png" height="25" width="10" alt="" /></td>'.
						'<td '.$rowspan.'background="'.$this->imgroot . $bgimg.'" width="'.$width.'%" align="center"><a class="'. (($issel)?'tabsellink':'tablink') .'" href="'.$TABS_DIRS[$i].'">'.$TABS_TITLES[$i].'</a></td>';
			} elseif ($i==$count-1) {
				//
				//	this is the last tab, choose an image with name-end
				//
				$wassel=($selected==$i-1);
				$issel=($selected==$i);
				$bgimg=(($issel)?'theme-'.$inner.'-selected-bg.png':'theme-'.$inner.'-notselected-bg.png');
		//		$rowspan=(($issel)?'rowspan="2" ' : '');
				//
				//	Build image between current and prior tab
				//
				$return .= '
					<td '.$rowspan.'colspan="2" valign="top" width="20" background="'.$this->imgroot . 'theme-'.$inner.'-'.(($wassel) ? '' : 'not').'selected-'.(($issel) ? '' : 'not').'selected.png">'.
						'<img src="'.$this->imgroot . 'clear.png" height="2" width="20" alt="" /></td>'.
						'<td '.$rowspan.'background="'.$this->imgroot . $bgimg.'" width="'.$width.'%" align="center" nowrap="nowrap"><a class="'. (($issel)?'tabsellink':'tablink') .'" href="'.$TABS_DIRS[$i].'">'.$TABS_TITLES[$i].'</a></td>';
				//
				//	Last graphic on right-side
				//
				$return .= '
					<td '.$rowspan.'valign="top" width="10" background="'.$this->imgroot . 'theme-'.$inner.'-'.(($issel) ? '' : 'not').'selected-end.png">'.
						'<img src="'.$this->imgroot . 'clear.png" height="2" width="10" alt="" /></td>';

			} else {
				//
				//	middle tabs
				//
				$wassel=($selected==$i-1);
				$issel=($selected==$i);
				$bgimg=(($issel)?'theme-'.$inner.'-selected-bg.png':'theme-'.$inner.'-notselected-bg.png');
		//		$rowspan=(($issel)?'rowspan="2" ' : '');
				//
				//	Build image between current and prior tab
				//
				$return .= '
					<td '.$rowspan.'colspan="2" valign="top" width="20" background="'.$this->imgroot . 'theme-'.$inner.'-'.(($wassel) ? '' : 'not').'selected-'.(($issel) ? '' : 'not').'selected.png">'.
						'<img src="'.$this->imgroot . 'clear.png" height="2" width="20" alt="" /></td>'.
						'<td '.$rowspan.'background="'.$this->imgroot . $bgimg.'" width="'.$width.'%" align="center"><a class="'. (($issel)?'tabsellink':'tablink') .'" href="'.$TABS_DIRS[$i].'">'.$TABS_TITLES[$i].'</a></td>';

			}
		}
		$return .= '</tr>';

		//
		//	Building a bottom row in this table, which will be darker
		//
		if ($selected == 0) {
			$beg_cols=0;
			$end_cols=((count($TABS_DIRS)*3)-3);
		} elseif ($selected == (count($TABS_DIRS)-1)) {
			$beg_cols=((count($TABS_DIRS)*3)-3);
			$end_cols=0;
		} else {
			$beg_cols=($selected*3);
			$end_cols=(((count($TABS_DIRS)*3)-3)-$beg_cols);
		}
		$return .= '<tr>';
		if ($beg_cols > 0) {
			$return .= '<td colspan="'.$beg_cols.'" height="1" class="below_tabs"><img src="'.$this->imgroot.'clear.png" height="1" width="10" /></td>';
		}
		$return .= '<td colspan="3" height="1" class="below_tabs_selected_'.$inner.'"><img src="'.$this->imgroot.'clear.png" height="1" width="10" /></td>';
		if ($end_cols > 0) {
			$return .= '<td colspan="'.$end_cols.'" height="1" class="below_tabs"><img src="'.$this->imgroot.'clear.png" height="1" width="10" /></td>';
		}
		$return .= '</tr>';
		return $return.'
		</table> 

		<!-- end tabs -->
';
	}

	function getSearchBox() {
        global $words,$forum_id,$group_id,$is_bug_page,$is_support_page,$Language,
            $is_pm_page,$is_snippet_page,$exact,$type_of_search,$atid;

		// if there is no search currently, set the default
		if ( ! isset($type_of_search) ) {
			$exact = 1;
		}

		$output = '
		<form action="/search/" method="post"><table style="text-align:left;float:right"><tr style="vertical-align:top;"><td>
		';
		$output .= '<select  style="font-size:0.8em" name="type_of_search">';
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
        }
        $output .= "\t<OPTION value=\"soft\"".( $type_of_search == "soft" ? " SELECTED" : "" ).">".$Language->getText('include_menu','software_proj')."</OPTION>\n";
        if ($GLOBALS['sys_use_snippet'] != 0) {
            $output .= "\t<OPTION value=\"snippets\"".( ($type_of_search == "snippets" || $is_snippet_page) ? " SELECTED" : "" ).">".$Language->getText('include_menu','code_snippets')."</OPTION>\n";
        }
        $output .= "\t<OPTION value=\"people\"".( $type_of_search == "people" ? " SELECTED" : "" ).">".$Language->getText('include_menu','people')."</OPTION>\n";
        $output .= "\t<OPTION value=\"wiki\"".( $type_of_search == "wiki" ? " SELECTED" : "" ).">".$Language->getText('include_menu','wiki')."</OPTION>\n";

        $search_type_entry_output = '';
        $em =& EventManager::instance();
        $eParams = array('type_of_search' => $type_of_search,
                         'output'         => &$search_type_entry_output);
        $em->processEvent('search_type_entry', $eParams);      
        $output .= $search_type_entry_output;

        $output .= "\t</select></td><td>";

        

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
        if ( isset($group_id) ) {
           $output .= "\t<INPUT TYPE=\"HIDDEN\" VALUE=\"$group_id\" NAME=\"group_id\">\n";
        }
		$output .= '';
        
		$output .= '<input style="font-size:0.8em" type="text" size="22" name="words" value="'. htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8') .'" /><br />';
        $output .= '<input type="CHECKBOX" name="exact" value="1"'.( $exact ? ' CHECKED' : ' UNCHECKED' ).'><span style="font-size:0.8em">'.$Language->getText('include_menu','require_all_words').'</span>';

		$output .= '</td><td>';
		$output .= '<input style="font-size:0.8em" type="submit" name="Search" value="'.$Language->getText('searchbox','search').'" />';
		$output .= '</td></tr></table></form>';
        return $output;
    }
    
	function searchBox() {
        print $this->getSearchBox();
	}
	
	//diplaying search box in body
    function bodySearchBox() {
    	//do nothing
    }
	
	/**
	 * feedback() - returns the htmlized feedback string when an action is performed.
	 *
	 * @param string feedback string
	 * @return string htmlized feedback
	 */
	function feedback($feedback) {
        if (!$feedback) {
			return '';
		} else {
			return '
				<h3><span class="feedback">'.strip_tags($feedback, '<br>').'</span></h3>';
		}
	}

    
    function menuhtml_top($title) {
        //do nothing, we are tabbed !
    }
    function menuhtml_bottom() {
        //do nothing, we are tabbed !
    }
	function menu_entry($link, $title) {
        //do nothing, we are tabbed !
    }


    //For GForge compatibility
	function box1_top($title,$echoout=1,$bgcolor='',$cols=2){
        if ($echoout) {
            print $this->boxTop($title);
        } else {
            return $this->boxTop($title);
        }
	}

	function box1_middle($title,$bgcolor='',$cols=2) {
		return $this->boxMiddle($title);
	}

	function box1_bottom($echoout=1) {
        if ($echoout) {
            print $this->boxBottom();
        } else {
            return $this->boxBottom();
        }
	}
}

?>
