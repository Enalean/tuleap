<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
* 
*/

class UTF8Test extends UnitTestCase {
    function UTF8Test($name = 'UTF-8 encoding test') {
        $this->UnitTestCase($name);
    }
    
    function testEncoding() {
        $exclude_wholename = array(
            '.svn',
            'simpletest',
            'tiny_mce',
            'phpwiki',
            'code-coverage-report',
            'plugins/fusionforge_compat/include/arc',
        );
        $cmd = 'find '.$GLOBALS['codendi_dir'].'/ -not -name "iso-8859-1_to_utf-8.sh" -not -wholename "*/'. implode('/*" -not -wholename "*/', $exclude_wholename) .'/*" -print -exec file -bi {} \; | grep -i iso-8859 -B 1';
        $handle = popen($cmd, 'r');
        $error = false;
        $filename = '';
        while(!feof($handle) && ($line = fgets($handle))) {
            if (strpos($line, '--') !== 0) {
                if (!$filename ) {
                    $filename = $line;
                } else {
                    $this->fail('The file [ '. $filename .' ] is '. $line);
                    $filename = '';
                }
            }
            $error = true;
        }
        if (!$error) {
            $this->pass();
        }
    }
    
    function testHtmlEncoding() {
        //file -i does not work well on text/xml files
        $this->_parseHtmlFiles($GLOBALS['codendi_dir'].'/');
    }
    
    private function _parseHtmlFiles($file) {
        if (is_dir($file) && !in_array(basename($file), array('.', '..', '.svn', 'phpwiki', 'code-coverage-report'))) {
            foreach(glob($file .'/*') as $f) {
                $this->_parseHtmlFiles($f);
            }
        } else if (preg_match('/\.(xsl|xml|html)$/i', basename($file))) {
            $cmd = 'java -classpath '. dirname(__FILE__) .'/chardet.jar org.mozilla.intl.chardet.HtmlCharsetDetector file://'. $file;
            $result = `$cmd`;
            
            //ascii files are allowed
            //utf-8 files are allowed
            if ((strstr($result, 'CHARSET = ASCII') === FALSE) && 
                (strstr($result, 'CHARSET = UTF-8') === FALSE) && 
                (strstr($result, 'Probable Charset = UTF-8') === FALSE)
            ) {
                $this->fail('The file [ '. $file . ' ] has '. implode(', ', explode("\n", $result)));
            } else {
                $this->pass();
            }
        }
    }
}
?>
