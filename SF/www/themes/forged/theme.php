<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

// Make sure '/etc/local.inc' is required in pre.php!

/* Dependencies upon this file:
	include/menu.php
	include/html.php
	include/utils.php
	include/osdn.php
*/

/*
	Set up the priority color array one time only
*/
$bgpri[1] = '#dadada';
$bgpri[2] = '#dad0d0';
$bgpri[3] = '#dacaca';
$bgpri[4] = '#dac0c0';
$bgpri[5] = '#dababa';
$bgpri[6] = '#dab0b0';
$bgpri[7] = '#daaaaa';
$bgpri[8] = '#da9090';
$bgpri[9] = '#da8a8a';

//Define all the defaults for this theme
// array used for the color select in forms
$theme_arcolor['Theme LightBG Default'] = '#EAECEF';
$theme_arcolor['Theme LightBG2 Default'] = '#FAFAFA';
$theme_arcolor['Theme TitleBar Default'] = '#D1D5D7';
$theme_arcolor['Theme Box Background Default'] = '#EEEEF8';
$theme_arcolor['Theme Background Default'] = '#FFFFFF';

$theme_arfont['Theme TitleBar Default'] = 'Helvetica';
$theme_arfont['Theme Box Default'] = 'Helvetica';
$theme_arfont['Theme Body Default'] = 'Helvetica';

// array used for the font color select in forms
$theme_arfontcolor['Theme TitleBar Default'] = '#333333';
$theme_arfontcolor['Theme Box Default'] = '#333333';
$theme_arfontcolor['Theme Body Default'] = '#333333';

// array used for the font face select in forms
$theme_arfontsize['Theme TitleBar Default'] = '12pt';
$theme_arfontsize['Theme Box Default'] = '12pt';
$theme_arfontsize['Theme Body Default'] = '12pt';

//Define all the icons for this theme
$theme_icons['Summary'] = 'ic/anvil24.png';
$theme_icons['Homepage'] = 'ic/home.png';
$theme_icons['Forums'] = 'ic/notes.png';
$theme_icons['Bugs'] = 'ic/bug.png';
$theme_icons['Support'] = 'ic/support.png';
$theme_icons['Patches'] = 'ic/patch.png';
$theme_icons['Lists'] = 'ic/mail.png';
$theme_icons['Tasks'] = 'ic/index.png';
$theme_icons['Docs'] = 'ic/docman.png';
$theme_icons['Surveys'] = 'ic/survey.png';
$theme_icons['News'] = 'ic/news.png';
$theme_icons['CVS'] = 'ic/convert.png';
$theme_icons['Files'] = 'ic/save.png';
 
function html_box1_top($title,$echoout=1,$bgcolor='') {
	if (!$bgcolor) {
		$bgcolor=$GLOBALS['COLOR_HTMLBOX_BACK'];
	}
	$return = '<TABLE cellspacing="1" cellpadding="5" width="100%" border="0" bgcolor="'.$GLOBALS['COLOR_HTMLBOX_BACK'].'">
			<TR BGCOLOR="'.$GLOBALS['COLOR_HTMLBOX_TITLE'].'" align="center" background="'.$GLOBALS['sys_themeimgroot'].'steel3.jpg">
				<TD colspan=2><SPAN class=titlebar>'.$title.'</SPAN></TD>
			</TR>
			<TR align=left bgcolor="'.$bgcolor.'">
				<TD colspan=2>';
	if ($echoout) {
		print $return;
	} else {
		return $return;
	}
}

function html_box1_middle($title,$bgcolor='') {
	if (!$bgcolor) {
		$bgcolor=$GLOBALS['COLOR_LTBACK1'];
	}
	return '
				</TD>
			</TR>
			<TR BGCOLOR="'.$GLOBALS['COLOR_TITLEBAR_BACK'].'" align="center" background="'.$GLOBALS['sys_themeimgroot'].'steel3.jpg">
				<TD colspan=2><SPAN class=titlebar>'.$title.'</SPAN></TD>
			</TR>
			<TR align=left bgcolor="'.$bgcolor.'">
				<TD colspan=2>';
}

function html_box1_bottom($echoout=1) {
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

// ############################

function theme_footer($params) {
	GLOBAL $HTML;
?>
	<!-- end content -->
	<p>&nbsp;</p>
	</td>
	<td width="9" bgcolor="<?php echo $GLOBALS['COLOR_CONTENT_BACK']; ?>">
		<?php html_blankimage(1,10); ?>
	</td>

	</tr>
	</table>
		</td>
		<td width="17" background="<?php echo $GLOBALS['sys_themeimgroot']; ?>rightbar1.png" align="right" valign="bottom"><img src="<?php echo $GLOBALS['sys_themeimgroot']; ?>rightbar1.png" width="17" height="25" alt=" "></td>
	</tr>
	<tr>
		<td background="<?php echo $GLOBALS['sys_themeimgroot']; ?>bbar1.png" height="17"><img src="<?php echo $GLOBALS['sys_themeimgroot']; ?>bleft1.png" width="17" height="17" alt=" "></td>
		<td background="<?php echo $GLOBALS['sys_themeimgroot']; ?>bbar1.png" align="center" colspan="3"><img src="<?php echo $GLOBALS['sys_themeimgroot']; ?>bbar1.png" width="1" height="17" alt=" "></td>
		<td background="<?php echo $GLOBALS['sys_themeimgroot']; ?>bbar1.png" bgcolor="#7c8188"><img src="<?php echo $GLOBALS['sys_themeimgroot']; ?>bright1.png" width="17" height="17" alt=" "></td>
	</tr>
</table>

<!-- themed page footer -->
<?php  
	$HTML->generic_footer($params);
}

// ############################

function theme_header($params) {
	GLOBAL $HTML;

	$HTML->generic_header_start($params); 

	//determine font for this platform
	if (browser_is_windows() && browser_is_ie()) {

		//ie needs smaller fonts
		$font_size='x-small';
		$font_smaller='xx-small';
		$font_smallest='7pt';

	} else if (browser_is_windows()) {

		//netscape on wintel
		$font_size='small';
		$font_smaller='x-small';
		$font_smallest='x-small';

	} else if (browser_is_mac()){

		//mac users need bigger fonts
		$font_size='medium';
		$font_smaller='small';
		$font_smallest='x-small';

	} else {

		//linux and other users
		$font_size='small';
		$font_smaller='x-small';
		$font_smallest='xx-small';

	}

	//themable someday?
	$site_fonts='verdana,arial,helvetica,sans-serif';

	?>

		<style type="text/css">
			<!--
	OL,UL,P,BODY,TD,TR,TH,FORM { font-family: <?php echo $GLOBALS['FONT_CONTENT'] . ',' . $site_fonts; ?>; font-size:<?php echo $font_size; ?>; color: #333333; }

	H1 { font-size: x-large; font-family: <?php echo $site_fonts; ?>; }
	H2 { font-size: large; font-family: <?php echo $site_fonts; ?>; }
	H3 { font-size: medium; font-family: <?php echo $site_fonts; ?>; }
	H4 { font-size: small; font-family: <?php echo $site_fonts; ?>; }
	H5 { font-size: x-small; font-family: <?php echo $site_fonts; ?>; }
	H6 { font-size: xx-small; font-family: <?php echo $site_fonts; ?>; }

	PRE,TT { font-family: courier,sans-serif }

	SPAN.center { text-align: center }
	SPAN.boxspace { font-size: 2pt; }
	SPAN.osdn {font-size: <?php echo $font_smaller; ?>; font-family: verdana,arial,helvetica,sans-serif;}
        SPAN.search {font-size: <?php echo $font_smaller; ?>; font-family: verdana,arial,helvetica,sans-serif;}
        SPAN.slogan {font-size: large; font-weight: bold; font-family: verdana,arial,helvetica,sans-serif;}
        SPAN.footer {font-size: <?php echo $font_smaller; ?>; font-family: verdana,arial,helvetica,sans-serif;}

	A.maintitlebar { color: #FFFFFF }
	A.maintitlebar:visited { color: #FFFFFF }

	A.sortbutton { color: #FFFFFF; text-decoration: underline; }
	A.sortbutton:visited { color: #FFFFFF; text-decoration: underline; }

	.menus { color: #6666aa; text-decoration: none; }
	.menus:visited { color: #6666aa; text-decoration: none; }

	A:link { text-decoration:none }
	A:visited { text-decoration:none }
	A:active { text-decoration:none }
	A:hover { text-decoration:underline; color:#FF0000 }

	.tabs { color: #000000; }
	.tabs:visited { color: #000000; }
	.tabs:hover { color:#FF0000; }
	.tabselect { color: #000000; font-weight: bold; }
	.tabselect:visited { font-weight: bold;}
	.tabselect:hover { color:#FF0000; font-weight: bold; }

	.titlebar { text-decoration:none; color:#000000; font-family: <?php echo $GLOBALS['FONT_HTMLBOX_TITLE'] . ',' . $site_fonts; ?>; font-size: <?php echo $GLOBALS['FONTSIZE_HTMLBOX_TITLE']; ?>; font-weight: bold; }
	.develtitle { color:#000000; font-weight: bold; }
	.legallink { color:#000000; font-weight: bold; }
			-->
		</style>
	
	<?php
	$HTML->generic_header_end($params); 
?>
<body text="#333333" link="#6666aa" alink="#aa6666" vlink="#6666aa" bgcolor="#6C7198" leftmargin="0" rightmargin="0" topmargin="0" bottommargin="0" marginwidth="0" marginheight="0">
<?php

/*

	OSDN NAV BAR

*/

osdn_print_navbar();

echo html_blankimage(5,100) . '<br>';

?>
<!-- start page body -->
<CENTER>
<table cellpadding="0" cellspacing="0" border="0" width="99%">
	<tr>
		<td background="<?php echo $GLOBALS['sys_themeimgroot']; ?>tbar1.png" width="1%" height="17"><img src="<?php echo $GLOBALS['sys_themeimgroot']; ?>tleft1.png" width="17" height="17" alt=" "></td>
		<td background="<?php echo $GLOBALS['sys_themeimgroot']; ?>tbar1.png" align="center" colspan="3" width="99%"><img src="<?php echo $GLOBALS['sys_themeimgroot']; ?>tbar1.png" width="1" height="17" alt=" "></td>
		<td><img src="<?php echo $GLOBALS['sys_themeimgroot']; ?>tright1.png" width="17" height="17" alt=" "></td>
	</tr>
	<tr>
		<td width="17" background="<?php echo $GLOBALS['sys_themeimgroot']; ?>leftbar1.png" align="left" valign="bottom"><img src="<?php echo $GLOBALS['sys_themeimgroot']; ?>leftbar1.png" width="17" height="25" alt=" "></td>
		<td colspan="3" bgcolor="#ffffff">


<!-- start main body cell -->

	<table cellpadding="0" cellspacing="0" border="0" width="100%">
		<tr>
			<td width="141" background="<?php echo $GLOBALS['sys_themeimgroot']; ?>steel3.jpg" bgcolor="#cfd1d4" align="left" valign="top">

	<CENTER>
	<a href="/"><IMG src="<?php echo $GLOBALS['sys_themeimgroot']; ?>sflogo-hammer1.jpg" alt=" Source Forge " border="0" width="136" height="79"></A>
	</CENTER>
	<P>
	<!-- menus -->
	<?php
	html_blankimage(1,140);
	menu_print_sidebar($params);
	?>
	<P>
	</TD>

	<td width="20" background="<?php echo $GLOBALS['sys_themeimgroot']; ?>fade1.png" nowrap><img src="<?php echo $GLOBALS['sys_themeimgroot']; ?>fade1.png" width="20" height="35" alt=" "></td>
	<td valign="top" bgcolor="<?php echo $GLOBALS['COLOR_CONTENT_BACK']; ?>" width="99%">
	<BR>
	<?php 

} //end funtion site_header

//function menuhtml_top($title) {
function theme_menuhtml_top($title) {
	/*
		Use only for the top most menu
	*/
	?>
<table cellpadding="0" cellspacing="0" border="0" width="140">
	<tr>
		<td align="left" valign="middle"><font face="arial, helvetica" size="2"><b><?php echo $title; ?></b></font><br></td>
	</tr>
	<tr>
		<td align="right" valign="middle">
	<?php
}

//function menuhtml_bottom() {
function theme_menuhtml_bottom() {
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

function theme_menu_entry($link, $title) {
	print "\t".'<font face="arial, helvetica" size="2"><A class="menus" href="'.$link.'">'.$title.'</A> &nbsp;<img src="'.$GLOBALS['sys_themeimgroot'].'point1.png" alt=" " width="7" height="7"></font><br>';
}

function theme_tab_entry($url='http://localhost/', $icon='', $title='Home', $selected=0) {
        print '
                <A ';
        if ($selected){
		print 'class=tabselect ';
	} else {
		print 'class=tabs ';
	}
        print 'href="'. $url .'">' . $title . '</A>&nbsp;|&nbsp;';
}

/* Example of menu usage
function theme_menu_sourceforge() {
	theme_menuhtml_top('SourceForge');
	theme_menu_entry('/compilefarm/','Compile Farm');
	theme_menuhtml_bottom();
}
*/
?>
