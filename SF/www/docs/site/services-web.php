<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"Services - Web Server"));
?>

<P><B>SourceForge Services - Web Server</B>

<P>Each project receives its own Apache-style htdocs and cgi-bin directories.
CGI can be executed only from the cgi-bin directory and is ScriptAliased to
http://yourproject.SourceForge.net/cgi-bin/. Your domain is hosted on
a name-based VirtualHost on the main project web server.

<P>The Apache server is compiled with PHP3/MySQL support and is able to
access your MySQL database.

<P>There is a 100MB soft quota for your web site and anonymous FTP directory.
(The high-capacity file server has no limit.) More space can be requested.

<P><A href="/docs/site/services.php">[Return to Site Service Documentation]</A>

<?php
$HTML->footer(array());

?>
