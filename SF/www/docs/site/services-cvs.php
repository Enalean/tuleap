<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"Services - CVS Repository"));
?>

<P><B>SourceForge Services - CVS Repository</B>

<P>Your project has been issued its own CVS Repository, not just
a directory on a master repository.

<P>This means that your cvs logs, history files, and behavior
are unique to your repository. This allows us to customize certain
projects on request. The old method of housing several directories
in one CVS tree also led to the mixing of log files and inability
to separate certain parts of projects easily.

<P>We've made custom changes to the CVS code to allow us to host
this many CVS Repositories through one access point, and to
clean up the handling of anonymous CVS users.

<P><B>SSH Developer Access</B>

<P>The most common method of accessing CVS repositories remotely
is through its "pserver" protocol implementation. This method is
insecure, however, as it broadcasts passwords in plaintext over
the net. It is possible to configure other methods of authentication
through pserver, but not without considerable effort on the client
side as well.

<P>We have chosen to allow developers to access the CVS reponsitories
through SSH, which the CVS client can easily do by setting one
environment variable (and installing SSH).

<P>While normally this would open up the CVS box to shell access,
an in-house developed custom shell ensures that only SSH/CVS access
is allowed.

<P><A href="/docs/cvs/">[View SourceForge CVS Documentation]</A>

<P><B>Anonymous CVS Access</B>

<P>We open up the repositories for anonymous access through pserver.
No passwords are sent in anonymous CVS access.

<P><A href="/docs/site/services.php">[Return to Site Service Documentation]</A>

<?php
$HTML->footer(array());

?>
