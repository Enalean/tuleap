<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

if ($details != '') { 

    // For none project members force the comment type to None (100)
    bug_data_add_history ('details',htmlspecialchars($details),$bug_id,100);  
    $feedback .= ' Comment added to bug ';
    
} else {
    
    $feedback .= ' Nothing Done ';

}

?>
