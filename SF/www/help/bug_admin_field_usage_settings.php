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

help_header("Bug Administration -  Bug Field Usage Modification");

?>

<p>The Bug Tracking System allows you to define what fields you
want to use in the Bug Tracking System of this project. 

<p>On this page you can define the following bug field parameters:

<ul type="compact">

<li><b>Rank on Screen</b> : the rank number allows you to place the
field with respect to the others both in the bug submission form and
in the bug update form. The fields with smaller values will appear
first on the screen. The rank values doesn't have to be consecutive
values. It is a good idea to use values like 10,20,30,... so that it
is easy for you to insert new fields in the future without having to
renumber all the fields.

<li><b>Status:</b>
<ul>

  <li><i><u>Required</u></i> : required fields are always in use. They are often fields considered vital for a Bug Tracking System  like Bug ID, bug submitter,etc. You cannot change the status of these fields
  
  <li><i><u>Used</u></i> : the field is used by your project.

  <li><i><u>Unused</u></i> : the field is not used by your project.
</ul>

<li><b>Display this field:</b> you can decide if you want to see this field to appear on the bug submission form for project members and non project members. In general non project members are given access to  restricted set of fields when they submit a bug.
<p>As an example they are not supposed to know how critical a bug is ('Severity' field), how to prioritize it ('Priority' field) (or whom a bug should be assigned to ('Assigned To' field). Therefore these fields should not appear on non project members submission form.
<p>Finally, some fields have fixed display settings anf they always or never appear on the submission form. For instance the 'Bug ID' field never appears on the bug submission form because by definition a bug not yet submitted has no Bug ID.
<ul>

</ul>

<?php
help_footer();
?>
