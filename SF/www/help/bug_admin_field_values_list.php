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

help_header("Bug Administration -  Bug Field Values List");

?>

<p>The Bug Tracking System allows you to define your own values for
most of the fields you have decided to use (see Field Usage menu
item). To customize a value, simply click on the corresponding field.


<p>This page shows you a list of Active and Hidden values for a given field.

<p>Information displayed on this page:
<ul type="compact">

<li><b>ID</b>: the ID column is only shown for fields which have a
'System' scope. For this type of field the ID often carry a semantic
meaning and if you redefine the value label associated with a given ID
it is important that you know about the ID.

<br> As an example the Severity field has 9 possible values and the
bugs with the highest ID are always considered as the most critical
bug by the system. So it would be bad idea for instance to redefine
the value label for ID 9 to something like 'Very Minor' :-) but it is
ok to redefine it as 'Fatal'.

<li><b>Value Label</b>: the text label of the value as shown in the select box

<li><b>Description</b>: meaning of the value

<li><b>Rank</b>: allows you to define the order of the field values in
the select box. The smaller values appear first in the select box.

<li><b>Status</b>: 
<ul>
<li><i><u>Active</u></i> : these values are currently visible in your
bug field select box.

<li><i><u>Hidden</u></i> : these values are not visible in your bug
field select box.

<li><i><u>Permanent</u></i> : these values are permanent and cannot be
removed from the list of possible values. They always appear in the
select box.
</ul>
<p>You can decide to activate/hide a value at any time in the life of
the project.

</ul>

<p>For fields with a 'Project' scope you also have the ability to
define a new value by filling out the form at the bottom of the
screen.
