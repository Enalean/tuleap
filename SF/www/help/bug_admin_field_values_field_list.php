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

help_header("Bug Administration -  Bug Field Values - Field List");

?>

<p>The Bug Tracking System allows you to define your own values for
most of the fields you have decided to use (see Field Usage menu
item). To customize the set of predefined values for a given field,
simply click on the corresponding field.


Information displayed on this page:
<ul type="compact">

<li><b>Field Label:</b> the name of the field
<li><b>Description:</b> what this field is about
<li><b>Scope:</b>
<ul>

  <li><i><u>System</u></i> : these fields have a limited level of
  customization. System fields of type 'Select Box' have a fixed
  number of values defined globally for the entire site. You can only
  change the label of system defined values but you cannot create new
  ones.
  
   <li><i><u>Project</u></i> : these fields are fully
   customizable. You can create new field values as well as
   hide/unhide existing ones.
</ul>

<ul>
