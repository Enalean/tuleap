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

help_header("Bug Administration -  Bug Field Value Settings");

?>

<p>The Bug Tracking System allows you to define and update your
own values for most of the fields you have decided to use (see Field
Usage menu item).


<p>This page shows allows you to update the parameters of a given
value. Information displayed on this page:

<ul type="compact">

<li><b>Value Label</b>: the text label of the value as shown in the select box

<li><b>Rank</b>: allows you to define the order of the field values in
the select box. The smaller values appear first in the select box. We
strongly advise you to leave some space in between 2 rank numbers (e.g
use 100, 200, 300,...) to make future insertion of new values easier.

<br><font color="red">Important remark:</font> if there is a permanent
'None' value defined for this field, the rank number for your own value
must always be greater than the 'None' rank.

<li><b>Status</b>: 
<ul>
<li><i><u>Active</u></i> : this value is visible in your
bug field select box.

<li><i><u>Hidden</u></i> : this value is not visible in your bug
field select box.

<li><i><u>Permanent</u></i> : these values are permanent and cannot be
removed from the list of possible values. They always appear in the
select box.
</ul>
<p><u>Remark</u>: You can decide to activate/hide a value at any time
in the life of the project. When you hide a value it wont show up in
the select box but old bugs already using this value will continue to
display ok.

<li><b>Description</b>: meaning of the value (optional but useful)

</ul>
