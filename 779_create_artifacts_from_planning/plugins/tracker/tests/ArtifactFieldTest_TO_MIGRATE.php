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

/* OBSOLETE 
if (!function_exists('require_whitebox')) {
    function require_whitebox($file) {
        $path = explode(PATH_SEPARATOR, get_include_path());
        $loaded = false;
        while(!$loaded && (list(,$p) = each($path))) {
            if (is_file($p . DIRECTORY_SEPARATOR . $file)) {
                $olddir = getcwd();
                $code = file_get_contents($p . DIRECTORY_SEPARATOR . $file);
                $code = preg_replace('/(\s*)(?:protected|private)(\s*function)/i', '$1public$2', $code);
                chdir(dirname($p . DIRECTORY_SEPARATOR . $file));
                eval('?>'.$code.'<?php ');
                chdir($olddir);
                $loaded = true;
            }
        }
        if (!$loaded) {
            file_get_contents($file);
        }
    }
}
require_whitebox(dirname(__FILE__).'/../include/ArtifactField_Integer.class.php');
Mock::generatePartial('ArtifactField_Integer', 'ArtifactField_IntegerTestVersion', array());

require_whitebox(dirname(__FILE__).'/../include/ArtifactField_Float.class.php');
Mock::generatePartial('ArtifactField_Float', 'ArtifactField_FloatTestVersion', array());

require_whitebox(dirname(__FILE__).'/../include/ArtifactField_Text.class.php');
Mock::generatePartial('ArtifactField_Text', 'ArtifactField_TextTestVersion', array('quote'));
*/
class ArtifactFieldTest extends UnitTestCase {
    function __construct($name = 'ArtifactField test') {
        parent::__construct($name);
    }
    /* OBSOLETE 

    function testBuildMatchExpression() {
        $int   = new ArtifactField_IntegerTestVersion();
        $float = new ArtifactField_FloatTestVersion();
        $text  = new ArtifactField_TextTestVersion();
        foreach(array('toto', 'titi', 'tutu', 'to\'t"o') as $w) {
            $text->setReturnValue('quote', addslashes($w), array($w));
        }
        
        $this->assertEqual("fieldname LIKE '%toto%'",
                           $text->buildMatchExpression('fieldname', 
                                                       array('value' => 'toto')));
        $this->assertEqual("fieldname LIKE '%to\\'t\\\"o%'",
                           $text->buildMatchExpression('fieldname', 
                                                       array('value' => 'to\'t"o')));
        $this->assertEqual("fieldname LIKE '%toto%' AND fieldname LIKE '%titi%' AND fieldname LIKE '%tutu%'",
                           $text->buildMatchExpression('fieldname', 
                                                       array('value' => 'toto titi    tutu')));
        $this->assertEqual("fieldname RLIKE 'regexp'",
                           $text->buildMatchExpression('fieldname', 
                                                       array('value' => '/regexp/')));
        
        $this->assertEqual("fieldname = 12",
                           $int->buildMatchExpression('fieldname', 
                                                      array('value' => '12')));
        $this->assertEqual("fieldname >= 12 AND fieldname <= 15",
                           $int->buildMatchExpression('fieldname', 
                                                      array('value' => '12-15')));
        $this->assertEqual("fieldname < 12",
                           $int->buildMatchExpression('fieldname', 
                                                      array('value' => '<12')));
        $this->assertEqual("fieldname <= 12",
                           $int->buildMatchExpression('fieldname', 
                                                      array('value' => '<=12')));
        $this->assertEqual("fieldname > 12",
                           $int->buildMatchExpression('fieldname', 
                                                      array('value' => '>12')));
        $this->assertEqual("fieldname >= 12",
                           $int->buildMatchExpression('fieldname', 
                                                      array('value' => '>=12')));
        $this->assertEqual("1",
                           $int->buildMatchExpression('fieldname', 
                                                      array('value' => 'invalid')));
        
        $this->assertEqual("fieldname = 1.2",
                           $float->buildMatchExpression('fieldname', 
                                                      array('value' => '1.2')));
        $this->assertEqual("fieldname = 1.2E-23",
                           $float->buildMatchExpression('fieldname', 
                                                      array('value' => '1.2e-23')));
        $this->assertEqual("fieldname >= 1.2E-21 AND fieldname <= 1.5E+34",
                           $float->buildMatchExpression('fieldname', 
                                                      array('value' => '1.2e-21-1.5e34')));
        $this->assertEqual("fieldname < 1.2",
                           $float->buildMatchExpression('fieldname', 
                                                      array('value' => '<1.2')));
        $this->assertEqual("fieldname <= 1.2",
                           $float->buildMatchExpression('fieldname', 
                                                      array('value' => '<=1.2')));
        $this->assertEqual("fieldname > 1.2",
                           $float->buildMatchExpression('fieldname', 
                                                      array('value' => '>1.2')));
        $this->assertEqual("fieldname >= 1.2",
                           $float->buildMatchExpression('fieldname', 
                                                      array('value' => '>=1.2')));
        $this->assertEqual("1",
                           $float->buildMatchExpression('fieldname', 
                                                      array('value' => 'invalid')));
        $this->assertEqual("1",
                           $float->buildMatchExpression('fieldname', 
                                                      array('value' => 'e+34')));
        $this->assertEqual("1",
                           $float->buildMatchExpression('fieldname', 
                                                      array('value' => '.2e+34')));
    }
    */
}
?>
