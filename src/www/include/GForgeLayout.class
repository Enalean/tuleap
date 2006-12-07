<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * Base GForge layout class.
 *
 */
require_once('TabbedLayout.class.php');
require_once('common/event/EventManager.class.php');
class GForgeLayout extends TabbedLayout {

	/**
	 * The default main page content
	 */
	var $rootindex = 'index.php';

	/**
	 * The root location for images
	 *
	 * @var		string	$imgroot
	 */
    var $imgroot;
	var $COLOR_CONTENT_BACK= 'ffffff';
	var $COLOR_LTBACK1= '#eeeeef';
	var $COLOR_LTBACK2= '#fafafa';
	var $COLOR_SELECTED_TAB= '#e0e0e0';
	var $COLOR_HTMLBOX_TITLE = '#bbbbbb';
	var $COLOR_HTMLBOX_BACK = '#eaecef';
	var $FONT_CONTENT = 'helvetica';
	var $FONT_HTMLBOX_TITLE = 'helvetica';
	var $FONTCOLOR_HTMLBOX_TITLE = '#333333';
	var $FONTCOLOR_CONTENT = '#333333';
	var $FONTSIZE = 'small';
	var $FONTSIZE_SMALLER='x-small';
	var $FONTSIZE_SMALLEST='xx-small';
	var $FONTSIZE_HTMLBOX_TITLE = 'small';
	var $bgpri = array();

	/**
	 * Layout() - Constructor
	 */
	function GForgeLayout($root) {
		GLOBAL $bgpri;
		// Constructor for parent class...
		//# if ( file_exists($GLOBALS['sys_custom_path'] . '/index_std.php') )
		//#	$this->rootindex = $GLOBALS['sys_custom_path'] . '/index_std.php';
		$this->TabbedLayout($root);
	
		//determine font for this platform
		if (browser_is_windows() && browser_is_ie()) {

			//ie needs smaller fonts
			$this->FONTSIZE='x-small';
			$this->FONTSIZE_SMALLER='xx-small';
			$this->FONTSIZE_SMALLEST='7pt';

		} else if (browser_is_windows()) {

			//netscape on wintel
			$this->FONTSIZE='small';
			$this->FONTSIZE_SMALLER='x-small';
			$this->FONTSIZE_SMALLEST='x-small';

		} else if (browser_is_mac()){

			//mac users need bigger fonts
			$this->FONTSIZE='medium';
			$this->FONTSIZE_SMALLER='small';
			$this->FONTSIZE_SMALLEST='x-small';

		} else {

			//linux and other users
			$this->FONTSIZE='small';
			$this->FONTSIZE_SMALLER='x-small';
			$this->FONTSIZE_SMALLEST='xx-small';

		}

		$this->FONTSIZE_HTMLBOX_TITLE = $this->FONTSIZE;
	}

    
	function getRootIndex() {
		return $this->rootindex;
	}
	
	function advancedSearchBox($sectionsArray, $group_id, $words, $isExact) {
		global $Language;
		 // display the searchmask
		print '
		<form name="advancedsearch" action="'.getStringFromServer('PHP_SELF').'" method="post">
		<input type="hidden" name="search" value="1"/>
		<input type="hidden" name="group_id" value="'.$group_id.'"/>
		<div align="center"><br />
			<table border="0">
				<tr>
					<td colspan ="2">
						<input type="text" size="60" name="words" value="'.stripslashes(htmlspecialchars($words)).'" />
						<input type="submit" name="submitbutton" value="'.$Language->getText('advanced_search', 'search_button').'" />
					</td>
				</tr>
				<tr>
					<td valign="top">
						<input type="radio" name="mode" value="'.SEARCH__MODE_AND.'" '.($isExact ? 'checked="checked"' : '').' />'.$Language->getText('advanced_search', 'and_search').'
					</td>
					<td>
						<input type="radio" name="mode" value="'.SEARCH__MODE_OR.'" '.(!$isExact ? 'checked="checked"' : '').' />'.$Language->getText('advanced_search', 'or_search').'
					</td>
				</tr>
			</table><br /></div>'
		.$this->createUnderSections($sectionsArray).'
		</form>';


		//create javascript methods for select none/all
		print '
		<script type="text/javascript">
			<!-- method for disable/enable checkboxes
			function setCheckBoxes(parent, checked) {


				for (var i = 0; i < document.advancedsearch.elements.length; i++)
					if (document.advancedsearch.elements[i].type == "checkbox") 
							if (document.advancedsearch.elements[i].name.substr(0, parent.length) == parent)
								document.advancedsearch.elements[i].checked = checked;
				}
			//-->
		</script>
		';

	}
	
	function createUnderSections($sectionsArray) {
		global $Language;
		$countLines = 0;
		foreach ($sectionsArray as $section) {
			if(is_array($section)) {
				$countLines += (3 + count ($section));
			} else {
				//2 lines one for section name and one for checkbox
				$countLines += 3;
			}
		}
		$breakLimit = round($countLines/3);
		$break = $breakLimit;
		$countLines = 0;
		$return = '
			<table width="100%" border="0" cellspacing="0" cellpadding="2" style="background-color:'. $this->COLOR_HTMLBOX_TITLE .'">
				<tr>
					<td>
						<table width="100%" cellspacing="0" border="0">
							<tr style="font-weight: bold; background-color:'. $this->COLOR_HTMLBOX_TITLE .'">
								<td colspan="2">'.$Language->getText('advanced_search', 'search_in').':</td>
								<td align="right">'.$Language->getText('advanced_search', 'select').' <a href="javascript:setCheckBoxes(\'\', true)">'.$Language->getText('advanced_search', 'all').'</a> / <a href="javascript:setCheckBoxes(\'\', false)">'.$Language->getText('advanced_search', 'none').'</a></td>
							</tr>
							<tr height="20" style="background-color:'. $this->COLOR_CONTENT_BACK .'">
								<td colspan="3">&nbsp;</td>
							</tr>
							<tr align="center" valign="top" style="background-color:'. $this->COLOR_CONTENT_BACK .'">
								<td>';
		foreach($sectionsArray as $key => $section) {
			$oldcountlines = $countLines;
			if (is_array($section)) {
				$countLines += (3 + count ($section));
			} else {
				$countLines += 3;
			}
				
			if ($countLines >= $break) {
				//if the next block is so large that shifting it to the next column hits the breakpoint better
				//the second part of statement (behind &&) proofs, that no 4th column is added
				if ((($countLines - $break) >= ($break - $countLines)) && ((($break + $breakLimit)/$breakLimit) <= 3)) {
					$return .= '</td><td>';
					$break += $breakLimit;
				}
			}
		
			$return .= '<table width="90%" border="0" cellpadding="1" cellspacing="0" style="background-color:'. $this->COLOR_HTMLBOX_TITLE.'">
							<tr><td><table width="100%" border="0" cellspacing="0" cellpadding="3">
							<tr style="background-color:'. $this->COLOR_HTMLBOX_TITLE .'; font-weight: bold">
								<td cellspacing="0">
									<a href="#'.$key.'">'.$Language->getText('group', $key).'</a>'
							.'	</td>
								<td align="right">'
								.$Language->getText('advanced_search', 'select').' <a href="javascript:setCheckBoxes(\''.$key.'\', true)">'.$Language->getText('advanced_search', 'all').'</a> / <a href="javascript:setCheckBoxes(\''.$key.'\', false)">'.$Language->getText('advanced_search', 'none').'</a>
								</td>
							</tr>
							<tr style="background-color:'. $this->COLOR_CONTENT_BACK.'">
								<td colspan="2">';
								
			if (!is_array($section)) {
				$return .= '		<input type="checkbox" name="'.urlencode($key).'"';
				if (isset($GLOBALS[urlencode($key)]))
					$return .= ' checked="checked" ';
				$return .= ' /></input>'.$Language->getText('group', $key).'<br />';
			}
			else
				foreach($section as $underkey => $undersection) {
					$return .= '	<input type="checkbox" name="'.urlencode($key.$underkey).'"';
					if (isset($GLOBALS[urlencode($key.$underkey)]))
						$return .= ' checked ';
					$return .= '></input>'.$undersection.'<br />';				
					
				}
				
			$return .=		'	</td>
							</tr>
						</table></td></tr></table><br />';
						
			if ($countLines >= $break) {
				if (($countLines - $break) < ($break - $countLines)) {
					$return .= '</td><td width="33%">';
					$break += $breakLimit;
				}
			}
		}
		
		return $return.'		</td>
							</tr>
						</table></td></tr></table>';
	}

	/**
	 * beginSubMenu() - Opening a submenu.
	 *
	 * @return	string	Html to start a submenu.
	 */
	function beginSubMenu () {
		$return = '
			<p><strong>';
		return $return;
	}

	/**
	 * endSubMenu() - Closing a submenu.
	 *
	 * @return	string	Html to end a submenu.
	 */
	function endSubMenu () {
		$return = '</strong></p>';
		return $return;
	}

	/**
	 * printSubMenu() - Takes two array of titles and links and builds the contents of a menu.
	 *
	 * @param	   array   The array of titles.
	 * @param	   array   The array of title links.
	 * @return	string	Html to build a submenu.
	 */
	function printSubMenu ($title_arr,$links_arr) {
		$count=count($title_arr);
		$count--;
		
		$return = '';
		
		for ($i=0; $i<$count; $i++) {
			$return .= '
				<a href="'.$links_arr[$i].'">'.$title_arr[$i].'</a> | ';
		}
		$return .= '
				<a href="'.$links_arr[$i].'">'.$title_arr[$i].'</a>';
		return $return;
	}

	/**
	 * subMenu() - Takes two array of titles and links and build a menu.
	 *
	 * @param	   array   The array of titles.
	 * @param	   array   The array of title links.
	 * @return	string	Html to build a submenu.
	 */
	function subMenu ($title_arr,$links_arr) {
		$return  = $this->beginSubMenu () ;
		$return .= $this->printSubMenu ($title_arr,$links_arr) ;
		$return .= $this->endSubMenu () ;
		return $return;
	}

	/**
	 * multiTableRow() - create a mutlilevel row in a table
	 *
	 * @param	string	the row attributes
	 * @param	array	the array of cell data, each element is an array,
	 *				  	the first item being the text,
	 *					the subsequent items are attributes (dont include
	 *					the bgcolor for the title here, that will be
	 *					handled by $istitle
	 * @param	boolean is this row part of the title ?
	 *
	 */
	 function multiTableRow($row_attr, $cell_data, $istitle) {
		$return= '
		<tr '.$row_attr;
		if ( $istitle ) {
			$return .=' align="center" bgcolor="'. $this->COLOR_HTMLBOX_TITLE .'"';
		}
		$return .= '>';
		for ( $c = 0; $c < count($cell_data); $c++ ) {
			$return .='<td ';
			for ( $a=1; $a < count($cell_data[$c]); $a++) {
				$return .= $cell_data[$c][$a].' ';
			}
			$return .= '>';
			if ( $istitle ) {
				$return .='<font color="'.$this->FONTCOLOR_HTMLBOX_TITLE.'"><strong>';
			}
			$return .= $cell_data[$c][0];
			if ( $istitle ) {
				$return .='</strong></font>';
			}
			$return .= '</td>';

		}
		$return .= '</tr>
		';

		return $return;
	}
	
	/**
	 * getThemeIdFromName()
	 *
	 * @param	string  the dirname of the theme
	 * @return	integer the theme id	
	 */
	/*
    function getThemeIdFromName($dirname) {
	 	$res=db_query("SELECT theme_id FROM themes WHERE dirname='$dirname'");
	        return db_result($res,0,'theme_id');
	}
    */
    
    
    
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
			$params['title'] =  $GLOBALS['sys_name'] . ":" . $params['title'];
		}
		print '<?xml version="1.0" encoding="' . $Language->getEncoding(). '"?>';
		?>

<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="<?php echo $Language->getLanguageCode(); ?>">

  <head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $Language->getEncoding(); ?>" />
	<title><?php echo $params['title']; ?></title>
    <?php
        $em =& EventManager::instance();
        $em->processEvent("javascript_file", null);
        
        foreach ($this->javascript_files as $file) {
            echo '<script type="text/javascript" src="'. $file .'"></script>'."\n";
        }
    ?>
	<script language="JavaScript" type="text/javascript">
	<!--
	function help_window(helpurl) {
		HelpWin = window.open( helpurl,'HelpWindow','scrollbars=yes,resizable=yes,toolbar=no,height=400,width=400');
	}
	// -->
	<?php 
        $em =& EventManager::instance();
        $em->processEvent("javascript",false) ; ?>
	</script>
<?php
/*



	WARNING - changing this font call can affect
	INTERNATIONALIZATION


*/


		//gets font from LANG Object
		// XXXX $site_fonts=$GLOBALS['Language']->getFont();

	?>
<!-- BEGIN codex stylesheet in GF Layout -->
<link rel="stylesheet" type="text/css" href="<? echo util_get_css_theme(); ?>">
<link rel="SHORTCUT ICON" href="<? echo util_get_image_theme("favicon.ico"); ?>">
<!-- END   codex stylesheet in GF Layout -->
<style type="text/css">
	<!--
    /*{{{ Missing rules */
.footer           { font-size: <?php echo $this->FONTSIZE_SMALLEST; ?>; }

.calendar_font_othermonth,
.calendar_font,
.calendar_font_month,
.calendar_font_day,
.osdn,
.osdntext,
.smaller,
.header_actions ul      { font-size: <?php echo $this->FONTSIZE_SMALLER; ?>;  }

body,
.menutable,
.disable,
.command,
.small,
.titlebar,
.normal,
.slogan,
.maintitlebar     { font-size:<?php echo $this->FONTSIZE; ?>;     }

/* Vertical spacing */
.vspace-bottom { margin-bottom: 3ex; }
.vspace-top { margin-top: 3ex; }
.vspace-both { margin-top: 3ex; margin-bottom: 3ex; }

    
.center { text-align: center; }
.alignright { text-align: right }
.left { text-align: left; }
.error { font-weight: bold; color: #980000; }
.feedback { color: red; }
.bold { font-weight: bold; }
.disable { color: gray;}
.highlight { color: red; }
.newproject { background-color: #EEEEEE; }
.top_up { color: #009900; }
.top_down { color: #CC0000; }
.command { font-family: courier,sans-serif;}
.subdomain { color: green; }
.bg_confirmdownload { background-color: #F7F7F7; }

PRE,TT { font-family: courier,sans-serif; }


/******** priorities **********/
.priora { background-color: #dadada; }
.priorb { background-color: #dacaca; }
.priorc { background-color: #dababa; }
.priord { background-color: #daaaaa; }
.priore { background-color: #da8a8a; }
.priorf { background-color: #da7a7a; }
.priorg { background-color: #da6a6a; }
.priorh { background-color: #da5a5a; }
.priori { background-color: #da4a4a; }

/********   forum    **********/
.thread { background-color: #DDDDDD; }
.threadbody { background-color: #EEEEEE; }
.threadmsg { background-color: #E3E3E3; }

/* .footer { color: #333333; } */
.standardtable { width: 99%; border-spacing: 0; border: 0; color: #333333; }

/* content frame specific */
.contenttable { background-color: #F7F7F7; vertical-align: top; width: 99%; border-spacing: 0; border: 0; }
.row_below_outertabs,
.outer_body_row,
.end_inner_body_row,
.below_tabs_selected_toptab{ 
	background:#e8e8e0;
}
.start_main_body_row,
.main_body_row,
.end_main_body_row,
.below_tabs_selected_bottomtab{
	background:#f7f7f7;
}
.below_tabs {
	background:#909090;
}

/* left menu specific */
.menuframe { background-color: #e8e8e0; vertical-align: top; horitontal-align: left; width: 60px; }
.menutable { background-color: #e8e8e0; vertical-align: top; width: 150px; border-spacing: 0; border: 0; }
.menutitle { vertical-align: top; font-weight: bold; text-align: left; color: #333333; }
.menuitem { vertical-align: top; text-align: right; color: #333333; }

/* group menu specific (see tabs and tabselect too) */
.groupmenutable { background-color: #e8e8e0; width: 100% ; border-top: thin solid #000000; border-bottom: thin solid #000000; }

/* classic box */
.boxtable { width: 99%; vertical-align: top; border-spacing: 1px; border: 0; }
.boxtitle { background-color: #E0DDD2; font-weight: bold; text-align: center; text-transform: capitalize; color: #000000; }
.boxitem {  background-color: transparent; text-align: left; color: #333333; }
.boxitemalt { text-align: left; background-color: #ffffff; }
.boxhighlight { background-color: #ffe0db; text-align: left; color: #333333; }

.boxtop {}
.boxtop_top {}
.boxtop_left {background:url('../images/box-topleft.png') top right no-repeat;}
.boxtop_center {background:#e0ddd2 url('../images/box-grad.png') top left repeat-x;}
.boxtop_right {background:url('../images/box-topright.png') top left no-repeat;}
.boxtop_inner {background:transparent url('../images/vert-grad.png') top left repeat-x;}
.boxmiddle {background:#e0ddd2 url('../images/box-grad.png') top left repeat-x;}
.boxmiddle_inner {background:transparent url('../images/vert-grad.png') top left repeat-x;}

.boxspace { font-size: 2pt; }
.maintitlebar { color: #FFFFFF;}
.slogan { font-weight: bold;}
.osdn { font-family: verdana,arial,helvetica,sans-serif; font-weight: bold; color: #ffffff; }
.osdntext, .osdntext:link { font-weight: bold; text-decoration: none; color: #ffffff; }
.osdntext:visited { font-weight: bold; text-decoration: none; color: #ffffff; }
.osdntext:hover { font-weight: bold; text-decoration: none; color: #ffffff; }
.footer { color: white; }

.tabs { color: #000000; }
.tabs:visited { color: #000000; }
.tabs:hover { color:#FF0000; } 
.tabselect { color: #000000; font-weight: bold; }
.tabselect:visited { color: #000000; font-weight: bold; }
.tabselect:hover { color:#FF0000; font-weight: bold; }

.titlebar { text-decoration:none; color:#000000; font-weight: bold;}
.develtitle { color:#000000; font-weight: bold; }
.legallink { color:#000000; font-weight: bold; }

.sortbutton { color: #000000; text-decoration: none; }
.sortbutton:hover { text-decoration: underline; }
.sortbutton:visited { color: #000000;}

/* calendar (fixed font size) */
.calendar_month { background-color: #E0DDD2; }
.calendar_day { background-color: #FFE6B5; }
.calendar_currentday { background-color: #FFFFA3; }
.calendar_nextmonth { background-color: #DBEAF5; }
.calendar_daymonth { background-color: white; } 
.calendar_font_othermonth { color: gray; }
.calendar_font { color: black; }
.calendar_font_month { color: black; }
.calendar_font_day { color: black; }

/* Help classes */
.bg_help {
  background-color: #ffffff;
}

/* text with tooltips (e.g. artifact fields) */
.tooltip { text-decoration:none; color: #000000}
.tooltip:hover { text-decoration:none; color: #ff0000}
    /*}}}*/

BODY {
		margin-top: 3;
		margin-left: 3;
		margin-right: 3;
		margin-bottom: 3;
		background-image: url("<?php echo $this->imgroot; ?>theme-top-blue.png");
	}
	ol,ul,p,body,td,tr,th,form { font-family: <?php echo $site_fonts; ?>; font-size:<?php echo $this->FONTSIZE; ?>;
		color: <?php echo $this->FONTCOLOR_CONTENT ?>; }

    .priora { background-color: #dadada; }
    .priorb { background-color: #dacaca; }
    .priorc { background-color: #dababa; }
    .priord { background-color: #daaaaa; }
    .priore { background-color: #da8a8a; }
    .priorf { background-color: #da7a7a; }
    .priorg { background-color: #da6a6a; }
    .priorh { background-color: #da5a5a; }
    .priori { background-color: #da4a4a; }

	h1 { font-size: x-large; font-family: <?php echo $site_fonts; ?>; }
	h2 { font-size: large; font-family: <?php echo $site_fonts; ?>; }
	h3 { font-size: medium; font-family: <?php echo $site_fonts; ?>; }
	h4 { font-size: small; font-family: <?php echo $site_fonts; ?>; }
	h5 { font-size: x-small; font-family: <?php echo $site_fonts; ?>; }
	h6 { font-size: xx-small; font-family: <?php echo $site_fonts; ?>; }

	pre,tt { font-family: courier,sans-serif }

	a:link { text-decoration:none; color: #0000be }
	a:visited { text-decoration:none; color: #0000be }
	a:active { text-decoration:none }
	a:hover { text-decoration:underline; color:red }

	.titlebar { color: black; text-decoration: none; font-weight: bold; }
	a.tablink { color: black; text-decoration: none; font-weight: bold; font-size: <?php echo $this->FONTSIZE_SMALLER; ?>; }
	a.tablink:visited { color: black; text-decoration: none; font-weight: bold; font-size: <?php echo $this->FONTSIZE_SMALLER; ?>; }
	a.tablink:hover { text-decoration: none; color: black; font-weight: bold; font-size: <?php echo $this->FONTSIZE_SMALLER; ?>; }
	a.tabsellink { color: #0000be; text-decoration: none; font-weight: bold; font-size: <?php echo $this->FONTSIZE_SMALLER; ?>; }
	a.tabsellink:visited { color: #0000be; text-decoration: none; font-weight: bold; font-size: <?php echo $this->FONTSIZE_SMALLER; ?>; }
	a.tabsellink:hover { text-decoration: none; color: #0000be; font-weight: bold; font-size: <?php echo $this->FONTSIZE_SMALLER; ?>; }
	<?php $em->processEvent("cssstyle",$this) ; ?>
	-->
</style>

</head>

<body>

<table border="0" width="100%" cellspacing="0" cellpadding="0">

	<tr>
		<td><a href="/"><?php echo html_image('logo.png',array('border'=>'0','width'=>198,'height'=>52,)); ?></a></td>
		<td><?php echo $this->searchBox(); ?></td>
		<td align="right"><?php
			if (user_isloggedin()) {
				?>
				<b><a style="color: #FFFFFF" href="/account/logout.php"><?php echo $Language->getText('common','logout'); ?></a></b><br />
				<b><a style="color: #FFFFFF" href="/account/"><?php echo $Language->getText('common','myaccount'); ?></a></b>
				<?php
			} else {
				?>
				<b><a style="color: #FFFFFF" href="/account/login.php"><?php echo $Language->getText('common','login'); ?></a></b><?php
              $em =& EventManager::instance();
              $display_new_user = true;
              $params = array('allow' => &$display_new_user);
              $em->processEvent('display_newaccount', $params);
              if ($display_new_user) {
                ?>
                <br />
				<b><a style="color: #FFFFFF" href="/account/register.php"><?php echo $Language->getText('common','newaccount'); ?></a></b>
				<?php
              }
			}

		?></td>
		<td>&nbsp;&nbsp;</td>
	</tr>

</table>

<table border="0" width="100%" cellspacing="0" cellpadding="0">

	<tr>
		<td>&nbsp;</td>
		<td colspan="3">

<?php echo $this->outerTabs($params); ?>

		</td>
		<td>&nbsp;</td>
	</tr>

	<tr>
		<td align="left" bgcolor="#E0E0E0" width="9"><img src="<?php echo $this->imgroot; ?>tabs/topleft.png" height="9" width="9" alt="" /></td>
		<td bgcolor="#E0E0E0" width="30"><img src="<?php echo $this->imgroot; ?>clear.png" width="30" height="1" alt="" /></td>
		<td bgcolor="#E0E0E0"><img src="<?php echo $this->imgroot; ?>clear.png" width="1" height="1" alt="" /></td>
		<td bgcolor="#E0E0E0" width="30"><img src="<?php echo $this->imgroot; ?>clear.png" width="30" height="1" alt="" /></td>
		<td align="right" bgcolor="#E0E0E0" width="9"><img src="<?php echo $this->imgroot; ?>tabs/topright.png" height="9" width="9" alt="" /></td>
	</tr>

	<tr>

		<!-- Outer body row -->

		<td bgcolor="#E0E0E0"><img src="<?php echo $this->imgroot; ?>clear.png" width="10" height="1" alt="" /></td>
		<td valign="top" width="99%" bgcolor="#E0E0E0" colspan="3">

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

}

?>
			<tr>
				<td align="left" bgcolor="#ffffff" width="9"><img src="<?php echo $this->imgroot; ?>tabs/topleft-inner.png" height="9" width="9" alt="" /></td>
				<td bgcolor="#ffffff"><img src="<?php echo $this->imgroot; ?>clear.png" width="1" height="1" alt="" /></td>
				<td align="right" bgcolor="#ffffff" width="9"><img src="<?php echo $this->imgroot; ?>tabs/topright-inner.png" height="9" width="9" alt="" /></td>
			</tr>

			<tr>
				<td bgcolor="#ffffff"><img src="<?php echo $this->imgroot; ?>clear.png" width="10" height="1" alt="" /></td>
				<td valign="top" width="99%" bgcolor="white">

	<?php

	}

	function footer($params) {

	?>

			<!-- end main body row -->


				</td>
				<td width="10" bgcolor="#ffffff"><img src="<?php echo $this->imgroot; ?>clear.png" width="2" height="1" alt="" /></td>
			</tr>
			<tr>
				<td align="left" bgcolor="#E0E0E0" width="9"><img src="<?php echo $this->imgroot; ?>tabs/bottomleft-inner.png" height="11" width="11" alt="" /></td>
				<td bgcolor="#ffffff"><img src="<?php echo $this->imgroot; ?>clear.png" width="1" height="1" alt="" /></td>
				<td align="right" bgcolor="#E0E0E0" width="9"><img src="<?php echo $this->imgroot; ?>tabs/bottomright-inner.png" height="11" width="11" alt="" /></td>
			</tr>
			</table>

		<!-- end inner body row -->

		</td>
		<td width="10" bgcolor="#E0E0E0"><img src="<?php echo $this->imgroot; ?>clear.png" width="2" height="1" alt="" /></td>
	</tr>
	<tr>
		<td align="left" bgcolor="#E0E0E0" width="9"><img src="<?php echo $this->imgroot; ?>tabs/bottomleft.png" height="9" width="9" alt="" /></td>
		<td bgcolor="#E0E0E0" colspan="3"><img src="<?php echo $this->imgroot; ?>clear.png" width="1" height="1" alt="" /></td>
		<td align="right" bgcolor="#E0E0E0" width="9"><img src="<?php echo $this->imgroot; ?>tabs/bottomright.png" height="9" width="9" alt="" /></td>
	</tr>
</table>

<br />

<?php
	    $this->generic_footer($params);
	}



	/**
	 * listTableTop() - Takes an array of titles and builds the first row of a new table.
	 *
	 * @param	   array   The array of titles
	 * @param	   array   The array of title links
	 */
	function listTableTop ($title_arr,$links_arr=false) {
		$return = '
		<table cellspacing="0" cellpadding="0" width="100%" border="0">
		<tr align="center">
	<!--		<td valign="top" align="right" width="10" background="'.$this->imgroot.'box-grad.png"><img src="'.$this->imgroot.'box-topleft.png" width="10" height="75"></td> -->
			<td background="'.$this->imgroot.'box-grad.png">
		<table width="100%" border="0" cellspacing="1" cellpadding="2">
			<tr>';

		$count=count($title_arr);
		if ($links_arr) {
			for ($i=0; $i<$count; $i++) {
				$return .= '
				<td align="center"><a class="sortbutton" href="'.$links_arr[$i].'"><span style="color:'.
				$this->FONTCOLOR_HTMLBOX_TITLE.'"><strong>'.$title_arr[$i].'</strong></span></a></td>';
			}
		} else {
			for ($i=0; $i<$count; $i++) {
				$return .= '
				<td align="center"><span style="color:'.
				$this->FONTCOLOR_HTMLBOX_TITLE.'"><strong>'.$title_arr[$i].'</strong></span></td>';
			}
		}
		return $return.'</tr>';
	}

	function listTableBottom() {
		return '</table></td>
			<!-- <td valign="top" align="right" width="10" background="'.$this->imgroot.'box-grad.png"><img src="'.$this->imgroot.'box-topright.png" width="10" height="75"></td> -->
			</tr></table>';
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

		<table cellspacing="0" cellpadding="0" width="100%" border="0" background="'.$this->imgroot.'vert-grad.png">
		<tr align="center">
			<td valign="top" align="right" width="10" background="'.$this->imgroot.'box-topleft.png"><img src="'.$this->imgroot.'clear.png" width="10" height="20"></td>
			<td width="100%" background="'.$this->imgroot.'box-grad.png"><span class="titlebar">'.$title.'</span></td>
			<td valign="top" width="10" background="'.$this->imgroot.'box-topright.png"><img src="'.$this->imgroot.'clear.png" width="10" height="20"></td>
		</tr>
		<tr>
			<td colspan="3">
			<table cellspacing="2" cellpadding="2" width="100%" border="0">
				<tr align="left">
					<td colspan="2">

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
				<tr align="center">
					<td colspan="2" background="'.$this->imgroot.'box-grad.png"><span class="titlebar">'.$title.'</span></td>
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
			<!-- Box Bottom Start -->
					</td>
				</tr>
			</table>
			</td>
		</tr>
		</table><br />
		<!-- Box Bottom End -->';
	}

}

// Local Variables:
// mode: php
// c-file-style: "bsd"
// End:

?>
