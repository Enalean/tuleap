<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//	Originally written by Laurent Julliard 2001, 2002, CodeX Team, Xerox
//

require "pre.php";

help_header("Bug Administration -  Bug Reports List");

?>


<p>The Bug Tracking System allows you to define your own bug
reports that you or your team can use to search your project bug
database. For each report you can specify a set of of bug fields to
use as search criteria as well as those bug fields that must be
displayed in the result table.

<p>Project Administrators can define project-wide bug reports that are
usable by all team members whereas other users can only define and
modify their own reports.

Information displayed on this page:

<ul type="compact">

<li><b>ID:</b> a number that uniquely identify the bug report

<li><b>Name:</b> the report short name as it will appear in the report
select box when you'll be using the bug browsing screen.

<li><b>Description:</b> a description of your report (e.g. Simple
Report, 'QA report'...)

<li><b>Scope:</b>
<ul>

  <li><i><u>Project</u></i> : this report will be usable by all
  project members. Only project administrators can define project-wide
  reports.
  
  <li><i><u>Personal</u></i> : this report will be usable by its creator only.
</ul>
<p>

<li><b>Delete?:</b> click the trash icon to delete the
report. Project-wide reports can only be deleted by project
administrators
<ul>
