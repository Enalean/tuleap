<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    // Initial db and session library, opens session
$HTML->header(array(title=>"Welcome to Project Alexandria"));
?>

<H1>Summary of SourceForge Services</H1>

<P>Following is a list of the services which 
we currently provide to SourceForge projects.
We expect to continually add services and improve upon
the services on this list.

<P><B>The SourceForge Team Development Environment (TDE)</B>

<UL>
<li><B>TDE Web Administrative Tools</B>
<BR><I>Advanced web-based administrative tools
provide easy maintenance for all facets of your project.</I>
<BR><li><B>Web Server</B>
<BR><I>Apache HTTP Server, PHP3/PERL/CGI support, and a dedicated subdomain.
Accounts are Apache VirtualHosts with separate /cgi-bin directories.
100MB space without monitoring - more if the space is justified.</I> 
<BR><A href="services-web.php">[Details...]</A>
<LI><B>MySQL Database</B>
<BR><I>Your shell account and web server have access to your own MySQL database.</I>
<BR><LI><B>Your Own Domain</B>
<BR><I>If you make the registration with Internic, we can
answer for your domain. This will affect all domains that would otherwise be
referenced by yourproject.SourceForge.net.</I>
<!--<BR><LI><B>Mail Aliases</B>
<BR><I>A web interface provides unlimited control over mail aliases at
alias@yourproject.SourceForge.net.</I>-->
<BR><li><B>E-mail Lists</B> (Public & Private)
<BR><I>The Mailman mailing list manager and majordomo are available
for your mailing lists.</I>
<BR><li><B>CVS Repository</B> 
<BR><I>Each project has its own CVS repository (with separate CVSROOT).
Developers are granted secure access to the tree through SSH. Anonymous
CVS/pserver access is granted to the general public.</I>
<BR><A href="services-cvs.php">[Details...]</A>
<li><B>High-capacity File Server</B>
<BR><I>Released versions of your project are stored on our
extreme-capacity fileserver. Current capacity for this server
is 1000 concurrent ftp and 3000 concurrent http connections.</I>
<BR><li><B>Mid-capacity FTP directory</I>
<BR><I>Full read/write access to your own FTP directory, available
anonymously to the outside world.</I>
<BR><li><B>Project Management Software</B>
<BR><li><B>Bug-Tracking System</B> (Public & Private)
<BR><li><B>Discussion Forums</B> (Public & Private)
<BR><li><B>SSH Account</B>
<BR><I>A restricted SSH account provides basic shell functions, your own crontab,
and access to your HTTP account for you and all of your developers.</I>
</p>                           
           

<?php
$HTML->footer(array());

?>

