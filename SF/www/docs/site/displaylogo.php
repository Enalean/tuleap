<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"Displaying the SourceForge Logo"));

print '<P><B>Displaying the SourceForge Logo</B>

<P>We ask that all projects hosted on SourceForge display a small
SourceForge logo on their homepage.

<P>The display of this logo serves two purposes. First, it drives
traffic to SourceForge, and ultimately back to the Open Source
projects that we host. SourceForge has a lot of potential for
"cross linking" many OpenSource projects, and the logo display
facilitates this.

<P>Second, when the logo is displayed, a log entry is generated
for your project that allows us to track hits to each of our hosted
projects. This information will be compiled and presented to
our users in "top" activity lists for hosted projects. This will
also help us to identify projects that may require additional hosting
resources due to large amounts of activity. 

<P>Please use the following URL to place the graphic on your page:
<BR><B>http://sourceforge.net/sflogo.php?group_id=0&type=1</B>

<P>In anchor and image tags, this would appear as follows:
<BR><B>&lt;A href="http://sourceforge.net"&gt;
<BR>&lt;IMG src="http://sourceforge.net/sflogo.php?group_id=0&type=1"
width="88" height="31" border="0"&gt;&lt;/A&gt;
</B>

<P><B><I>Important!</I></B> Substitute the number after "group_id"
(currently 0)
with your own project/group number, which is displayed at the top
of your project page on SourceForge.

<P>This is the image that we now display:
<P><IMG src="http'
.(session_issecure()?'s':'').
'://sourceforge.net/sflogo.php?group_id=0&type=1"
width="88" height="31">
<P>Please email <A href="mailto:admin@sourceforge.net">admin@sourceforge.net</A>
with any questions you may have.
';


$HTML->footer(array());

?>
