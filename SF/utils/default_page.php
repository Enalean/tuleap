<?php
// Default Web Page for groups that haven't setup their page yet
// Please replace this file with your own website
//
// $Id$
//
$headers = getallheaders();
?>
<HTML>
<HEAD>
<TITLE>CodeX: Welcome</TITLE>
<LINK rel="stylesheet" href="http://codex.xerox.com/codex.css" type="text/css" >
</HEAD>

<BODY bgcolor="#BCBCAD" link="#8b4020" vlink="#8b4020 topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" marginheight="0" marginwidth="0">

<!-- top strip -->
<TABLE width="100%" border=0 cellspacing=0 cellpadding=2 bgcolor="#BCBCAD">
  <TR>
    <TD><SPAN class=maintitlebar>&nbsp;&nbsp;
      <A class=maintitlebar href="http://codex.xerox.com/"><B>Home</B></A> | 
      <A class=maintitlebar href="http://codex.xerox.com/about.php"><B>About</B></A> | 
      <A class=maintitlebar href="http://codex.xerox.com/partners.php"><B>Partners</B></a> |
      <A class=maintitlebar href="http://codex.xerox.com/contact.php"><B>Contact Us</B></A> |
      <A class=maintitlebar href="http://codex.xerox.com/account/logout.php"><B>Logout</B></A></SPAN></TD>
    </TD>
  </TR>
</TABLE>
<!-- end top strip -->

<!-- top title table -->
<TABLE width="100%" border=0 cellspacing=0 cellpadding=0 bgcolor="" valign="center">
  <TR valign="top" bgcolor="#ece9e5">
    <TD valign="center">
      <A href="http://codex.xerox.com/"><IMG src="http://codex.xerox.com/images/codex_logo.gif" vspace="" hspace="7" border=0 alt="Xerox Code eXchange Site"></A>
    </TD>
    <TD width="99%"><!-- right of logo -->
      <a href="http://www.xerox.com"><IMG src="http://bazaar.adoc.xerox.com/xerox/images/redx_medium_trans.gif" align="right" alt="Xerox" hspace="5" vspace="7" border=0 width="51" height="48"></A>
    </TD><!-- right of logo -->
  </TR>
  <TR><TD bgcolor="#543a48" colspan=2><IMG src="http://codex.xerox.com/images/blank.gif" height=2 vspace=0></TD></TR>
</TABLE>
<!-- end top title table -->

<!-- center table -->
<TABLE width="100%" border="0" cellspacing="0" cellpadding="2" bgcolor="#FFFFFF" align="center">
  <TR>
    <TD>
      <CENTER><BR>
      <H2>Welcome to http://<?php print $headers[Host]; ?>/</H2>
      <P>We're Sorry but this Project hasn't yet uploaded their personal webpage yet.<BR>
      Please check back soon for updates or visit <A href="http://codex.xerox.com/">CodeX</A></P><BR>
      </CENTER>
    </TD>
  </TR>
</TABLE>
<!-- end center table -->

<!-- footer table -->
<TABLE width="100%" border="0" cellspacing="0" cellpadding="2" bgcolor="#BCBCAD">
  <TR>
    <TD align="center"><FONT color="#ffffff"><B><SPAN class="maintitlebar">
     Code eXchange is a Xerox internal Web site. All material hosted on this site is the property of Xerox. ©2000 Xerox Corporation.</SPAN><B></FONT>
    </TD>
  </TR>
</TABLE>

<!-- end footer table -->
</BODY>
</HTML>
