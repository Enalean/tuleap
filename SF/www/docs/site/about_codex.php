<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    // Initial db and session library, opens session
$HTML->header(array(title=>"About the ".$GLOBALS['sys_name']." Site"));
?>

<h2>About the <?php print $GLOBALS['sys_name']; ?> Site</h2>

<P><?php print $GLOBALS['sys_name']; ?> is a service to <B>all <?php print $GLOBALS['sys_org_name']; ?> 
software development team</B>s who want to <b>internally</B> share the
source code of their software projects with other <?php print $GLOBALS['sys_org_name']; ?> development
teams. <?php print $GLOBALS['sys_name']; ?> aims at transposing inside
the <?php print $GLOBALS['sys_long_org_name']; ?> environment, the software development culture and
methods that have proven to be efficient in the external Open Source
world.<p>

To make things very clear and to avoid any confusion, <?php print
$GLOBALS['sys_name']; ?> is not about making <?php print $GLOBALS['sys_org_name']; ?> software Open
Source outside of <?php print $GLOBALS['sys_org_name']; ?>. <?php print $GLOBALS['sys_name']; ?> is about
Inner Source: it means that all the projects hosted on the <?php print $GLOBALS['sys_name']; ?> site
are for <u><?php print $GLOBALS['sys_org_name']; ?> internal use and sharing only</u>.<P>

The <?php print $GLOBALS['sys_name']; ?> team wants to make internal
source code sharing easy and to provide a single place where to go to
when you as a developer, a project leader or a manager are looking for
existing pieces of software and/or technology that you could re-use,
adapt and improve to speed up your own Time To Market while
capitalizing on <?php print $GLOBALS['sys_org_name']; ?> knowledge. For a consistent and fruitful
approach of internal software sharing, the <?php print
$GLOBALS['sys_name']; ?> Team has put together the <b><A
href="/docman/display_doc.php?docid=16&group_id=1"><?php print $GLOBALS['sys_org_name']; ?> Code eXchange
Policy</a></b>. It governs internal code sharing and re-use and is the
cornerstone of the <?php print $GLOBALS['sys_name']; ?> edifice. Make
sure you <A href="/docman/display_doc.php?docid=16&group_id=1">read it</a>. <P>

<?php print $GLOBALS['sys_name']; ?> also offers a full-featured,
purely web-based and easy to use project management and communication
environment. Avoid the recurrent burden of setting up a project
management environment in your own team. Using <?php print
$GLOBALS['sys_name']; ?> can help you save time/money and better focus
on what really matters: software development and users/developers
relationship.

We strongly invite you to read this document to better understand
where <?php print $GLOBALS['sys_org_name']; ?> is today in terms of source code sharing and where we want
it to be in the near future. If you want to help convert <?php print $GLOBALS['sys_org_name']; ?> to a
more open and spontaneous internal code sharing culture then spread
the word about <?php print $GLOBALS['sys_name']; ?> and publish your
projects on the <?php print $GLOBALS['sys_name']; ?> site.<P>

We thank you for your support!<P>

The <a href="/staff.php"><?php print $GLOBALS['sys_name']; ?> Team</a>.

<?php
$HTML->footer(array());

?>

