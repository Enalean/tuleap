<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"CVS Documentation"));
?>

<B>SourceForge CVS Documentation</B>

<P>This very basic right now, but it will get you up and running.
<P>For all developer (read/write) access, you will be using SSH.
SSH (1.x) client must be available to your local machine. The local
environment variable CVS_RSH must also be set to the path to ssh.
This is done on most linux (bash) systems by typing:
<UL><B><I>export CVS_RSH=ssh</I></B>
</UL>

<P>Anonymous CVS access uses CVS pserver and does not require SSH.

<P>If you get 'permission denied' errors with no prompt for a password,
you do not have this environment variable set properly or SSH is not
available to your system. Fix this before suspecting a password problem.

<P><B>How to import source code into your repository</B>
<UL>
<LI>On your local machine, change to the directory whose files (and subdirectories) 
you want to import. Everything now in the current directory
and all subdirectories will be imported into the tree.
<LI>Type the following, where loginname is your SourceForge login name,
yourproject is the unix group name of your project, and
directoryname is the name of the new CVS root level directory.
<BR><B><I>cvs -dloginname@cvs.yourproject.sourceforge.net:/cvsroot/yourproject import directoryname vendor start</I></B>
</UL>

<P><B>How to check out source via SSH</B>
<UL>
<LI>Type the following, making necessary obvious substitutions for
your username and project.
<BR><B><I>cvs -dloginname@cvs.yourproject.sourceforge.net:/cvsroot/yourproject co directoryname</I></B>
<LI>After initial checkout, you can change into this directory and execute
cvs commands without the -d tag. For example:
<BR><B><I>cvs update<BR>cvs commit -m "comments for this commit"<BR>cvs add myfile.c</I></B>
</UL>

<P><B>How to check out source anonymously through pserver</B>
<UL>
<LI>Type the following, making necessary obvious substitutions for
your username and project.
<BR><B><I>cvs -d:pserver:anonymous@cvs.yourproject.sourceforge.net:/cvsroot/yourproject login</I></B>
<LI>After anonymously logging in:
<BR><B><I>cvs -d:pserver:anonymous@cvs.yourproject.sourceforge.net:/cvsroot/yourproject co directoryname</I></B>
<LI>After initial checkout, you can change into this directory and execute
cvs commands without the -d tag. For example:
<BR><B><I>cvs update</I></B>
</UL>

<P><B>Additional References</B>
<UL>
<LI><A href="http://cvsbook.red-bean.com/">The CVS Book</A>
<LI><A href="http://www.loria.fr/~molli/cvs/doc/cvs_toc.html">CVS Docs at www.loria.fr</A>
</UL>

<?php
$HTML->footer(array());

?>
