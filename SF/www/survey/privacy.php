<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require('pre.php');
require('../survey/survey_utils.php');
survey_header(array('title'=>'Survey'));

?>

<H1>Survey Privacy</H1>

The information collected in these surveys will never be 
sold to third parties or used to solicit you to purchase 
any goods or services.
<P>
This information is being gathered to build a profile 
of the projects and developers being surveyed. That profile 
will help visitors to the site understand the quality of a 
given project.
<P>
The ID's of those who answer surveys are suppressed 
and not viewable by project administrators or the public 
or third parties.
<P>
The information gathered is used only in aggregate 
form, not to single out specific users or developers.
<P>
If any changes are made to this policy, it will affect 
only future data that is collected and the user will of 
course have the ability to 'opt-out'.
<P>
<B>The <?php print $GLOBALS['sys_name']; ?> Team</B>
<P>
<?php

survey_footer(array());

?>
