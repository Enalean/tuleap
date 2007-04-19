<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// $Id$

// Special case for command line call
if (isset($argc)) {
    if ($argc == 2) {
        $scriptname = $argv[1] . '.class.php';
    }
}

// Check if the classname is defined
if (isset($scriptname) && $scriptname) {
    // Check if the file of the class is defined
    if (is_file("scripts/".$scriptname)) {

        require_once("scripts/".$scriptname);

        // instanciate the class and apply the upgrade
        $classname = 'Update_' . substr(basename($scriptname, ".class.php"), 0, 3);
        $upgrade = new $classname();
        $upgrade->apply();

        if ($upgrade->isUpgradeError()) {
            $upgrade->writeFeedback("Upgrade failed :".$upgrade->getLineSeparator());
            $errors = $upgrade->getUpgradeErrors();
            print_r($upgrade->getUpgradeErrors());
        } else {
            $upgrade->writeFeedback("Upgrade successfuly done!".$upgrade->getLineSeparator());
        }

    } else {
        echo "Undefined file : ".$scriptname;
    }

} else {
    echo "Missing parameters : <script name>\n";
}


?>
