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

help_header("Bug Administration -  Bug Field Usage List");

?>

<p>The Bug Tracking System allows you to define what fields you
want to use in the Bug Tracking System of this project. 

<p>This page shows you all the fields that are currently defined in
the system. The first part of the list shows the bug fields that
are currently in use in your project and the second part shows the
fields that are currently unused.

<p> To change the Status of a field (Used/Unused) as well as some
other parameters simply click on the field name.

Information displayed on this page:
<ul type="compact">

<li><b>Type:</b> fields can be of type
<ul>
  <li><i><u>Select Box</u></i> : this field will take its value from a
  predefined list of values that can be defined by the Project
  Adminstrator.

  <li><i><u>Text Field</u></i> and <i><u>Text Area</u></i> : allows the user to enter
  free text.

  <li><i><u>Date Field</u></i> : a text field that can only accept date/time
  information
</ul>

<br><font color="red">Important Remark</font>: Some fields appear twice in the
list. Although they have the same name they actually come with a
different type. Choose the type that best suits your needs: predefined values
('Select Box') or free text ('Text Field').<br><br>


<li><b>Rank on Screen</b> : the rank number allows you to place the field with
respect to the others both in the bug submission form and in the bug
update form. The fields with smaller values will appear first on the
screen. The rank values doesn't have to be consecutive values. It is a
good idea to use values like 10,20,30,... so that it is easy for you
to insert new fields in the future without having to renumber all the
fields.

<li><b>Scope:</b>
<ul>

  <li><i><u>System</u></i> : these fields have a limited level of
  customization. System fields of type 'Select Box' have a fixed
  number of values for the CodeX site globally. You can only change
  the label and the rank of these existing values but you can't create
  any new one.
  
  <li><i><u>Project</u></i> : these fields are fully customizable. You can create/delete field values, make it appear or not on the bug forms,etc.
</ul>

<li><b>Status:</b>
<ul>

  <li><i><u>Required</u></i> : required fields are always in use. They are often fields considered vital for a Bug Tracking System  like Bug ID, bug submitter,etc.
  
  <li><i><u>Used</u></i> : the field is used by your project.

  <li><i><u>Unused</u></i> : the field is not used by your project.
</ul>


</ul>

<?php
help_footer();
?>
