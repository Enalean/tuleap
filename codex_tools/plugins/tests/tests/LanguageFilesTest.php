<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/

class LanguageFilesTest extends UnitTestCase {
    function TemplatePluginTest($name = 'Language Files test') {
        $this->UnitTestCase($name);
    }
    
    function testLanguagesFiles() {
        $cmd = 'cd '.$GLOBALS['codex_utils_prefix'].' ; CODEX_LOCAL_INC='. getenv('CODEX_LOCAL_INC') .' '.$GLOBALS['codex_utils_prefix'].'/analyse_language_files.pl 2>&1';
        $output = `$cmd`;
        if (preg_match('/[1-9]\s*missing keys/', $output)) {
            echo "<pre>\n$output\n</pre>";
            $this->fail();
        } else {
            $this->pass();
        }
    }
}
?>
