<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"Suspended Account"));
list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		
?>

<P><B>Suspended Account</B>

<P>Your account has been suspended. If you have questions regarding your suspension,
please email <A href="mailto:staff@<?php echo $host; ?>">staff@<?php echo $host; ?></A>.
Inquiries through other channels will be directed to this address.

<?php
$HTML->footer(array());

?>
