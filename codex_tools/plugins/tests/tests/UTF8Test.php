<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/

class UTF8Test extends UnitTestCase {
    function TemplatePluginTest($name = 'UTF-8 encoding test') {
        $this->UnitTestCase($name);
    }
    
    function testEncoding() {
        $exclude_wholename = array(
            '.svn',
            'simpletest',
        );
        $cmd = 'find '.$GLOBALS['codex_dir'].'/codex_tools/ -not -wholename "*/'. implode('/*" -not -wholename "*/', $exclude_wholename) .'/*" -print -exec file -bi {} \; | grep -i iso -B 1';
        $handle = popen($cmd, 'r');
        $error = false;
        while(!feof($handle) && ($line = fgets($handle))) {
            if (!$error) {
                echo "<pre>\n";
            }
            echo $line;
            flush();
            $error = true;
        }
        if ($error) {
            echo "\n</pre>";
            $this->fail();
        } else {
            $this->pass();
        }
    }
}
?>
