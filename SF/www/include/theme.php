<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

$theme_arcolor = array(White=>"#FFFFFF", Black=>"#000000", Blue=>"#0000FF", Green=>"#00FF00", Red=>"#FF0000");
$theme_arfont = array(Helvetica=>"Helvetica", Times=>"Times", Courier=>"Courier", Lucida=>"Lucida");
$theme_arfontsize = array('small'=>'small', 'medium'=>'medium', 'large'=>'large', 'x-large'=>'x-large');

function user_getthemeid($user_id = 0) {
        global $USER_THEME;
        // use current user if one is not passed in
        if (!$user_id) {
                return ($G_USER?$G_USER['user_theme']:$GLOBALS['sys_themeid']);
        }
        // else must lookup name
        else {
                if ($USER_THEME["user_$user_id"]) {
                        //user theme was fetched previously
                        return $USER_THEME["user_$user_id"];
                } else {
                        //fetch the user theme and store it for future reference
                        $result = db_query("SELECT * FROM theme_prefs WHERE user_id='$user_id'");
                        if ($result && db_numrows($result) > 0) {
                                //valid theme - store and return
                                $USER_THEME["user_$user_id"]=db_result($result,0,"user_theme");
                                return $USER_THEME["user_$user_id"];
                        } else {
                                //invalid theme - store and return
                                $USER_THEME["user_$user_id"]="<B>Invalid User ID</B>";
                                return $USER_THEME["user_$user_id"];
                        }
                }
        }
}

function get_themedir($theme_id = 0) {
        global $THEME_DIR;
        // use current theme if one is not passed in
        if (!$theme_id) {
                return ($THEME_DIR?$THEME_DIR["theme_$theme_id"]:$GLOBALS['sys_theme']);
        }
        // else must lookup name
        else {
                if ($THEME_DIR["theme_$theme_id"]) {
                        //theme name was fetched previously
                        return $THEME_DIR["theme_$theme_id"];
                } else {
                        //fetch the theme name and store it for future reference
                        $result = db_query("SELECT theme_id,dirname FROM themes WHERE theme_id='$theme_id'");
                        if ($result && db_numrows($result) > 0) {
                                //valid theme - store and return
                                $THEME_DIR["theme_$theme_id"]=db_result($result,0,"dirname");
                                return $THEME_DIR["theme_$theme_id"];
                        } else {
                                //invalid theme - store and return
                                $THEME_DIR["theme_$theme_id"]="<B>Invalid Theme ID</B>";
                                return $THEME_DIR["theme_$theme_id"];
                        }
                }
        }
}

function get_themename($theme_id = 0) {
        global $THEME_NAME;
        // use current theme if one is not passed in
        if (!$theme_id) {
                return ($THEME_NAME?$THEME_NAME["theme_$theme_id"]:$GLOBALS['sys_theme']);
        }
        // else must lookup name
        else {
                if ($THEME_NAME["theme_$theme_id"]) {
                        //theme name was fetched previously
                        return $THEME_NAME["theme_$theme_id"];
                } else {
                        //fetch the theme name and store it for future reference
                        $result = db_query("SELECT theme_id,fullname FROM themes WHERE theme_id='$theme_id'");
                        if ($result && db_numrows($result) > 0) {
                                //valid theme - store and return
                                $THEME_NAME["theme_$theme_id"]=db_result($result,0,"fullname");
                                return $THEME_NAME["theme_$theme_id"];
                        } else {
                                //invalid theme - store and return
                                $THEME_NAME["theme_$theme_id"]="<B>Invalid Theme ID</B>";
                                return $THEME_NAME["theme_$theme_id"];
                        }
                }
        }
}

function theme_get_userpref($preference_name) {
        GLOBAL $theme_pref;
        if (user_isloggedin()) {
                /*
                        First check to see if we have already fetched the preferences
                */
                if ($theme_pref) {
                        if ($theme_pref["$preference_name"]) {
                                //we have fetched prefs - return part of array
                                return $theme_pref["$preference_name"];
                        } else {
                                //we have fetched prefs, but this pref hasn't been set
                                return false;
                        }
                } else {
                        //we haven't returned prefs - go to the db
                        $result=db_query("SELECT * FROM theme_prefs ".
                                "WHERE user_id='".user_getid()."'");
                        if (db_numrows($result) < 1) {
                                return false;
                        } else {

        			$theme_pref = db_fetch_array($result);

				if($theme_pref["$preference_name"]){
					return $theme_pref["$preference_name"];
				} else {
					return false;
				}
                        }
                }
        } else {
                return false;
        }
}

function theme_set_usertheme($theme_id) {
        if (user_isloggedin()) {
                $result=db_query("DELETE FROM theme_prefs WHERE user_id='".user_getid()."'");
		$result=db_query("INSERT INTO theme_prefs (user_id,user_theme) VALUES ('".user_getid()."','$theme_id')");
        } else {
                return false;
        }
	return true;
}

function theme_sysinit($theme_id = 0){
	GLOBAL $HTML;

        if (user_isloggedin() && !$theme_id){
		$GLOBALS['sys_themeid'] = user_getthemeid(user_getid());
		$GLOBALS['sys_theme'] = get_themedir($GLOBALS['sys_themeid']);
	} else {
		$GLOBALS['sys_theme'] = get_themedir($theme_id);
	}

	//Make sure the theme directory and class file exists.
	if(!isset($GLOBALS['sys_theme']) || !is_dir($GLOBALS['sys_themeroot'].$GLOBALS['sys_theme'])|| !is_file($GLOBALS['sys_themeroot'].$GLOBALS['sys_theme'].'/Theme.class')){
		$GLOBALS['sys_themeid'] = 1;
		$GLOBALS['sys_theme'] = 'forged';
	} else {
		//now include the actual chosen theme file
		include($GLOBALS['sys_themeroot'].$GLOBALS['sys_theme'].'/Theme.class');
		$HTML = new Theme();
	}
	
}

// returns full select output for a particular root
function theme_selectfull($selected,$name) {
        print "<BR><SELECT name=\"$name\">\n\t";
        $res_theme = db_query('SELECT theme_id,fullname FROM themes ORDER BY fullname');
        while ($row_theme = db_fetch_array($res_theme)) {
                print '  <OPTION value="'.$row_theme['theme_id'].'"';
                if ($selected == $row_theme['theme_id']) print (' selected');
                print '>'.$row_theme['fullname']."\n\t";
        }
        print "</SELECT>\n";
}

// returns full select output for a particular root
function theme_optionselectfull($name, $optionar, $selected) {
        $other_sel = " selected";
        print "<BR><SELECT name=\"$name\">\n\t";
        reset ($optionar);
        while (list ($key, $val) = each ($optionar)) {
            print '  <OPTION value="'.$val.'"';
            if ($selected == $val){ print (' selected'); $other_sel = ""; }
            print '>'.$key."</OPTION>\n\t";
        }
        print "  <OPTION VALUE='changed'$other_sel>Other\n\t</SELECT>\n";
	if($other_sel){ $GLOBALS[$name.'_other'] = $GLOBALS[$name]; } else {$GLOBALS[$name.'_other'] = ''; }
        print "  <INPUT TYPE='text' NAME='".$name."_other' SIZE='7' VALUE='".$GLOBALS[$name.'_other']."'>\n";
}


// returns form for modifying the user theme preferences
function theme_usermodform($selected,$action,$name,$component) {
	if(!$component){ print "<FORM NAME='$name' ACTION='$action' METHOD='post'>\n"; }
	print'
		<FONT FACE="Helvetica" SIZE="2">
';
		theme_selectfull($GLOBALS['sys_themeid'],"sys_themeid");
	print'
		<BR>
		<INPUT TYPE="submit" NAME="theme_action" VALUE="Preview Theme">
		<INPUT TYPE="submit" NAME="theme_action" VALUE="Apply Theme">
		</FONT>
';
	if(!$component){ print "</FORM>\n"; }
}

// returns form for modifying the user color preferences
function theme_modcolorform($action,$name,$component) {
	if(!$component){ print "<FORM NAME='$name' ACTION='$action' METHOD='post'>\n"; }
        print'
                <FONT FACE="Helvetica" SIZE="2">
		Light Background (Tables)';
		theme_optionselectfull("COLOR_LTBACK1", $GLOBALS['theme_arcolor'], $GLOBALS['COLOR_LTBACK1']);
	print'  <BR>Alternate Row Background :';
		theme_optionselectfull("COLOR_LTBACK2", $GLOBALS['theme_arcolor'], $GLOBALS['COLOR_LTBACK2']);
	print'  <BR>Box Title Bar Background :';
		theme_optionselectfull("COLOR_HTMLBOX_TITLE", $GLOBALS['theme_arcolor'], $GLOBALS['COLOR_HTMLBOX_TITLE']);
	print'  <BR>Box Content Background :';
		theme_optionselectfull("COLOR_HTMLBOX_BACK", $GLOBALS['theme_arcolor'], $GLOBALS['COLOR_HTMLBOX_BACK']);
	print'  <BR>Main Content Background :';
		theme_optionselectfull("COLOR_CONTENT_BACK", $GLOBALS['theme_arcolor'], $GLOBALS['COLOR_CONTENT_BACK']);
	print'  <BR>
                <INPUT TYPE="submit" NAME="color_action" VALUE="Preview Colors">
                <INPUT TYPE="submit" NAME="color_action" VALUE="Apply Colors">
                <INPUT TYPE="submit" NAME="color_action" VALUE="Cancel">
                </FONT>
';
	if(!$component){ print "</FORM>\n"; }
}

// returns form for modifying the user color preferences
function theme_modfontform($action,$name,$component) {
        if(!$component){ print "<FORM NAME='$name' ACTION='$action' METHOD='post'>\n"; }
        print'
                <FONT FACE="Helvetica" SIZE="2">
                Body Font:';
                theme_optionselectfull("FONT_CONTENT", $GLOBALS['theme_arfont'], $GLOBALS['FONT_CONTENT']);
                theme_optionselectfull("FONTSIZE_CONTENT", $GLOBALS['theme_arfontsize'], $GLOBALS['FONTSIZE_CONTENT']);
		theme_optionselectfull("FONTCOLOR_CONTENT", $GLOBALS['theme_arcolor'], $GLOBALS['FONTCOLOR_CONTENT']);

        print'  <BR>Box TitleBar Font:';
                theme_optionselectfull("FONT_HTMLBOX_TITLE", $GLOBALS['theme_arfont'], $GLOBALS['FONT_HTMLBOX_TITLE']);
                theme_optionselectfull("FONTSIZE_HTMLBOX_TITLE", $GLOBALS['theme_arfontsize'], $GLOBALS['FONTSIZE_HTMLBOX_TITLE']);
		theme_optionselectfull("FONTCOLOR_HTMLBOX_TITLE", $GLOBALS['theme_arcolor'], $GLOBALS['FONTCOLOR_HTMLBOX_TITLE']);

        print'  <BR>
                <INPUT TYPE="submit" NAME="font_action" VALUE="Preview Fonts">
                <INPUT TYPE="submit" NAME="font_action" VALUE="Apply Fonts">
                <INPUT TYPE="submit" NAME="font_action" VALUE="Cancel">
                </FONT>
';
        if(!$component){ print "</FORM>\n"; }
}

?>
