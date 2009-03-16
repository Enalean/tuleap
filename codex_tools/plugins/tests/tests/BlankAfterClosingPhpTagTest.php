<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/

class BlankAfterClosingPhpTagTest extends UnitTestCase {
    
    function BlankAfterClosingPhpTagTest($name = 'Blank after closing php tag test') {
        $this->UnitTestCase($name);
        $this->NONE   = 0;
        $this->START  = 1;
        $this->END    = 2;
        
        //The directories that should not be parsed
        $this->exclude = array('.', '..', '.svn', 'simpletest', 'tiny_mce', 'phpwiki', 'SimplePie');
        
        //Same as before when the dirname is ambiguous
        $this->exclude_wholedir = '`(?:'. implode('|', array(
                                'plugins/IM/include/jabbex_api/tests',
                                'plugins/IM/www/webmuc/lib/jsjac/utils',
                                )) .')$`';
        
        //Those files are allowed to contains something before opening tag
        $this->allow_start = array(
            'cli/codendi.php',
            'codex_tools/utils/checkCommitMessage.php',
            'plugins/IM/include/jabbex_api/installation/install.php',
            'plugins/IM/www/webmuc/groupchat.php',
        );
        
        //Those files are allowed to contain something after closing tag
        $this->allow_end = array(
            'cli/codendi.php',
            'plugins/tests/www/index.php',
            'codex_tools/tests/www/index.php',
            'plugins/IM/www/webmuc/groupchat.php',
            'plugins/IM/www/webmuc/muckl.php',
            'plugins/IM/www/webmuc/roster.js.php',
            'src/www/tracker/tracker_selection.php',
            'src/www/tracker/group_selection.php',
            'src/www/scripts/check_pw.js.php',
            'src/www/scripts/cross_references.js.php',
            'plugins/salome/include/SalomeWithCodex.jnlp.php',
            'site-content/en_US/others/default_page.php',
            'site-content/fr_FR/others/default_page.php'
        );
    }
    
    function testNoBlankBeforeAndAfterClosingPhpTag() {
        $this->_parsePhpFiles($GLOBALS['codex_dir'].'/');
    }
    
    protected function _parsePhpFiles($file) {
        if (is_dir($file) && !in_array(basename($file), $this->exclude) && !preg_match($this->exclude_wholedir, $file)) {
            foreach(glob($file .'/*') as $f) {
                $this->_parsePhpFiles($f);
            }
        } else if (preg_match('/\.php$/i', basename($file))) {
            
            $content = file_get_contents($file);
            $allow = $this->_allow($file);
            if (!($allow & $this->START)) {
                $this->assertPattern('/^<\?(?:php|\s)/', $content, 'The file '. $file .' should not contain something *before* php opening tag');
            }
            if (!($allow & $this->END)) {
                $this->assertPattern('/\?>$/', $content, 'The file '. $file .' should not contain something *after* php closing tag');
            }
        }
    }
    
    protected function _allow($file) {
        $allow = $this->NONE;
        reset($this->allow_start);
        while(!($allow & $this->START) && list(,$f) = each($this->allow_start)) {
            if (preg_match('`'. $f .'$`', $file)) {
                $allow += $this->START;
            }
        }
        reset($this->allow_end);
        while(!($allow & $this->END) && list(,$f) = each($this->allow_end)) {
            if (preg_match('`'. $f .'$`', $file)) {
                $allow += $this->END;
            }
        }
        return $allow;
    }
}
?>
