<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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

/**
* help_button() - Show a help button.
*
* @param        type      the php script or html page that contains/generates help
* @param        helpid   if specified this is an argument passed to the PHP script
*                                      if false then it is a static HTML page
* @param        prompt what to display to point to the  help
*/
function help_button($type, $helpid = false, $prompt = '[?]')
{
    $purifier = Codendi_HTMLPurifier::instance();
    // Generic processing derives the script name from the help type
    if ($helpid == false) {
    // $type is a static HTML page from the Codendi User Guide
        $lang = HTTPRequest::instance()->getCurrentUser()->getShortLocale();
        $script = '/doc/'.$lang.'/user-guide/'.$purifier->purify($type, CODENDI_PURIFIER_JS_QUOTE);
    } else {
    // $type is a php script - the invoker probably wants to customize
    // the help display somehow
        $script = '/help/'.$purifier->purify($type, CODENDI_PURIFIER_JS_QUOTE);
        $script .= '.php?helpid='.$purifier->purify(urlencode($helpid), CODENDI_PURIFIER_JS_QUOTE);
    }
    $prompt_purified = $purifier->purify($prompt);
    return ('<A href="javascript:help_window(\''.$script.'\')"><B>'.$prompt_purified.'</B></A>');
}


/**
* help_header() - Show a help page header
*
* @param        string    Header title
*/
function help_header($title, $help_banner = true)
{
    global $Language;
    ?>
<HTML>
<HEAD>
<TITLE><?php print $title; ?></TITLE>
<LINK rel="stylesheet" href="<?php echo util_get_css_theme(); ?>" type="text/css">
</HEAD>
<BODY class="bg_help">
    <?php print ($help_banner ? '<H4>'.$GLOBALS['sys_name'].' '.$Language->getText('include_help', 'site_help_sys').'</H4>' : ''); ?>
<H2><?php print $title; ?></H2>
<HR>
    <?php
}

/**
* help_footer() - Show a help page footer
*/
function help_footer()
{
    ?>
</BODY>
</HTML>
    <?php
}

?>
