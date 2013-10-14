<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Layout.class.php');
class TabbedLayout extends Layout {

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
            if((isset($GLOBALS['sys_use_project_registration']) && $GLOBALS['sys_use_project_registration'] ==1) || !isset($GLOBALS['sys_use_project_registration'])) {
                $output .= '<li><a href="/project/register.php">'.$GLOBALS['Language']->getText('include_menu','register_new_proj').'</a></li>';
            } 
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
                    <a  class="header_logo" href="/"><img src="'.$this->imgroot.'organization_logo.png" /></a>
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

		$this->generic_header($params); 
		?>


<body class="<?php echo $this->getClassnamesForBodyTag() ?>">
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

} else if ((isset($params['selected_top_tab']) && $params['selected_top_tab'] == '/my/') || 
           strstr(getStringFromServer('REQUEST_URI'),'/my/') ||  
           strstr(getStringFromServer('REQUEST_URI'),'/account/')) {
    ?>
    <tr>
        <td>&nbsp;</td>
        <td>
        <?php
        echo $this->tabGenerator(array(
                '/my/', 
                '/account/', 
                '/account/preferences.php'
            ), array(
                $Language->getText('my_index','my_dashboard'),
                $Language->getText('my_index','account_maintenance'),
                $Language->getText('account_options','preferences')
            ),
            true,
            (isset($params['selected_top_tab']) && $params['selected_top_tab'] == '/my/') ||
              strstr(getStringFromServer('REQUEST_URI'),'/my/') ? 0 :
                (strstr(getStringFromServer('REQUEST_URI'),'/account/preferences.php') ? 2 : 1)
            ,
            'WHITE', //deprecated
            '');


        ?>
        </td>
        <td>&nbsp;</td>
    </tr>
    <?php
}
$main_body_class = '';
if (isset($params['toptab']) && is_string($params['toptab'])) {
    $main_body_class = 'service-' . $params['toptab'];
}
?>
			<tr class="start_main_body_row">
				<td align="left" width="9"><img src="<?php echo $this->imgroot; ?>tabs/topleft-inner.png" height="9" width="9" alt="" /></td>
				<td><img src="<?php echo $this->imgroot; ?>clear.png" width="1" height="1" alt="" /></td>
				<td align="right" width="9"><img src="<?php echo $this->imgroot; ?>tabs/topright-inner.png" height="9" width="9" alt="" /></td>
			</tr>

			<tr class="main_body_row">
				<td><img src="<?php echo $this->imgroot; ?>clear.png" width="10" height="1" alt="" /></td>
                                <td valign="top" width="99%" class="contenttable <?=$main_body_class;?>">

	<?php
        echo $this->getBreadCrumbs();
        echo $this->getToolbar();
        $this->_feedback->display();
        echo $this->getNotificationPlaceholder();
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
			$pm = ProjectManager::instance();
            $project = $pm->getProject($params['group']);
			if ($project && is_object($project)) {
				if ($project->isError()) {

				} else {
					    $selected=array_search("/softwaremap/", $TABS_DIRS);
				}
			}
		} else if (strstr(getStringFromServer('REQUEST_URI'),'/my/')   ||
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
		} elseif ($selected_top_tab && (array_search($selected_top_tab,$TABS_DIRS) !== FALSE)) {
            $selected = array_search($selected_top_tab,$TABS_DIRS);
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
        $pm = ProjectManager::instance();
        $project=$pm->getProject($group_id);
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
