<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once('CLI_Action_Docman_CreateItem.class.php');

class CLI_Action_Docman_CreateDocument extends CLI_Action_Docman_CreateItem  {

    function __construct($name, $description) {
        parent::__construct($name, $description);

        $this->addParam(array(
            'name'           => 'obsolescence_date',
            'description'    => '--obsolescence_date=<yy-mm-dd|yyyy-mm-dd>    Date when the document will be obsolete',
            'soap'     => true,
        ));
    }

    function validate_obsolescence_date(&$date) {
        if (isset($date)) {
            $match = preg_match('/^([0-9]+)-([0-9]+)-([0-9]+)$/', $date, $m);
            if (!$m) {
                echo $this->help();
                exit_error('Obsolete date format must be: yyyy-mm-dd or yy-mm-dd');
            } else {
                $month  = $m[2];
                $day    = $m[3];

                if ($month > 12 || $day > 31 || $month < 1 ||  $day < 1) {
                    echo $this->help();
                    exit_error('Obsolescence date format must be: yyyy-mm-dd or yy-mm-dd. Please respect the correct ranges: 1 < mm < 12; 1 < dd < 31');
                }
            }
        }
        return true;
    }
}
