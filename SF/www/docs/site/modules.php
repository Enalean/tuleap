<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"Documentation - File Modules"));
?>

<P><B>File Modules</B>

<P>Any given project may want to release one or more file products
under their project name. This will include any official released versions
of their source code, in any format.

<P>Before releasing files, you must first define a file module for
your project. If your project has only one development tree, you will
probably define only one module. If your project will be releasing multiple
filesets, then additional modules may be defined.

<P>A file module defines an actual software product. When other
projects choose to make references to your products, for dependencies
or other reasons, it is your file modules that will be referenced. For this
reason, when a file module is defined, it <B>cannot be deleted</B> in
case it is referenced specifically by other parts of the site.

<P><B>IMPORTANT!</B>

<P>File modules are always referenced through their parent project.
People do not search for your modules, they search for your project.
Modules will share bug tracking, task management, forums, developers,
web site, and cvs repository.

<P><B><I>For MOST projects, there will only be the need to define one file
release module.</I></B>

<P><B>New modules vs. New projects</B>

<P>Projects share administrators and developers, have one web site,
and share a CVS repository.
If it is conceivable that a new file module would <B>ever</B> spin off
into its own development cycle, with a non-identical developer set or requiring
an independent web site, it is probably best to assign the product
a new project, rather than just a module. It is OK to have the same
developer set on two projects, if they are releasing two different products.

<P><B>Examples</B>

<P>For a project I used to work on, ThorMail, we used to simultaneously
maintain two file release modules. Every time we released a new version
of ThorMail, we would simultaneously release a ThorData module, which
included some utility scripts and patches for additional programs that
would work with ThorMail. These two files were always dependent upon
eachother, would always have the same set of developers, and would never
be separated. They would have been two modules under one project in
SourceForge.

<?php
$HTML->footer(array());

?>
