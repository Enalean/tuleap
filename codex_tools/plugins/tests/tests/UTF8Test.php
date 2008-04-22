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
        $cmd = 'find '.$GLOBALS['codex_dir'].'/ -not -wholename "*/'. implode('/*" -not -wholename "*/', $exclude_wholename) .'/*" -print -exec file -bi {} \; | grep -i iso -B 1';
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
    
    function testHtmlEncoding() {
        //file -i does not work well on text/xml files
        $this->_parseHtmlFiles($GLOBALS['codex_dir'].'/documentation/user_guide');
    }
    
    private function _parseHtmlFiles($file) {
        if (is_dir($file) && !in_array(basename($file), array('.', '..', '.svn'))) {
            foreach(glob($file .'/*') as $f) {
                $this->_parseHtmlFiles($f);
            }
        } else if (preg_match('/\.(xsl|xml|html)$/i', basename($file))) {
            $cmd = 'java -classpath '. dirname(__FILE__) .'/chardet.jar org.mozilla.intl.chardet.HtmlCharsetDetector file://'. $file;
            $result = `$cmd`;
            
            //ascii files are allowed
            //utf-8 files are allowed
            if ((strstr($result, 'CHARSET = ASCII') === FALSE) && 
                (strstr($result, 'Probable Charset = UTF-8') === FALSE)
            ) {
                $this->fail('The file [ '. $file . ' ] has '. implode(', ', explode("\n", $result)));
            }
        }
    }
}
?>
