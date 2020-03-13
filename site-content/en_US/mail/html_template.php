<?php
/*
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

//This template is hardly inspired from MailChimp Email Blueprints
//  https://github.com/mailchimp/Email-Blueprints
//  These email blueprints are licensed under a Creative Commons Attribution-ShareAlike 3.0 Unported License: http://creativecommons.org/licenses/by-sa/3.0/
//
//  https://github.com/mailchimp/Email-Blueprints/blob/master/templates/simple-basic.html

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

        <title><?php echo $title; ?></title>
<style type="text/css">
/* Client-specific Styles */
#outlook a{padding:0;} /* Force Outlook to provide a "view in browser" button. */
body{width:100% !important;} .ReadMsgBody{width:100%;} .ExternalClass{width:100%;} /* Force Hotmail to display emails at full width */
body{-webkit-text-size-adjust:none;} /* Prevent Webkit platforms from changing default text sizes. */

/* Reset Styles */
body{margin:0; padding:0;}
img{border:0; height:auto; line-height:100%; outline:none; text-decoration:none;}
table, table td, table th {border-collapse:collapse;}
#backgroundTable{height:100% !important; margin:0; padding:0; width:100% !important;}

/* Template Styles */

/* /\/\/\/\/\/\/\/\/\/\ STANDARD STYLING: COMMON PAGE ELEMENTS /\/\/\/\/\/\/\/\/\/\ */

/**
* @tab Page
* @section background color
* @tip Set the background color for your email. You may want to choose one that matches your company\'s branding.
* @theme page
*/
body, #backgroundTable{
/*@editable*/ background-color:#FAFAFA;
}

/**
* @tab Page
* @section email border
* @tip Set the border for your email.
*/
#templateContainer{
/*@editable*/ border: 1px solid #DDDDDD;
}

/**
* @tab Page
* @section heading 1
* @tip Set the styling for all first-level headings in your emails. These should be the largest of your headings.
* @style heading 1
*/
h1, .h1{
/*@editable*/ color:#202020;
display:block;
/*@editable*/ font-family:Arial;
/*@editable*/ font-size:22px;
/*@editable*/ font-weight:bold;
/*@editable*/ line-height:100%;
margin-top:0;
margin-right:0;
margin-bottom:10px;
margin-left:0;
/*@editable*/ text-align:left;
}

/**
* @tab Page
* @section heading 2
* @tip Set the styling for all second-level headings in your emails.
* @style heading 2
*/
h2, .h2{
/*@editable*/ color:#202020;
display:block;
/*@editable*/ font-family:Arial;
/*@editable*/ font-size:18px;
/*@editable*/ font-weight:bold;
/*@editable*/ line-height:100%;
margin-top:20px;
margin-right:0;
margin-bottom:10px;
margin-left:0;
/*@editable*/ text-align:left;
/*@editable*/ border-top: 1px solid #cccccc;
/*@editable*/ padding-top: 10px;
}

/**
* @tab Page
* @section heading 3
* @tip Set the styling for all third-level headings in your emails.
* @style heading 3
*/
h3, .h3{
/*@editable*/ color:#202020;
display:block;
/*@editable*/ font-family:Arial;
/*@editable*/ font-size:14px;
/*@editable*/ font-weight:bold;
/*@editable*/ line-height:100%;
margin-top:0;
margin-right:0;
margin-bottom:10px;
margin-left:0;
/*@editable*/ text-align:left;
}

/**
* @tab Page
* @section heading 4
* @tip Set the styling for all fourth-level headings in your emails. These should be the smallest of your headings.
* @style heading 4
*/
h4, .h4{
/*@editable*/ color:#202020;
display:block;
/*@editable*/ font-family:Arial;
/*@editable*/ font-size:12px;
/*@editable*/ font-weight:bold;
/*@editable*/ line-height:100%;
margin-top:0;
margin-right:0;
margin-bottom:10px;
margin-left:0;
/*@editable*/ text-align:left;
}

/* /\/\/\/\/\/\/\/\/\/\ STANDARD STYLING: PREHEADER /\/\/\/\/\/\/\/\/\/\ */

/**
* @tab Header
* @section preheader style
* @tip Set the background color for your email\'s preheader area.
* @theme page
*/
#templatePreheader{
/*@editable*/ background-color:#FAFAFA;
}

/**
* @tab Header
* @section preheader text
* @tip Set the styling for your email\'s preheader text. Choose a size and color that is easy to read.
*/
.preheaderContent div{
/*@editable*/ color:#505050;
/*@editable*/ font-family:Arial;
/*@editable*/ font-size:10px;
/*@editable*/ line-height:100%;
/*@editable*/ text-align:left;
}

/**
* @tab Header
* @section preheader link
* @tip Set the styling for your email\'s preheader links. Choose a color that helps them stand out from your text.
*/
.preheaderContent div a:link, .preheaderContent div a:visited, /* Yahoo! Mail Override */ .preheaderContent div a .yshortcuts /* Yahoo! Mail Override */{
/*@editable*/ color:#336699;
/*@editable*/ font-weight:normal;
/*@editable*/ text-decoration:underline;
}

/* /\/\/\/\/\/\/\/\/\/\ STANDARD STYLING: HEADER /\/\/\/\/\/\/\/\/\/\ */

/**
* @tab Header
* @section header style
* @tip Set the background color and border for your email\'s header area.
* @theme header
*/
#templateHeader{
/*@editable*/ background-color:#FFFFFF;
/*@editable*/ border-bottom:0;
}

/**
* @tab Header
* @section header text
* @tip Set the styling for your email\'s header text. Choose a size and color that is easy to read.
*/
.headerContent{
/*@editable*/ color:#202020;
/*@editable*/ font-family:Arial;
/*@editable*/ font-size:34px;
/*@editable*/ font-weight:bold;
/*@editable*/ line-height:100%;
/*@editable*/ padding:0;
/*@editable*/ text-align:center;
/*@editable*/ vertical-align:middle;
}

/**
* @tab Header
* @section header link
* @tip Set the styling for your email\'s header links. Choose a color that helps them stand out from your text.
*/
.headerContent a:link, .headerContent a:visited, /* Yahoo! Mail Override */ .headerContent a .yshortcuts /* Yahoo! Mail Override */{
/*@editable*/ color:#336699;
/*@editable*/ font-weight:normal;
/*@editable*/ text-decoration:underline;
}

#headerImage{
height:auto;
max-width:750px !important;
}

/* /\/\/\/\/\/\/\/\/\/\ STANDARD STYLING: MAIN BODY /\/\/\/\/\/\/\/\/\/\ */

/**
* @tab Body
* @section body style
* @tip Set the background color for your email\'s body area.
*/
#templateContainer, .bodyContent{
/*@editable*/ background-color:#FFFFFF;
}

/**
* @tab Body
* @section body text
* @tip Set the styling for your email\'s main content text. Choose a size and color that is easy to read.
* @theme main
*/
.bodyContent div, .bodyContent td, .bodyContent th {
/*@editable*/ color:#505050;
/*@editable*/ font-family:Arial;
/*@editable*/ font-size:14px;
/*@editable*/ line-height:150%;
/*@editable*/ text-align:left;
}

/**
* @tab Body
* @section body link
* @tip Set the styling for your email\'s main content links. Choose a color that helps them stand out from your text.
*/
.bodyContent div a:link, .bodyContent div a:visited, /* Yahoo! Mail Override */ .bodyContent div a .yshortcuts /* Yahoo! Mail Override */{
/*@editable*/ color:#336699;
/*@editable*/ font-weight:normal;
/*@editable*/ text-decoration:underline;
}

.bodyContent img{
display:inline;
height:auto;
}

.bodyContent .tracker_formelement_label {
    font-weight: bold;
}
.bodyContent .tracker_artifact_field {
    margin-bottom: 1em;
}
.bodyContent .tracker_artifact_followup_header {
    -webkit-border-top-right-radius:20px;
    -moz-border-radius-topright:20px;
    border-top-right-radius:20px;
    -webkit-border-top-left-radius:20px;
    -moz-border-radius-topleft:20px;
    border-top-left-radius:20px;
    border: 1px solid #f6f6f6;
    border-bottom: none;
    margin-left:75px;
    padding:10px 10px 2px 10px;
}
.bodyContent .tracker_artifact_followup_title {
    float:left;
}
.bodyContent .tracker_artifact_followup_title_user {
    font-size:1.1em;
    font-weight:bold;
    color:#999;
}
.bodyContent .tracker_artifact_followup_comment_edited_by {
    font-size:0.8em;
    color:#999;
}
.bodyContent .tracker_artifact_followup_comment_body {
    border-color: #e8ebb5;
    -moz-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
    -webkit-box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
    margin: 1em 0;
    padding: 0.5em 1em;
}
.bodyContent .tracker_artifact_followup_comment_changes {
}
.bodyContent .tracker_artifact_followup_date {
    text-align:right;
    font-size:0.95em;
    color:#666;
}
.bodyContent .tracker_artifact_followup_avatar {
    width: 75px;
    clear: both;
    float: left;
    text-align: center;
}
.bodyContent .tracker_artifact_followup_content {
    -webkit-border-bottom-right-radius:20px;
    -moz-border-radius-bottomright:20px;
    border-bottom-right-radius:20px;
    -webkit-border-bottom-left-radius:20px;
    -moz-border-radius-bottomleft:20px;
    border-bottom-left-radius:20px;
    border: 1px solid #f6f6f6;
    border-top: none;
    margin-left:75px;
    padding-left: 1em;
    padding-bottom:20px;
    padding-top:10px;
    padding-right:1em;
    min-height:50px;
    margin-bottom:1em;
}

.bodyContent .tracker_artifact_followup_avatar,
.bodyContent .tracker_artifact_followup.boxitemalt .tracker_artifact_followup_avatar {
    background: url(<?php echo $http_url ?>/plugins/tracker/themes/default/images/tracker_artifact_comment_alt.png) right top no-repeat;
}
.bodyContent .tracker_artifact_followup.boxitem .tracker_artifact_followup_avatar {
    background: url(<?php echo $http_url ?>/plugins/tracker/themes/default/images/tracker_artifact_comment.png) right top no-repeat;
}
.bodyContent .tracker_artifact_followup_header,
.bodyContent .tracker_artifact_followup_content,
.bodyContent .tracker_artifact_followup.boxitemalt .tracker_artifact_followup_header,
.bodyContent .tracker_artifact_followup.boxitemalt .tracker_artifact_followup_content{
     background-color: #f6f6f6;
}
.bodyContent .tracker_artifact_followup.boxitem .tracker_artifact_followup_header,
.bodyContent .tracker_artifact_followup.boxitem .tracker_artifact_followup_content{
    background-color: #f0f0f0;
}
.bodyContent div.avatar {
    background: transparent url(<?php echo $img_path ?>/avatar_default.png) 0 0 no-repeat;
    background-size: contain;
    width:50px;
    height:50px;
}
.bodyContent div.avatar img {
    border-radius: 50%;
    width: 100%;
    height: 100%;
}
.bodyContent span.cta a,
.bodyContent span.cta a:link,
.bodyContent span.cta a:visited,
.bodyContent span.cta a:hover,
.bodyContent span.cta a:active {
    background: orange;
    font-weight: bold;
    line-height: 150%;
    color: white;
    padding: 0.5em 1em;
    margin-right: 5px;
    text-decoration: none;
    text-shadow:1px 1px 1px darkorange;
    float: right;
}
.bodyContent hr {
  width: 100%;
  height: 1px;
  background: #ccc;
  border: 0;
}

/* {{{ Diff */
.diff .context {
    color:#666;
}
.artifact_changes ins,
.diff .added,
.diff .final {
    background: #dfd;
}
.artifact_changes del,
.diff .deleted,
.diff .original {
    background: #fdd;
}
.artifact_changes ins,
.diff .added ins,
.diff .final ins {
    color: #090;
    text-decoration: none;
    font-weight:bold;
}
.artifact_changes del,
.diff .deleted del,
.diff .original del {
    color: #c00;
    font-weight:bold;
}
/* }}} */

.bodyContent div.content-header {
/*@editable*/ color:#707070;
/*@editable*/ font-family:Arial;
/*@editable*/ font-size:10px;
/*@editable*/ text-align:left;
/*@editable*/ margin-bottom: 0.5em;;
}

dd {
    margin-left:1.5em;
}
/* /\/\/\/\/\/\/\/\/\/\ STANDARD STYLING: FOOTER /\/\/\/\/\/\/\/\/\/\ */

/**
* @tab Footer
* @section footer style
* @tip Set the background color and top border for your email\'s footer area.
* @theme footer
*/
#templateFooter{
/*@editable*/ background-color:#FFFFFF;
/*@editable*/ border-top:0;
}

/**
* @tab Footer
* @section footer text
* @tip Set the styling for your email\'s footer text. Choose a size and color that is easy to read.
* @theme footer
*/
.footerContent div{
/*@editable*/ color:#707070;
/*@editable*/ font-family:Arial;
/*@editable*/ font-size:12px;
/*@editable*/ line-height:125%;
/*@editable*/ text-align:left;
}

/**
* @tab Footer
* @section footer link
* @tip Set the styling for your email\'s footer links. Choose a color that helps them stand out from your text.
*/
.footerContent div a:link, .footerContent div a:visited, /* Yahoo! Mail Override */ .footerContent div a .yshortcuts /* Yahoo! Mail Override */{
/*@editable*/ color:#336699;
/*@editable*/ font-weight:normal;
/*@editable*/ text-decoration:underline;
}

.footerContent img{
display:inline;
}

/**
* @tab Footer
* @section social bar style
* @tip Set the background color and border for your email\'s footer social bar.
* @theme footer
*/
#social{
/*@editable*/ background-color:#FAFAFA;
/*@editable*/ border:0;
}

/**
* @tab Footer
* @section social bar style
* @tip Set the background color and border for your email\'s footer social bar.
*/
#social div{
/*@editable*/ text-align:center;
}

/**
* @tab Footer
* @section utility bar style
* @tip Set the background color and border for your email\'s footer utility bar.
* @theme footer
*/
#utility{
/*@editable*/ background-color:#FFFFFF;
/*@editable*/ border:0;
}

/**
* @tab Footer
* @section utility bar style
* @tip Set the background color and border for your email\'s footer utility bar.
*/
#utility div{
/*@editable*/ text-align:center;
}

</style>
</head>
    <body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
     <center>
         <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="backgroundTable">
             <tr>
                 <td align="center" valign="top">
                    <!-- // Begin Template Preheader \\ -->
                    <table border="0" cellpadding="10" cellspacing="0" width="750" id="templatePreheader">
                        <tr>
                            <td valign="top" class="preheaderContent">

                             <!-- // Begin Module: Standard Preheader \ -->
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                   <tr>
                                      <td valign="bottom">
                                         <div>
                                             <img src="<?php echo $img_path ?>/organization_logo_mail.png" alt="<?php echo $GLOBALS['sys_name'] ?>" />
                                         </div>
                                      </td>
                                    </tr>
                                </table>
                             <!-- // End Module: Standard Preheader \ -->
                            </td>
                        </tr>
                    </table>
                    <!-- // End Template Preheader \\ -->
                     <table border="0" cellpadding="0" cellspacing="0" width="750" id="templateContainer">
                        <tr>
                             <td align="center" valign="top">
                                <!-- // Begin Template Body \\ -->
                                <table border="0" cellpadding="0" cellspacing="0" width="750" id="templateBody">
                                    <tr>
                                        <td valign="top" class="bodyContent">

                                            <!-- // Begin Module: Standard Content \\ -->
                                            <table border="0" cellpadding="20" cellspacing="0" width="100%">
                                                <tr>
                                                    <td valign="top">
                                                        <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                                            <tr>
                                                                <td>
                                                                    <?php echo !empty($breadcrumbs) ? '<div class="content-header">' . implode(' &raquo; ', $breadcrumbs) . '</div>' : ''; ?>
                                                                </td>
                                                                <td align="right">
                                                                    <?php echo !empty($unsubscribe_link) ? '<div class="content-header">' . $unsubscribe_link . '</div>' : ''; ?>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                        <?php echo $body ?>
                                                    </td>
                                                </tr>
                                            </table>
                                            <!-- // End Module: Standard Content \\ -->
                                        </td>
                                    </tr>
                                </table>
                                <!-- // End Template Body \\ -->
                            </td>
                        </tr>
                        <tr>
                             <td align="center" valign="top">
                                    <!-- // Begin Template Footer \\ -->
                                 <table border="0" cellpadding="10" cellspacing="0" width="750" id="templateFooter">
                                     <tr>
                                         <td valign="top" class="footerContent">

                                                <!-- // Begin Module: Standard Footer \\ -->
                                                <table border="0" cellpadding="10" cellspacing="0" width="100%">
                                                    <tr>
                                                        <td colspan="2" valign="middle" id="social">
                                                            <div>
                                                                <?php echo !empty($additional_footer_link) ? '&nbsp;' . $additional_footer_link . '&nbsp; |' : '' ?>
                                                                &nbsp;<a href="<?php echo HTTPRequest::instance()->getServerUrl() ?>/account/preferences.php" target="_blank" rel="noreferrer"><?php echo $txt_can_update_prefs ?></a>&nbsp;
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>
                                                <!-- // End Module: Standard Footer \\ -->
                                            </td>
                                        </tr>
                                    </table>
                                    <!-- // End Template Footer \\ -->
                                </td>
                            </tr>
                        </table>
                        <br />
                    </td>
                </tr>
            </table>
        </center>
    </body>
</html>
