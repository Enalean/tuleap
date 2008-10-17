<?php
/* 
 * Copyright (c) The CodeX Team, Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

require_once('common/language/BaseLanguage.class.php');

class BaseLanguageTest extends UnitTestCase {
    
    function __construct($name = 'BaseLanguage test') {
        parent::__construct($name);
    }

    function testConstructor() {
        $l1 = new BaseLanguage('lang1,lang2, lang3 ,lang4 , lang5', 'lang1');
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
}
?>
