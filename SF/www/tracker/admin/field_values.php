<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Stephane Bouhet
//

$ath->adminHeader(array('title'=>'Tracker Administration - Field Values Administration','help' => 'TrackerAdministration.html#TrackerFieldValuesManagement'));

echo '<H2>Tracker \'<a href="/tracker/admin/?group_id='.$group_id.'&atid='.$atid.'">'.$ath->getName().'</a>\' - Field Values Administration</H2>';
$ath->displayFieldValuesEditList();

$ath->footer(array());

?>
