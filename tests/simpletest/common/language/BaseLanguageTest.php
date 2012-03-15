<?php
/* 
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Originally written by Nicolas Terray, 2008
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

require_once('common/language/BaseLanguage.class.php');
Mock::generatePartial('BaseLanguage', 'BaseLanguageTestVersion', array('loadAllTabFiles'));

class BaseLanguageTest extends UnitTestCase {
    
    function __construct($name = 'BaseLanguage test') {
        parent::__construct($name);
    }

    function setUp() {
        $this->glob = $GLOBALS;
        $GLOBALS['sys_incdir']            = dirname(__FILE__) . '/_fixtures/codendi/site-content';
        $GLOBALS['sys_pluginsroot']       = dirname(__FILE__) . '/_fixtures/codendi/plugins';
        $GLOBALS['sys_themeroot']         = dirname(__FILE__) . '/_fixtures/codendi/themes';
        $GLOBALS['sys_custom_incdir']     = dirname(__FILE__) . '/_fixtures/etc/site-content';
        $GLOBALS['sys_custompluginsroot'] = dirname(__FILE__) . '/_fixtures/etc/plugins';
        $GLOBALS['sys_custom_themeroot']  = dirname(__FILE__) . '/_fixtures/etc/themes';
        $GLOBALS['codendi_cache_dir']     = dirname(__FILE__) . '/_fixtures/tmp';
         
	if (!is_dir($GLOBALS['codendi_cache_dir'].'/lang')) {
	    mkdir($GLOBALS['codendi_cache_dir'].'/lang', 0777, true);
	}
    }
    
    function tearDown() {
        $tmpdir = $GLOBALS['codendi_cache_dir'] . '/lang/';
        $fd = opendir($tmpdir);
        while(false !== ($file = readdir($fd))) {
            if(is_file($tmpdir .'/'. $file)) {
                unlink($tmpdir .'/'. $file);
            }
        }
        
       $GLOBALS = $this->glob;
    }
    
    function testConstructor() {
        $l1 = new BaseLanguage(',lang1,lang2, lang3 ,lang4 , lang5,', 'lang1');
        $this->assertEqual(array('lang1','lang2','lang3','lang4','lang5'), $l1->allLanguages);
        
        $result = 'fail';
        try {
            $l2 = new BaseLanguage('lang1,lang2', 'do-not-exist');
        } catch (Exception $e) {
            if ($e->getMessage() == 'The default language must be part of supported languages') {
                $result = 'pass';
            } else {
                throw $e;
            }
        }
        $this->$result('An exception must be thrown if a default language is not supported');
        
        $result = 'fail';
        try {
            $l3 = new BaseLanguage('', '');
        } catch (Exception $e) {
            if ($e->getMessage() == 'You must provide supported languages (see local.inc)') {
                $result = 'pass';
            } else {
                throw $e;
            }
        }
        $this->$result('An exception must be thrown if supported languages are empty');
    }
    
    function testParseAcceptLanguage() {
        $l = new BaseLanguage('en_US,fr_FR', 'en_US');
        
        $this->assertEqual(array(
            'en-ca' => 1,
            'en'    => 0.8,
            'en-us' => 0.6,
            'de-de' => 0.4,
            'de'    => 0.2,
        ), $l->parseAcceptLanguage('en-ca,en;q=0.8,en-us;q=0.6,de-de;q=0.4,de;q=0.2'));
        
        $this->assertEqual(array(
            'en-us' => 1,
            'en'    => 0.8,
            'fr'    => 0.5,
            'fr-fr' => 0.3,
        ), $l->parseAcceptLanguage('en-us,en;q=0.8,fr;q=0.5,fr-fr;q=0.3'));
        
        $this->assertEqual(array(), $l->parseAcceptLanguage(''));
    }
    
    function testGetLanguageFromAcceptLanguage() {
        $l = new BaseLanguage('en_US,fr_FR', 'en_US');
        
        $this->assertEqual('en_US', $l->getLanguageFromAcceptLanguage(''));
        $this->assertEqual('en_US', $l->getLanguageFromAcceptLanguage('en'));
        $this->assertEqual('en_US', $l->getLanguageFromAcceptLanguage('en-us'));
        $this->assertEqual('en_US', $l->getLanguageFromAcceptLanguage('en-ca'));
        $this->assertEqual('en_US', $l->getLanguageFromAcceptLanguage('en-us,en;q=0.8,fr;q=0.5,fr-fr;q=0.3'));
        $this->assertEqual('en_US', $l->getLanguageFromAcceptLanguage('de-de'));
        $this->assertEqual('fr_FR', $l->getLanguageFromAcceptLanguage('fr'));
        $this->assertEqual('fr_FR', $l->getLanguageFromAcceptLanguage('fr-fr'));
        $this->assertEqual('fr_FR', $l->getLanguageFromAcceptLanguage('fr-ca'));
        $this->assertEqual('fr_FR', $l->getLanguageFromAcceptLanguage('fr-fr,fr;q=0.8,en-us;q=0.5,en;q=0.3'));
        
        $l2 = new BaseLanguage('en_US,fr_FR', 'fr_FR');
        $this->assertEqual('fr_FR', $l2->getLanguageFromAcceptLanguage(''));
        $this->assertEqual('fr_FR', $l2->getLanguageFromAcceptLanguage('de-de'));
        
    }
    
    function testGetLanguages() {
        $l1 = new BaseLanguage('en_US,fr_FR', 'en_US');
        $this->assertEqual(array(
            'en_US' => 'English',
            'fr_FR' => 'Français',
        ), $l1->getLanguages());
        
        $l2 = new BaseLanguage('en_US', 'en_US');
        $this->assertEqual(array(
            'en_US' => 'English',
        ), $l2->getLanguages());
        
        $l3 = new BaseLanguage('fr_FR', 'fr_FR');
        $this->assertEqual(array(
            'fr_FR' => 'Français',
        ), $l3->getLanguages());
        
        $l4 = new BaseLanguage('fr_CA', 'fr_CA');
        $this->assertEqual(array(
        ), $l4->getLanguages());
    }
    
    function testParseLanguageFile() {
        $l = new BaseLanguage('en_US,fr_FR', 'en_US');
        
        $result = array();
        $l->parseLanguageFile($GLOBALS['sys_incdir'].'/en_US/only-comments.tab', $result);
        $this->assertEqual(array(
        ), $result, 'Comments are ignored');
        
        
        $result = array();
        $l->parseLanguageFile($GLOBALS['sys_incdir'].'/en_US/empty-lines.tab', $result);
        $this->assertEqual(array(
        ), $result, 'Empty lines are ignored');
        
        
        $result = array(
            'file' => array('key1' => 'old-value')
        );
        $l->parseLanguageFile($GLOBALS['sys_incdir'].'/en_US/file.tab', $result);
        $this->assertEqual(array(
            'file' => array(
                'key1' => 'value',
                'key2' => 'value'
            )
        ), $result, 'Definitions are merged');
        
        $result = array();
        $l->parseLanguageFile($GLOBALS['sys_incdir'].'/en_US/include.tab', $result);
        $this->assertEqual(array(
            'inc'    => array('key1' => 'value'),
            'common' => array('key1' => 'value'),
        ), $result, 'Files are included');
        
    }
    
    function testLoadAllTabFiles() {
        $l = new BaseLanguage('en_US,fr_FR', 'en_US');
        
        $result = array();
        $l->loadAllTabFiles($GLOBALS['sys_incdir'], $result);
        $this->assertEqual(array(
            'file'   => array(
                'key1' => 'value',
                'key2' => 'value'
            ),
            'inc'    => array('key1' => 'value'),
            'common' => array('key1' => 'value'),
        ), $result);
    }
    
    function testDirectories() {
        $result = array();
        
        $l1 = new BaseLanguageTestVersion($this);
        $l1->expectOnce('loadAllTabFiles', array($GLOBALS['sys_incdir'].'/en_US', '*'));
        $l1->loadCoreSiteContent('en_US', $result);
        
        $l2 = new BaseLanguageTestVersion($this);
        $l2->expectOnce('loadAllTabFiles', array($GLOBALS['sys_custom_incdir'].'/en_US', '*'));
        $l2->loadCustomSiteContent('en_US', $result);
        
        $l3 = new BaseLanguageTestVersion($this);
        $l3->expectOnce('loadAllTabFiles', array($GLOBALS['sys_pluginsroot'].'/toto/site-content/en_US', '*'));
        $l3->loadPluginsSiteContent('en_US', $result);
        
        $l4 = new BaseLanguageTestVersion($this);
        $l4->expectOnce('loadAllTabFiles', array($GLOBALS['sys_custompluginsroot'].'/toto/site-content/en_US', '*'));
        $l4->loadPluginsCustomSiteContent('en_US', $result);
        
    }
    
    function testLoadOrder() {
        $result = array();
        
        $l = new BaseLanguageTestVersion($this);
        $l->expectAt(0, 'loadAllTabFiles', array($GLOBALS['sys_incdir'].'/en_US', '*'));
        $l->expectAt(1, 'loadAllTabFiles', array($GLOBALS['sys_custom_incdir'].'/en_US', '*'));
        $l->expectAt(2, 'loadAllTabFiles', array($GLOBALS['sys_pluginsroot'].'/toto/site-content/en_US', '*'));
        $l->expectAt(3, 'loadAllTabFiles', array($GLOBALS['sys_custompluginsroot'].'/toto/site-content/en_US', '*'));
        
        $l->loadAllLanguageFiles('en_US', $result);
    }
    
    function testDumpLanguageFile() {
        $l = new BaseLanguage('en_US', 'en_US');
        $l->dumpLanguageFile('my_lang', array('module' => array('key' => 'value')));
        $this->assertEqual("<?php\n\$this->text_array['module']['key'] = 'value';\n?>",
            file_get_contents($GLOBALS['codendi_cache_dir'] .'/lang/my_lang.php')
        );
    }
}
?>
