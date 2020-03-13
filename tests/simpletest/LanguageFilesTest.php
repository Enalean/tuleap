<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*/

class LanguageFilesTest extends TuleapTestCase
{

    public function testLanguagesFiles()
    {
        $basedir      = __DIR__ . '/../../';
        $cmd          = $basedir . '/src/utils/analyse_language_files.pl ' . $basedir . ' 2>&1';
        $return_value = 1;
        $output       = array();
        exec($cmd, $output, $return_value);
        $full_output = implode("\n", $output);
        if ($return_value != 0 || preg_match('/[1-9]\s*(missing|incorrect|duplicate) keys/', $full_output)) {
            echo "<pre>\n$full_output\n</pre>";
            $this->fail();
        }
    }
}
