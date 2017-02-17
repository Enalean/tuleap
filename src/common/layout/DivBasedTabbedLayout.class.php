<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/TreeNode/TreeNode.class.php');
require_once('BuildMenuVisitor.class.php');
require_once('common/layout/Layout.class.php');

class DivBasedTabbedLayout extends Layout
{
    /**
     * The root location for images
     *
     * @var		string	$imgroot
     */
    var $imgroot;

	/**
	 * DivBasedTabbedLayout - Constructor
	 */
    function __construct($root) {
        // Parent constructor
        parent::__construct($root);
        $this->imgroot = $root.'/images/';
    }

    function getBodyHeader($params) {
/* A 2x2 table header
 Organisation logo		|  users actions
 Separator or any image	|  Search box
NB: Original OsdnNavBar has been removed from first cell. <td align="center">'.$this->getOsdnNavBar()
*/
        $output = '
        <table cellpadding="0" cellspacing="0" border="0" width="100%">
            <tr>
                <td class="header_logo">'. $this->getBodyHeaderLogo() .'</td>
                <td class="header_actions">';
        $output .= $this->getBodyHeaderActions($params);
        $output .= '<div class="header_searchbox">'.$this->getSearchBox().'</div>
                </td>
            </tr>
        </table>';
        return $output;
    }
    function getBodyHeaderLogo() {
        return '<a  href="/"><img src="'.$this->imgroot.'organization_logo.png" /></a>';
    }
    function getBodyHeaderActions($params) {
        $html = '';
        $html .= '<ul>';
        if (user_isloggedin()) {
            $html       .= '<li class="header_actions_nolink">'.$GLOBALS['Language']->getText('include_menu','logged_in').': '.user_getname().'</li>';
            $logout_csrf = new CSRFSynchronizerToken('logout_action');
            $logout_link = '/account/logout.php?' . http_build_query(array($logout_csrf->getTokenName() => $logout_csrf->getToken()));
            $html       .= '<li><a href="' . $logout_link . '">'.$GLOBALS['Language']->getText('include_menu','logout').'</a></li>';
            if((isset($GLOBALS['sys_use_project_registration']) && $GLOBALS['sys_use_project_registration'] ==1) || !isset($GLOBALS['sys_use_project_registration'])) {
                $html .= '<li><a href="/project/register.php">'.$GLOBALS['Language']->getText('include_menu','register_new_proj').'</a></li>';
            } 
            if (!HTTPRequest::instance()->isPost()) {
                $add_bookmark_url = http_build_query(array(
                    'bookmark_url'   => $_SERVER['REQUEST_URI'],
                    'bookmark_title' => str_replace($GLOBALS['sys_name'].': ', '', $params['title'])
                ));
                $html .= '<li class="bookmarkpage"><a href="/my/bookmark_add.php?'.$add_bookmark_url.'">'.$GLOBALS['Language']->getText('include_menu','bookmark_this_page').'</a></li>';
            }
        } else {
            $html .= '<li class="header_actions_nolink highlight">'.$GLOBALS['Language']->getText('include_menu','not_logged_in').'</li>';

            $login_url = '/account/login.php';
            if ($_SERVER['REQUEST_URI'] != $login_url) {
                $login_url .= '?return_to='.urlencode($_SERVER['REQUEST_URI']);
            }

            $html .= '<li><a href="'.$this->purifier->purify($login_url).'">'.$GLOBALS['Language']->getText('include_menu','login').'</a></li>';
            $em =& EventManager::instance();
            $display_new_user = true;
            $em->processEvent('display_newaccount', array('allow' => &$display_new_user));
            if ($display_new_user) {
                $html .= '<li><a href="/account/register.php">'.$GLOBALS['Language']->getText('include_menu','new_user').'</a></li>';
            }
        
        }
        $html .= '</ul>';
        return $html;
    }
    
	/**
	 *	header() - "steel theme" top of page
	 *
	 * @param	array	Header parameters array
	 */
	function header(array $params) {
		global $Language;

        $this->generic_header($params);
        ?>

<body class="<?php echo $this->getClassnamesForBodyTag($params) ?>">
<div id="wrapper">
<?php
    $deprecated = $this->getBrowserDeprecatedMessage();
    if ($deprecated) {
        echo '<div id="browser_deprecated">'.$deprecated.'</div>';
    }
    echo $this->getMOTD();
?>

<div id="header"><?php echo $this->getBodyHeader($params); ?></div>

<div id="navigation">
<?php 
echo $this->outerTabs($params);

$main_body_class = '';
if (isset($params['toptab']) && is_string($params['toptab'])) {
    $main_body_class = 'service-' . $params['toptab'];
}

?>
</div> <!-- headertab -->

<div class="main_body_row <?= $main_body_class;?>">

  <div class="contenttable">
	<?php
        echo $this->getBreadCrumbs();
        echo $this->getToolbar();
      echo $this->_getFeedback();
      $this->_feedback->display();
      echo $this->getNotificationPlaceholder();
	}

	function footer(array $params) {
        if (!isset($params['showfeedback']) || $params['showfeedback']) {
            echo $this->_getFeedback();
        }
	?>        
  </div> <!-- class="contenttable"> -->      
</div> <!-- class="main_body_row"> -->
</div> <!-- wrapper -->
    <?php echo $this->getCustomFooter(); ?>
    
<?php
        $this->generic_footer($params);
	}

    function getCustomFooter() {
        return '';
    }
    
    function _getTogglePlusForWidgets() {
        return 'pointer_right.png';
    }
    function _getToggleMinusForWidgets() {
        return 'pointer_down.png';
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

        <table cellspacing="1" width="100%" border="0" class="boxtable">
          <tr class="boxtitle">
            <td class="boxtop_center" width="100%"><span class="titlebar">'.$title.'</span></td>
          </tr>
          <tr>
            <td>
              <table cellspacing="0" cellpadding="2" width="100%" border="0">
                <tr align="left"">
                  <td>
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
                <tr align="center" class="boxitem">
                  <td><span class="titlebar">'.$title.'</span></td>
                </tr>
                <tr align="left">
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
                  </td>
                </tr>
              </table>
            </td>
          </tr> 
        <!-- Box Bottom Start -->					
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
		global $Language;
		$selected_top_tab = '';
		if (isset($params['selected_top_tab'])) {
		    $selected_top_tab = $params['selected_top_tab'];
		}
        $menuTree = new TreeNode();
        $sthSelected = false;

        $menuTree->addChild(new TreeNode(array('link'=>'/'
                                                ,'title'=>$Language->getText('menu','home'))));
        // We need to keep a reference on this node in order to set the
        // selected value in the data. See bottom of this function.
        $homeNode =& $menuTree->getChild(0);
        

		if (user_isloggedin()) {
            $selected = ((isset($params['selected_top_tab']) && $params['selected_top_tab'] == '/my/') || 
                         strstr(getStringFromServer('REQUEST_URI'),'/my/') ||  
                         strstr(getStringFromServer('REQUEST_URI'),'/account/'));            
            $sthSelected = ($sthSelected || $selected);
            $mynode = new TreeNode(array('link'=>'/my/'
                                         ,'title'=>$Language->getText('menu','my_personal_page')
                                         ,'selected'=>$selected));

            if($selected) {
                $selected = (isset($params['selected_top_tab']) && $params['selected_top_tab'] == '/my/') ||  (boolean) strstr(getStringFromServer('REQUEST_URI'),'/my/');
                $mynode->addChild(new TreeNode(array('link'=>'/my/'
                                                     ,'title'=>$Language->getText('my_index','my_dashboard')
                                                     ,'selected'=>$selected)));
                
                $selected = (boolean) strstr(getStringFromServer('REQUEST_URI'),'/account/');
                
                $mynode->addChild(new TreeNode(array('link'=>'/account/'
                                                     ,'title'=>$Language->getText('my_index','account_maintenance')
                                                     ,'selected'=>$selected)));
            }
            $menuTree->addChild($mynode);
            
        } else {
            $selected = (boolean) strstr(getStringFromServer('REQUEST_URI'),'/my/');
            $sthSelected = ($sthSelected || $selected);
            $menuTree->addChild(new TreeNode(array('link'=>'/my/'
                                                    ,'title'=>$Language->getText('menu','my_personal_page')
                                                    ,'selected'=>$selected)));
	}
        
	if ($GLOBALS['sys_use_trove'] != 0 || (isset($params['group']) && $params['group'])) {
        $selected = false;
        if (isset($params['group']) && $params['group']) {
            // get group info using the common result set
			$pm = ProjectManager::instance();
            $project = $pm->getProject($params['group']);
			if ($project && is_object($project)) {
				if ($project->isError()) {
                    die('is error');
				} else {
                    $sthSelected = true;

                    $projTree = $this->project_tabs($params['toptab'],$params['group']);

                    $projTree->setData(array('link'=>'/softwaremap/'
                                             ,'title'=>$Language->getText('menu','projectree')
                                             ,'selected'=>true));
                                             //'link'=>'/projects/'.$project->getUnixName().'/'
                                             //,'title'=>$project->getPublicName()
                                             //,'selected' => true));
                    
                    $menuTree->addChild($projTree);
				}
			}
        } else {
            $selected = (boolean) strstr(getStringFromServer('REQUEST_URI'),'softwaremap');
            $sthSelected = ($sthSelected || $selected);
            $menuTree->addChild(new TreeNode(array('link'=>'/softwaremap/'
                                                    ,'title'=>$Language->getText('menu','projectree')
                                                    ,'selected'=>$selected)));
        }
	}

		if (user_ismember(1,'A')) {
            $selected = strpos(getStringFromServer('REQUEST_URI'),'/admin/') === 0 || $selected_top_tab === 'admin';
            $sthSelected = ($sthSelected || $selected);
            $menuTree->addChild(new TreeNode(array('link'=>'/admin/'
                                                   ,'title'=>$Language->getText('menu','admin')
                                                   ,'selected'=>$selected)));
		}

        $selected = (boolean) (strstr(getStringFromServer('REQUEST_URI'),'/help/') || $selected_top_tab === 'help');
        $sthSelected = ($sthSelected || $selected);
        $menuTree->addChild(new TreeNode(array('link'=>'/help/'
                                               ,'title'=>$Language->getText('include_layout','Help')
                                               ,'selected'=>$selected)));
        
        $additional_tabs = array();
        include $GLOBALS['Language']->getContent('layout/extra_tabs', null, null, '.php');
        foreach ($additional_tabs as $t) {
            $sthSelected = ($sthSelected || $t['selected']);
            $menuTree->addChild(new TreeNode($t));
        }

        // Set selected value for 'home' link (this is the selected tab 
        // if no other was previously selected)
        $homeNodeData =& $homeNode->getData();
        $homeNodeData['selected'] = !$sthSelected;

        $buildMenuVisitor = new BuildMenuVisitor();
        $menuTree->accept($buildMenuVisitor);
        echo $buildMenuVisitor->getHtml();
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
        $menuTree = new TreeNode();
        $output   = '';
        $tabs     = $this->_getProjectTabs($toptab, $project);
        $nb       = count($tabs);
        $selected = false;
        for($i = 0; $i < $nb ; $i++) {
            if ($tabs[$i]['enabled'] === true) {
                $selected = true;
            }
            $menuTree->addChild(new TreeNode(array('link'=>$tabs[$i]['link']
                                                   ,'title'=>$tabs[$i]['label']
                                                   ,'selected'=>$tabs[$i]['enabled'])));
        }
        //$output .= $this->tabGenerator($TABS_DIRS,$TABS_TITLES,true,$selected, 2);
        //echo $output;        
        return $menuTree;
	}

    /**
    * @param sel_tab_bgcolor DEPRECATED
    */
	function tabGenerator($TABS_DIRS,$TABS_TITLES,$nested=false,$selected=false,$level) {
		$count=count($TABS_DIRS);
		$width=intval((100/$count));
		
		$return = '';
		
		$return .= '

        <!-- start tabs -->
        <ul id="level_'.$level.'">';

		if ($nested) {
			$inner='bottomtab';
		} else {
			$inner='toptab';
		}
		$rowspan = '';
		for ($i=0; $i<$count; $i++) {			
				//
				//	middle tabs
				//
				$wassel=($selected==$i-1);
				$issel=($selected==$i);				
                
                if($issel) 
                    $address = '<span>'.$TABS_TITLES[$i].'</span>';
                else
                    $address = '<a href="'.$TABS_DIRS[$i].'">'.$TABS_TITLES[$i].'</a>';

                $return .= '
          <li>'.$address.'</li>';
                
		}
		$return .= '
        </ul>
        <!-- end tabs -->
';
    return $return;    
	}

    //diplaying search box in body
    function bodySearchBox() {
    }
}

?>
