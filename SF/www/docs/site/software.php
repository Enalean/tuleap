<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"About SorceForge"));
?>

<P>
<h2>The Software behind SourceForge</h2>

<P>In addition to writing much of our own software, we utilized the
efforts of many great software developers, and would like to 
recognize their products here. These are all fantastic products and
we would be happy to talk about how we have implemented them to any
interested parties. 

<HR>

<table border=0 cellspacing=2 cellpadding=2 bgcolor="" valign="bottom">
<tr valign=top>
<td><a href="http://www.amanda.org/">Amanda</a></td>
<td>AMANDA, the Advanced Maryland Automatic Network Disk Archiver, is a backup system that allows the administrator of a LAN to set up a single master backup server to back up multiple hosts to a single large capacity tape drive.</td>
</tr>

<tr valign=top>
<td><a href="http://www.apache.org/">Apache</a></td>
<td>Apache is an award winning webserver that made this site possible.</td>
</tr>

<tr valign=top>
<td><a href="http://www.boa.org/">Boa</a>*</td>
<td>Boa is a single-tasking HTTP server. That means that unlike traditional web servers, it does not fork for each incoming connection, nor does it fork many copies of itself to handle multiple connections.</td>
</tr>

<tr valign=top>
<td><a href="http://www.cyclic.com/">CVS</a>*</td>
<td>CVS is the Version Control system that we use, for both our development and that of the projects.</td>
</tr>

<tr valign=top>
<td><a href="http://stud.fh-heilbronn.de/~zeller/cgi/cvsweb.cgi/">cvsweb</a>*</td>
<td>A script to allow easy viewing of live CVS repositories.</td>
</tr>

<tr valign=top>
<td><a href="http://www.leonine.com/~ltemplin/">Grap</a></td>
<td>General execution wRAPper. Grap is a wrapper designed to verify commands before executing them.</td>
</tr>

<tr valign=top>
<td><a href="http://www.linuxvirtualserver.org/">IPVS</a></td>
<td>Virtual server is a scalable and highly available server built on a cluster of real servers. The architecture of the cluster is transparent to end users, and the users see only a single virtual server.</td>
</td>

<tr valign=top>
<td><a href="http://www.list.org/">GNU MailMan</a>*</td>
<td>Mailman is software to help manage email discussion lists, much like Majordomo and Smartmail. Unlike most similar products, Mailman gives each mailing list a web page, and allows users to subscribe, unsubscribe, etc. over the web.</td>
</tr>

<tr valign=top>
<td><a href="http://www.modssl.org/">ModSSL</a></td>
<td>The mod_ssl project provides strong cryptography for the Apache 1.3 webserver via the Secure Sockets Layer (SSL v2/v3) and Transport Layer Security (TLS v1) protocols by the help of the Open Source SSL/TLS toolkit OpenSSL, which is based on SSLeay from Eric A. Young and Tim J. Hudson.</td>
</tr>

<tr valign=top>
<td><a href="http://www.mysql.com/">MySQL</a></td>
<td>MySQL is a true multi-user, multi-threaded SQL database server. SQL (Structured Query Language) is the most popular and standardized database language in the world. MySQL is a client/server implementation that consists of a server daemon mysqld and many different client programs and libraries.</td>
</tr>

<tr valign=top>
<td><a href="http://www.php.net/">PHP</a></td>
<td>PHP is a server-side, cross-platform, HTML embedded scripting language.</td>
</tr>

<tr valign=top>
<td><a href="http://www.redhat.com/">Redhat Linux</a></td>
<td>Red Hat Linux is a powerful, extremely stable, next-generation computer operating system that provides a high performance computing environment for both server and desktop PCs.</td>
</tr>

<tr valign=top>
<td><a href="http://www.ssh.fi/">SSH</a></td>
<td>Secure Shell is a program that allows you to open an encrypted connection to another computer.  It allows remote execution of command and copying of files from one machine to another.</td>
</tr>

</table>

<p>* Denotes that we modified the standard version to suit our needs.</p>

<?php
$HTML->footer(array());

?>

