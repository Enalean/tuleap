<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Stephane Bouhet
//

$ath->adminHeader(array('title'=>'Tracker Administration - Field Usage Administration','help' => 'HELP_FIXME.html'));

echo '<H2>Tracker \'<a href="/tracker?group_id='.$group_id.'&atid='.$atid.'">'.$ath->getName().'</a>\' - Field Values Administration</H2>';
$ath->displayFieldUsageList();
$ath->displayFieldUsageForm();

$ath->footer(array());

?>
