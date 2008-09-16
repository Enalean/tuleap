<?php
//
// Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
// This file is licensed under the GNU General Public License version 2. See the file COPYING.
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
<TITLE>Codendi: Welcome</TITLE>
<LINK rel="stylesheet" href="http://<? echo $default_domain; ?>/codex.css" type="text/css" >
</HEAD>

<BODY bgcolor="#BCBCAD" link="#8b4020" vlink="#8b4020" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" marginheight="0" marginwidth="0">

<!-- top strip -->
<TABLE width="100%" border=0 cellspacing=0 cellpadding=2 bgcolor="#BCBCAD">
  <TR>
    <TD><SPAN class=maintitlebar>&nbsp;&nbsp;
      <A class=maintitlebar href="http://<? echo $default_domain; ?>/"><B>Home</B></A> | 
      <A class=maintitlebar href="http://<? echo $default_domain; ?>/docs/site/about_codex.php"><B>About</B></A> | 
      <A class=maintitlebar href="http://<? echo $default_domain; ?>/contact.php"><B>Contact Us</B></A> |
      <A class=maintitlebar href="http://<? echo $default_domain; ?>/account/logout.php"><B>Logout</B></A></SPAN>
    </TD>
  </TR>
</TABLE>
<!-- end top strip -->

<!-- top title table -->
<TABLE width="100%" border=0 cellspacing=0 cellpadding=0 bgcolor="" valign="center">
  <TR valign="top" bgcolor="#ece9e5">
    <TD valign="center">
      <A href="http://<? echo $default_domain; ?>/"><IMG src="http://<? echo $default_domain; ?>/themes/CodeX/images/codex_logo.png" vspace="" hspace="7" border=0 alt="Codendi Site"></A>
    </TD>
    <TD width="99%"><!-- right of logo -->
      <IMG src="http://<? echo $default_domain; ?>/themes/CodeX/images/organization_logo.png" align="right" alt="Organization" hspace="5" vspace="7" border=0 width="51" height="48">
    </TD><!-- right of logo -->
  </TR>
  <TR><TD bgcolor="#543a48" colspan=2><IMG src="http://<? echo $default_domain; ?>/themes/CodeX/images/blank.png" height=2 vspace=0></TD></TR>
</TABLE>
<!-- end top title table -->

<!-- center table -->
<TABLE width="100%" border="0" cellspacing="0" cellpadding="2" bgcolor="#FFFFFF" align="center">
  <TR>
    <TD>
      <CENTER><BR>
      <H2>Welcome to http://<?php print $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?></H2>
      <h3>This Project hasn't yet set up its personal web site.<BR>
      Please check back soon for updates or visit the <A href="http://<? echo $default_domain; ?>/projects/<?php echo $project_name; ?>">Project Summary</a> page on Codendi<BR></h3>
      </CENTER>
    </TD>
  </TR>
</TABLE>
<!-- end center table -->

<!-- footer table -->
<TABLE width="100%" border="0" cellspacing="0" cellpadding="2" bgcolor="#BCBCAD">
  <TR>
    <TD align="center"><FONT color="#ffffff"><B><SPAN class="maintitlebar">
     This web site is hosted on Codendi.
    </SPAN></B></FONT>
    </TD>
  </TR>
</TABLE>

<!-- end footer table -->
</BODY>
</HTML>
