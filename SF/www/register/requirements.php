<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    // Initial db and session library, opens session
session_require(array(isloggedin=>1));
$HTML->header(array(title=>"Project Requirements"));
?>

<H2>Step 1: <?php print $GLOBALS['sys_name']; ?> Services & Requirements (Service Specific Rules)</H2>

<p>
We are now offering a full suite of services for <?php print $GLOBALS['sys_name']; ?> projects. If
you haven't already, please be sure to browse the most recent revision of
the <a href="/docs/site" target="side_window"><?php print $GLOBALS['sys_name']; ?> Services</a>.
</p>

<p>
<b>Use of Project Account</b>
</p>

<p>
The space given to you on this server is given for the expressed purpose of
Xerox software development or, in the case of web sites, the advancement of
Code eXchange culture inside Xerox. For more information, please read the <?php print $GLOBALS['sys_name']; ?> Terms of Service ("Terms of Service") in Step 2.
</p>


<!-- LJ Not sure what it means in the Xerox context 
<p>
<b>Creative Freedom</b>
</p>

<p>
It is our intent to allow you creative freedom on your project. This is not
a totally free license, though. For our legal protection and yours there are
limits. Please know, however that we too are Open Source developers that
value our freedom. Details about these restrictions are described in the
Terms of Service.
</p>
-->
<!-- LJ Not sure what it means in the Xerox context 
<p>
<b>Advertisements</b>
</p>

<p>
You may not place any revenue-generating advertisements on a site hosted at
<?php print $GLOBALS['sys_name']; ?>.
</p>
-->
<p>
<b><?php print $GLOBALS['sys_name']; ?> Link</b>
</p>

<p>
If you host a web site at <?php print $GLOBALS['sys_name']; ?>, you must place one of our approved
graphic images on your site with a link back to <?php print $GLOBALS['sys_name']; ?>. The graphic may
either link to the main <?php print $GLOBALS['sys_name']; ?> site or to your project page on
<?php print $GLOBALS['sys_name']; ?>. We will leave placement up to you.  For information about how
to insert a <?php print $GLOBALS['sys_name']; ?> logo which will track your pageviews, please read
the <a href="/docman/display_doc.php?docid=21&group_id=1" target="side_window"><?php print $GLOBALS['sys_name']; ?> Logo display documentation</a>.
</p>

<p>
<b>Code eXchange Policy</b>
</p>

<p>
In the course of the registration process you will be presented with the Xerox Code eXchange Policy. This policy that has been patiently crafted by the Xerox Open Source Committee and the Xerox's Intellectual Property Law Dept and explains the rights and duties implied by code sharing. We strongly suggest that you use this default policy for your own project. Do not confuse this policy with Copyright:  in any case your team (and Xerox in general) will still own the code and the Xerox standard Copyright statement must be present in your source code.
</p>

<p>
If you wish to use another policy that is not currently approved by the
Xerox Open Source Committee, let us know and we will review these requests on a
case-by-case basis. But please be sure that the one we provide is not good enough before you contact us.
</p>

<p>
It is our intent to provide a permanent home for all versions of your code.
We do reserve the right, however, to terminate your project if there is due
cause, in accordance with the Terms of Service.
</p>

<BR><H3 align=center><a href="tos.php">Step 2: Terms of Service Agreement</a></H
3>

<?php
$HTML->footer(array());
?>

