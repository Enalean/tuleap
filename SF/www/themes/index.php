<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
session_require(array('isloggedin'=>'1'));

switch ($theme_action) {
    case 'Cancel' : {
        header('Location: https://'.$HTTP_HOST.'/themes/index.php');
        exit;
    }
    case 'Apply Theme': {
	theme_set_usertheme($GLOBALS['sys_themeid']);
    }
    case 'Preview Theme':{
        $COLOR_LTBACK1 		= $theme_arcolor['Theme LightBG Default'];
        $COLOR_MENUBARBACK 	= $theme_arcolor['Theme MenuBar Default'];
        $BODY_font 		= $theme_arfont['Theme Body Default'];
        $BODY_size 		= $theme_arfontsize['Theme Body Default'];
        $COLOR_TITLEBAR_BACK 	= $theme_arcolor['Theme TitleBar Default'];
        $TITLEBAR_font	 	= $theme_arfont['Theme TitleBar Default'];
        $TITLEBAR_size 		= $theme_arfontsize['Theme TitleBar Default'];
    }
}
switch ($color_action){
    case 'Cancel':{
	header('Location: https://'.$HTTP_HOST.'/themes/index.php');
	exit;
    }
    case 'Apply Colors': {
	$save_sql = 'UPDATE theme_prefs SET';
        if($COLOR_LTBACK1 != $theme_arcolor['Theme LightBG Default']){
		$save_sql .= " COLOR_LTBACK1 = '$COLOR_LTBACK1'";
	} else {
		$save_sql .= " COLOR_LTBACK1 = ''";
	}

        if($COLOR_TITLEBAR_BACK != $theme_arcolor['Theme TitleBar Default']){
		$save_sql .= ", COLOR_TITLEBAR_BACK = '$COLOR_TITLEBAR_BACK'";
        } else {
		$save_sql .= ", COLOR_TITLEBAR_BACK = ''";
        }

        $save_sql .= " WHERE user_id = '".user_getid()."'";

        $result=db_query($save_sql);
    }
}

switch ($font_action){
    case 'Cancel':{
	header('Location: https://'.$HTTP_HOST.'/themes/index.php');
	exit;
    }
    case 'Apply Fonts': {
	$save_sql = 'UPDATE theme_prefs SET ';

        if($BODY_font != $theme_arfont['Theme Body Default']){
                $save_sql .= "BODY_font='$BODY_font'";
        } else {
                $save_sql .= "BODY_font=''";
        }

        if($BODY_size != $theme_arfontsize['Theme Body Default']){
                $save_sql .= ", BODY_size='$BODY_size'";
        } else {
                $save_sql .= ", BODY_size=''";
        }
        if($TITLEBAR_font != $theme_arfont['Theme TitleBar Default']){
                $save_sql .= ", TITLEBAR_font='$TITLEBAR_font'";
        } else {
                $save_sql .= ", TITLEBAR_font=''";
        }

        if($TITLEBAR_size != $theme_arfontsize['Theme TitleBar Default']){
                $save_sql .= ", TITLEBAR_size='$TITLEBAR_size'";
        } else {
                $save_sql .= ", TITLEBAR_size=''";
        }

        $save_sql .= " WHERE user_id='".user_getid()."'";

        $result=db_query($save_sql);
    }
}

$title = 'Choose Your Theme';
$HTML->header(array('title'=>$title));

echo "<H3>$title</H3>";
// get global user vars
$res_user = db_query("SELECT * FROM user WHERE user_id=" . user_getid());
$row_user = db_fetch_array($res_user);

$HTML->box1_top("Choosing Theme and Colors for " . user_getname()); ?>

<p>Welcome, <b><?php print user_getname(); ?></b>. 
<p>You can change your theme from here. 
<P>
Your profile currently uses the <?php print get_themename(user_getthemeid()); ?> theme.
<?php $HTML->box1_bottom(); ?>

<TABLE width=100% cellpadding=0 cellspacing=0 border=0><TR valign=top>
<TD width=100%>

<?php $HTML->box1_top("New User Theme");
echo "<div align='center'>\n";
// User Theme select form
theme_usermodform(user_getthemeid(),'https://'.$HTTP_HOST.'/themes/index.php','theme_modform',0);
?>
</div>
<?php
/* Put this in in V2 when we reenable color changing
<FONT FACE="Helvetica, Times" SIZE="2" COLOR="#FF0000">
*NOTE*: Previewing or applying a theme will discard the colors selected in the color preferences form.  
Applying a theme will also discard any user selected color preferences.
</FONT>
*/
$HTML->box1_bottom(); 

/* Font prefs will be added later...
$HTML->box1_top("User Fonts");
// User Font select form
theme_modfontform('https://'.$HTTP_HOST.'/themes/index.php','theme_modfontform',0);
? >
<FONT FACE="Helvetica, Times" SIZE="2" COLOR="#FF0000">
*NOTE*: Previewing or applying fonts will discard the colors selected in the color preferences form.  
Applying fonts will also discard any user selected font preferences.
</FONT>
< ?php
$HTML->box1_bottom();
? >
* /

? >

</TD>
<TD>&nbsp;</TD>
<TD width=50%>

< ?php $HTML->box1_top("Color Preferences"); 
// Color Pref Function Goes Here
theme_modcolorform('https://'.$HTTP_HOST.'/themes/index.php','theme_colormodform',0);
? >
<FONT FACE="Helvetica, Times" SIZE="2" COLOR="#FF0000">
*NOTE*: Previewing or applying a color scheme will not always alter colors used in the theme selected in the theme form, as images may be used as backgrounds.  Applying a new color scheme only alters the colors applied to your currently set theme indicated above.
</FONT>
<?php
$HTML->box1_bottom(); 

*/

?>

</TD>
</TR></TABLE>

<?php
$HTML->footer(array());
?>
