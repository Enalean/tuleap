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
	include/util.php
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
$theme_arcolor['Theme LightBG Default'] = '#EEEEF8';
$theme_arcolor['Theme LightBG2 Default'] = '#FAFAFA';
$theme_arcolor['Theme TitleBar Default'] = '#737b9c';
$theme_arcolor['Theme Box Background Default'] = '#EEEEF8';
$theme_arcolor['Theme Background Default'] = '#FFFFFF';

$theme_arfont['Theme TitleBar Default'] = 'Lucida';
$theme_arfont['Theme Box Default'] = 'Lucida';
$theme_arfont['Theme Body Default'] = 'Lucida';

$theme_arfontsize['Theme TitleBar Default'] = '12px';
$theme_arfontsize['Theme Box Default'] = '12px';
$theme_arfontsize['Theme Body Default'] = '12px';

$theme_arfontcolor['Theme TitleBar Default'] = '#000000';
$theme_arfontcolor['Theme Box Default'] = '#000000';
$theme_arfontcolor['Theme Body Default'] = '#000000';

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
        $return = '
                <TABLE cellspacing="0" cellpadding="1" width="100%" border="0" bgcolor="'
                .$GLOBALS['COLOR_HTMLBOX_TITLE'].'"><TR><TD>';

        $return .= '<TABLE cellspacing="1" cellpadding="2" width="100%" border="0" bgcolor="'.$bgcolor.'">'.
                        '<TR BGCOLOR="'.$GLOBALS['COLOR_HTMLBOX_TITLE'].'" align="center">'.
                        '<TD colspan=2><SPAN class=titlebar>'.$title.'</SPAN></TD></TR>'.
                        '<TR align=left>
                                <TD colspan=2>';
        if ($echoout) {
                print $return;
        } else {
                return $return;
        }
}


function html_box1_middle($title,$bgcolor='#FFFFFF') {
        return '
                                </TD>
                        </TR>
                        <TR BGCOLOR="'.$GLOBALS['COLOR_TITLEBAR_BACK'].'" align="center">
                                <TD colspan=2><SPAN class=titlebar>'.$title.'</SPAN></TD>
                        </TR>
                        <TR align=left bgcolor="'.$bgcolor.'">
                                <TD colspan=2>';
}

function html_box1_bottom($echoout=1) {
        $return = '
                                </TD>
                        </TR>
                </TABLE></TD></TR></TABLE><P>';
        if ($echoout) {
                print $return;
        } else {
                return $return;
        }
}

// ############################

function theme_footer($params) {
	
	?>
        <!-- end content -->
        <p>&nbsp;</p>
        </td>
        <td width="9">
                <?php html_blankimage(1,10); ?>
        </td>

        </tr>
        </table>
    </td>
  </tr>
</table>

<!-- end themed page footer -->
	<?php
	html_generic_footer($params);
}

// ############################

function theme_header($params) {
	html_generic_header_start($params); 
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
        .titlebar {text-decoration:none; color:#000000; font-family: <?php echo $GLOBALS['TITLEBAR_font']; ?>,Lucida; font-weight: bold; font-size:<?php echo $GLOBALS['TITLEBAR_size']; ?>}
        .title {text-decoration:none; color:#000000; font-family: Helvetica; font-weight: bold; font-size:20px}
        .normal {text-decoration:none; color:#000000; font-family: Helvetica; font-weight: light; font-size:12px}

	BODY { background-color: <?php echo $GLOBALS['COLOR_CONTENT_BACK']; ?>; font-family: <?php echo $GLOBALS['FONT_CONTENT']; ?>,<?php echo $site_fonts; ?>; font-size:<?php echo $font_size; ?>; }

	A { text-decoration: none; color #6666FF; }
	A:visited { text-decoration: none; color: #6666AA; }
	A:link { text-decoration: none; color: #6666AA; }
	A:active { text-decoration: none; color: #6666AA; }
	A:hover { text-decoration: none; color: #FF6666 }
	OL,UL,P,BODY,TD,TR,TH,FORM,SPAN { font-family: <?php echo $GLOBALS['FONT_CONTENT']; ?>,arial,helvetica,sans-serif;color: #333333; }
	H1,H2,H3,H4,H5,H6 { font-family: <?php echo $GLOBALS['FONT_CONTENT']; ?>,arial,helvetica,sans-serif }
	PRE,TT { font-family: courier,sans-serif }

	SPAN.center { text-align: center }
	SPAN.boxspace { font-size: 2pt; }

	A.maintitlebar { color: #FFFFFF }
	A.maintitlebar:visited { color: #FFFFFF }

	A.sortbutton { color: #FFFFFF; text-decoration: underline; }
	A.sortbutton:visited { color: #FFFFFF; text-decoration: underline; }

	A.menus { color: #000000; text-decoration: underline; }
	A.menus:visited { color: #000000; text-decoration: underline; }

	A.tabs { color: #000000; text-decoration: underline; }
	A.tabs:visited { color: #000000; text-decoration: underline; }

	SPAN.alignright { text-align: right }
	SPAN.maintitlebar { font-size: small; color: #FFFFFF }
	SPAN.titlebar { text-align: center; font-size: small; color: #FFFFFF; font-weight: bold }
	SPAN.develtitle { text-align: center; font-size: small; color: #000000; font-weight: bold }

        SPAN.osdn {font-size: x-small; font-family: verdana,arial,helvetica,sans-serif;}
        SPAN.search {font-size: x-small; font-family: verdana,arial,helvetica,sans-serif;}
        SPAN.slogan {font-size: large; font-weight: bold; font-family: verdana,arial,helvetica,sans-serif;}
        SPAN.footer {font-size: x-small; font-family: verdana,arial,helvetica,sans-serif;}

	TD.featurebox { font-size: small; }

      -->
    </style>
	
	<?php
	html_generic_header_end($params); 
	?>
        <BODY bgcolor=<?php echo $GLOBALS['COLOR_CONTENT_BACK']; ?> topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" marginheight="0" marginwidth="0">
<?php 
	osdn_print_navbar(); 
?>
        <!-- top title table -->
        <TABLE width="100%" border=0 cellspacing=0 cellpadding=0 bgcolor="<?php echo $GLOBALS['COLOR_BARBACK']; ?>" valign="center">
        <TR valign="top" bgcolor="<?php echo $GLOBALS['COLOR_LTBACK1']; ?>"><TD>
        <A href="/"><?php

        html_image('sflogo2-steel.png',array('vspace'=>'0'));
        ?></A>
        </TD>
        <TD width="99%"><!-- right of logo -->
        <a href="http://www.valinux.com"><?php html_image("valogo3.png",array('align'=>'right','alt'=>'VA Linux Systems','hspace'=>'5','vspace'=>'0')); ?></A>

        <BR>
        <FONT SIZE="+1">Breaking Down The Barriers to Open Source Development</FONT>
        <BR>
        <?php
        if (!user_isloggedin()) {
                print '<B>Status: Not Logged In</B>
                        <A href="/account/login.php">[Login]</A> |
                        <A href="/account/register.php">[New User]</A><BR>';
        }
        ?>

        <A href="/softwaremap/">[Software Map]</A>
        <A href="/new/">[New Releases]</A>
        <A href="/docs/site/">[Site Docs]</A>
        <A href="/top/">[Top Projects]</A>

        <!-- VA Linux Stats Counter -->
        <?php
        if (!session_issecure()) {
                print '<IMG src="http://www2.valinux.com/clear.gif?id=105" width=1 height=1 alt="Counter">';
        }
        ?>


        </TD><!-- right of logo -->
        </TR>

        <TR><TD bgcolor="#543a48" colspan=2><IMG src="/images/blank.png" height=2 vspace=0></TD></TR>

        </TABLE>
        <!-- end top title table -->
        <!-- content table -->
        <TABLE width="100%" cellspacing=0 cellpadding=0 border=0>
        <TR valign="top">
        <TD bgcolor=<?php print $GLOBALS['COLOR_LTBACK1']; ?>>
        <!-- menus -->
        <?php
        menu_print_sidebar($params);
        ?>
        </TD>

        <td width="9">
                <?php html_blankimage(1,9); ?>
        </td>
        <!-- content -->

        <td width="99%">
        &nbsp;<BR>
        <?php
}

function theme_menuhtml_top($title) {
	/*
		Use only for the top most menu
	*/
	?>
        <table cellspacing="0" cellpadding="3" width="100%" border="0" bgcolor="<?php echo $GLOBALS['COLOR_TITLEBAR_BACK']; ?>">
        <tr bgcolor="<?php echo $GLOBALS['COLOR_TITLEBAR_BACK']; ?>">
        <td align="center">
        <?php html_blankimage(1,135); ?><BR>
        <span class="titlebar"><font color="#ffffff"><?php print $title; ?></font></span></td>
        </tr>
        <tr align="left" BGCOLOR="<?php echo $GLOBALS['COLOR_LTBACK1']; ?>"><td>
	<?php
}

function theme_menuhtml_bottom() {
	/*
		End the table
	*/
        print '

        </TD>
        </TR></TABLE>
';
}

function theme_menu_entry($link, $title) {
	print "\t".'<font face="arial, helvetica" size="2"><A class="menus" href="'.$link.'">'.$title.'</A></font><br>';
}

function theme_tab_entry($url='http://localhost/', $icon='ic/home16b.png', $title='Home', $selected=0) {
        print '
                <A ';
        if ($selected) {
                print 'class=tabs ';
        }
        print 'href="'. $url .'">';
	html_image($icon,array('alt'=>"$title",'border'=>($selected?'1':'0'),'width'=>24,'height'=>24));
	print '</A>';
}

?>
