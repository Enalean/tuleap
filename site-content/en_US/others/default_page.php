<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

//
// Purpose:
//    Default Web Page for groups that haven't setup their page yet
//   Please replace this file with your own website
if (preg_match('|^/www/(.*)/|',$_SERVER['REQUEST_URI'],$matches)) {
  $project_name = $matches[1];
  $default_domain = $_SERVER['HTTP_HOST'];
} else {
  $pieces = explode('.', $_SERVER['HTTP_HOST']);
  $project_name = array_shift($pieces);
  $default_domain = join('.',$pieces);
}

?>
<HTML>
<HEAD>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<TITLE>Welcome</TITLE>
<LINK rel="stylesheet" href="http://<?php echo $default_domain; ?>/codendi.css" type="text/css" >
</HEAD>

<BODY bgcolor="#BCBCAD" link="#8b4020" vlink="#8b4020" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" marginheight="0" marginwidth="0">

<!-- top strip -->
<TABLE width="100%" border=0 cellspacing=0 cellpadding=2 bgcolor="#BCBCAD">
  <TR>
    <TD><SPAN class=maintitlebar>&nbsp;&nbsp;
      <A class=maintitlebar href="http://<?php echo $default_domain; ?>/"><B>Home</B></A> | 
      <A class=maintitlebar href="http://<?php echo $default_domain; ?>/docs/site/about_codendi.php"><B>About</B></A> | 
      <A class=maintitlebar href="http://<?php echo $default_domain; ?>/contact.php"><B>Contact Us</B></A> |
      <A class=maintitlebar href="http://<?php echo $default_domain; ?>/account/logout.php"><B>Logout</B></A></SPAN>
    </TD>
  </TR>
</TABLE>
<!-- end top strip -->

<!-- top title table -->
<TABLE width="100%" border=0 cellspacing=0 cellpadding=0 bgcolor="" valign="center">
  <TR valign="top" bgcolor="#ece9e5">
    <TD valign="center">
      <A href="http://<?php echo $default_domain; ?>/">
        <IMG src="http://<?php echo $default_domain; ?>/themes/common/images/organization_logo.png" vspace="" hspace="7" border=0 alt="Organization logo">
      </A>
    </TD>
  </TR>
</TABLE>
<!-- end top title table -->

<!-- center table -->
<TABLE width="100%" border="0" cellspacing="0" cellpadding="2" bgcolor="#FFFFFF" align="center">
  <TR>
    <TD>
      <CENTER><BR>
      <H2>Welcome to http://<?php print $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?></H2>
      <h3>This Project hasn't yet set up its personal web site.<BR>
      Please check back soon for updates or visit the <A href="http://<?php echo $default_domain; ?>/projects/<?php echo $project_name; ?>">Project Summary</a> page on Codendi<BR></h3>
      </CENTER>
    </TD>
  </TR>
</TABLE>
<!-- end center table -->

<!-- end footer table -->
</BODY>
</HTML>
