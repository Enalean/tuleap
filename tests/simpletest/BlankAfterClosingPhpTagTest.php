<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class BlankAfterClosingPhpTagTest extends UnitTestCase {
    
    function __construct() {
        $this->NONE   = 0;
        $this->START  = 1;
        $this->END    = 2;
        
        //The directories that should not be parsed
        $this->exclude = array('.', '..', '.svn', 'simpletest', 'tiny_mce', 'phpwiki', 'SimplePie', 'ckeditor', 'xhprof_lib', 'xhprof_html', 'doxygen');
        
        //Same as before when the dirname is ambiguous
        $this->exclude_wholedir = '`(?:'. implode('|', array(
                                'plugins/IM/include/jabbex_api/_tests',
                                'plugins/IM/www/webmuc/lib/jsjac/utils',
                                'plugins/git/gitphp',
                                'plugins/git/gitphp-0.1.0',
                                'plugins/webdav/include/lib',
                                'plugins/doaprdf',
                                'plugins/admssw',
                                'plugins/foafprofiles',
                                'plugins/fusionforge_compat',
                                'tools/utils/releases_notes',
                                'plugins/mediawiki',
                                )) .')$`';
        
        //Those files are allowed to contains something before opening tag
        $this->allow_start = array(
            'cli/codendi.php',
            'cli/tuleap.php',
            'tools/utils/checkCommitMessage.php',
            'plugins/IM/include/jabbex_api/installation/install.php',
            'plugins/IM/www/webmuc/groupchat.php',
            'plugins/docman/bin/DocmanImport/FSDocmanUploader.class.php',
            'post_merge_actions.php',
            'import_all_xml.php',
            'export_all_xml.php',
            'export_project_xml.php',
        );
        
        //Those files are allowed to contain something after closing tag
        $this->allow_end = array(
            'cli/codendi.php',
            'cli/tuleap.php',
            'plugins/tests/www/index.php',
            'plugins/IM/www/webmuc/groupchat.php',
            'plugins/IM/www/webmuc/muckl.php',
            'plugins/IM/www/webmuc/roster.js.php',
            'src/www/file/confirm_download.php',
            'src/www/tracker/tracker_selection.php',
            'src/www/tracker/group_selection.php',
            'plugins/tracker/www/tracker_selection.php',
            'plugins/tracker/www/group_selection.php',
            'src/www/scripts/check_pw.js.php',
            'src/www/scripts/cross_references.js.php',
            'plugins/tests/www/mocha/src/core/check_pw.js.php',
            'plugins/tests/www/mocha/src/core/cross_references.js.php',
            'plugins/tracker/www/scripts/codendi/TrackerArtifact.js.php',
            'plugins/tracker/www/scripts/codendi/TrackerReports.js.php',
            'plugins/tracker/www/scripts/codendi/TrackerAdminFields.js.php',
            'site-content/en_US/others/default_page.php',
            'site-content/fr_FR/others/default_page.php',
            'site-content/en_US/mail/html_template.php',
            'plugins/git/site-content/en_US/git_url_example.php',
            'plugins/git/site-content/fr_FR/git_url_example.php',
            'autoload.php',
        );
    }
    
    function testNoBlankBeforeAndAfterClosingPhpTag() {
        $this->_parsePhpFiles($GLOBALS['codendi_dir'].'/');
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
