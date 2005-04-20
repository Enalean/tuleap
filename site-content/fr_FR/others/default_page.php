<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2004. All Rights Reserved
// This file is licensed under the CodeX Component Software License
// http://codex.xerox.com
//
// $Id$
//
// Purpose:
//    Default Web Page for groups that haven't setup their page yet
//   Please replace this file with your own website

$headers = getallheaders();
$pieces = explode('.', $headers[Host]);
$project_name = array_shift($pieces);
$default_domain = join('.',$pieces);
?>
<HTML>
<HEAD>
<TITLE>CodeX: Bienvenue</TITLE>
<LINK rel="stylesheet" href="http://<? echo $default_domain; ?>/codex.css" type="text/css" >
</HEAD>

<BODY bgcolor="#BCBCAD" link="#8b4020" vlink="#8b4020" topmargin="0" bottommargin="0" leftmargin="0" rightmargin="0" marginheight="0" marginwidth="0">

<!-- top strip -->
<TABLE width="100%" border=0 cellspacing=0 cellpadding=2 bgcolor="#BCBCAD">
  <TR>
    <TD><SPAN class=maintitlebar>&nbsp;&nbsp;
      <A class=maintitlebar href="http://<? echo $default_domain; ?>/"><B>Accueil</B></A> | 
      <A class=maintitlebar href="http://<? echo $default_domain; ?>/docs/site/about_codex.php"><B>A propos de</B></A> | 
      <A class=maintitlebar href="http://<? echo $default_domain; ?>/contact.php"><B>Contactez-nous</B></A> |
      <A class=maintitlebar href="http://<? echo $default_domain; ?>/account/logout.php"><B>Se déconecter</B></A></SPAN>
    </TD>
  </TR>
</TABLE>
<!-- end top strip -->

<!-- top title table -->
<TABLE width="100%" border=0 cellspacing=0 cellpadding=0 bgcolor="" valign="center">
  <TR valign="top" bgcolor="#ece9e5">
    <TD valign="center">
      <A href="http://<? echo $default_domain; ?>/"><IMG src="http://<? echo $default_domain; ?>/images/codex.theme/codex_logo.png" vspace="" hspace="7" border=0 alt="Xerox Code eXchange Site"></A>
    </TD>
    <TD width="99%"><!-- right of logo -->
      <a href="http://www.xerox.com"><IMG src="http://<? echo $default_domain; ?>/images/codex.theme/organization_logo.png" align="right" alt="Xerox" hspace="5" vspace="7" border=0 width="51" height="48"></A>
    </TD><!-- right of logo -->
  </TR>
  <TR><TD bgcolor="#543a48" colspan=2><IMG src="http://<? echo $default_domain; ?>/images/codex.theme/blank.png" height=2 vspace=0></TD></TR>
</TABLE>
<!-- end top title table -->

<!-- center table -->
<TABLE width="100%" border="0" cellspacing="0" cellpadding="2" bgcolor="#FFFFFF" align="center">
  <TR>
    <TD>
      <CENTER><BR>
      <H2>Bienvenue sur http://<?php print $headers[Host]; ?>/</H2>
      <h3>Ce projet n'a pas encore créer son site Web.<BR>
      Rendez-nous visite prochainement ou aller sur la page de <A href="http://<? echo $default_domain; ?>/projects/<?php echo $project_name; ?>">Sommaire du projet</a> sur CodeX<BR></h3>
      </CENTER>
    </TD>
  </TR>
</TABLE>
<!-- end center table -->

<!-- footer table -->
<TABLE width="100%" border="0" cellspacing="0" cellpadding="2" bgcolor="#BCBCAD">
  <TR>
    <TD align="center"><FONT color="#ffffff"><B><SPAN class="maintitlebar">
     Code eXchange est un site interne à Xerox. Tout ce qui est hebergé sur ce site est la propriété de Xerox. ©2001 Xerox Corporation.</SPAN></B></FONT>
    </TD>
  </TR>
</TABLE>

<!-- end footer table -->
</BODY>
</HTML>
