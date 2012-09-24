<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
* 
*/

class LanguageFilesTest extends UnitTestCase {

    function LanguageFilesTest($name = 'Language Files test') {
        $this->UnitTestCase($name);
    }

    function testLanguagesFiles() {
        $local_inc = getenv('CODENDI_LOCAL_INC')?getenv('CODENDI_LOCAL_INC'):'/etc/codendi/conf/local.inc';
        $cmd       = 'cd '.$GLOBALS['codendi_utils_prefix'].' ; CODENDI_LOCAL_INC='.$local_inc.' '.$GLOBALS['codendi_utils_prefix'].'/analyse_language_files.pl 2>&1';
        $output    = `$cmd`;
        if (preg_match('/[1-9]\s*(missing|incorrect|duplicate) keys/', $output)) {
            echo "<pre>\n$output\n</pre>";
            $this->fail();
        } else {
            $this->pass();
        }
    }

}

?>