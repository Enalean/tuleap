<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    // Initial db and session library, opens session
$HTML->header(array(title=>"Why Register?"));
?>

<p>
<?php print $GLOBALS['sys_name']; ?> would like to extend an invitation to any internally developed project  to be hosted for no price and no catch. Our goal is to transpose the success of the external Open Source phenomenon and culture <b>inside</b> our Corporate environment. <?php print $GLOBALS['sys_name']; ?> is our contribution to the <?php print $GLOBALS['sys_org_name']; ?> software developers community.
</p>

<p>
<i>Why are we doing this ?</i><br>
A lot of internal projects are being hosted all over the place in <?php print $GLOBALS['sys_org_name']; ?> and not only is it difficult to know who is working on what but it also implies that each development team is re-creating the same kind of development environment again and again. So, building on the experience and software designed by the <a href="http://sourceforge.net">SourceForge.net</a> people, we have decided to transpose this concept inside <?php print $GLOBALS['sys_org_name']; ?> and provide all development teams with a series of tools and services that will make their life much easier, save time and allow them to concetrate on their code while letting the rest of the organization know about their work.
</p>

<p>
<i>What we will provide:</i>
<li>Web Site Space 
<li>E-mail Lists (Public & Private)
<li>CVS support (both anonymous read & secure write)
<li>FTP Server
<li>Light weight Project Management Software
<li>Bug-Tracking System (Public & Private)
<li>Discussion Forums (Public & Private)
<!--<li>Press Releases-->
<!--<li>Package Server/Build Server (tarballs, RPMs, DEBs)-->
<li>Subdomain hosting -- i.e., yourproject.<?php echo $sys_default_domain; ?>
<li>There are no hard quotas. However, should your directory exceed 150 megabytes, we will check to
see if the space your using is well justified. This policy will change over time as more resources are
acquired and allocated for this project.
<li>Restricted Shell Access                         
</p>                           
           
<p>
<li>Account acceptance is at our discretion and is based on the following criteria:
<ul>
<li><i>relevance</i>: <?php print $GLOBALS['sys_org_name']; ?> internally developed sofware projects only, 
<li><i>legitimacy</i>: your project must have a goal aligned with <?php print $GLOBALS['sys_org_name']; ?> business,
<li><i>appropriateness</i>: hosted projects must provide source code or documentation).
</ul>

<p>Furthermore, <?php print $GLOBALS['sys_name']; ?> reserves the
right to revoke an existing account without notice if there is due
cause.
</p>

<p>
<a href="/register/">Register A New Project</a>
</p>

<?php
$HTML->footer(array());

?>

