<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2003. All rights reserved
//
// $Id$
//
//
//  Written for CodeX by Stephane Bouhet
//

$ath->adminHeader(array('title'=>'Tracker Administration - User Permissions'));

echo '<H2>Users Permissions</H2>';
echo '<form>';
echo '<input type="hidden" name="group_id" value="'.$group_id.'">';
echo '<input type="hidden" name="atid" value="'.$atid.'">';
echo '<input type="hidden" name="func" value="adduser">';
echo 'Add a user: <input type="text" name="user_name" lenght="30"> <input type="submit" value="Add User">';
echo '</FORM>';
echo '<FORM>';
echo '<input type="hidden" name="group_id" value="'.$group_id.'">';
echo '<input type="hidden" name="atid" value="'.$atid.'">';
echo '<input type="hidden" name="func" value="updateperm">';
$ath->displayUsersPerm();
echo '<p align="center"><input type="submit" name="update" value="Update Permissions"></p></FORM>';

$ath->footer(array());

?>
