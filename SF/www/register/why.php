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
<?php print $GLOBALS['sys_name']; ?> would like to extend an invitation to any Xerox internally developed project (so called Inner Source project) to be hosted for no price and no catch. The Xerox Open Source team has been formed in July 2000. One of our goal was to transpose the success of the external Open Source phenomenon and culture <b>inside</b> our Corporate environment. <?php print $GLOBALS['sys_name']; ?> is our contribution to the Xerox software developers community.
</p>

<p>
<i>Why are we doing this ?</i><br>
A lot of Xerox projects are being hosted all over the place in Xerox and not only is it difficult to know who is working on what but it also implies that each Xerox development team is re-creating the same kind os development environment again and again. So, building on the experience and software designed by the <a href="http://sourceforge.net">SourceForge.net</a> people, we have decided to transpose this concept internally in Xerox and provide all Xerox development team with a series of tools and services that will make their life much easier, save time, allow them to concetrate on their code while letting the rest of the Xerox World know about their work.
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
<li>We will not require that you place any banner or even reference to <?php print $GLOBALS['sys_name']; ?> on your page. 
<li>Account acceptance is at our discretion and is based on the following criteria:
<ul>
<li><i>relevance</i>: Xerox internally developed sofware projects only, 
<li><i>legitimacy</i>: your project must have a goal aligned with Xerox business,
<li><i>appropriateness</i>: hosted projects must provide source code or documentation).
</ul>

<p>Furthermore, <?php print $GLOBALS['sys_name']; ?> reserves the
right to revoke an existing account without notice if there is due
cause.
</p>

<p> We realize we may be presenting a few technical restrictions here
and there. However, If this project receives sufficient interest from
the Xerox developers community, we will obtain more computers and more
disk space for the project.
</p>

<p>
<a href="/register/">Register New Project</a>
</p>

<?php
$HTML->footer(array());

?>

