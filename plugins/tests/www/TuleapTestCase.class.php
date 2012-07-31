<?php
/*
 * Copyright (c) Enalean, 2011. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */


require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

require_once('common/include/Response.class.php');
Mock::generate('Response');

require_once 'common/layout/Layout.class.php';
Mock::generate('Layout');

require_once dirname(__FILE__).'/../../../tests/simpletest/common/user/UserTestBuilder.php';
require_once dirname(__FILE__).'/../../../tests/simpletest/common/include/builders/aRequest.php';

require_once 'MockBuilder.php';

/**
 * Abstract class to use for unit tests inside Tuleap.
 *
 * It typically setUp globals objects like Response and Language, common in all the platform.
 */
abstract class TuleapTestCase extends UnitTestCase {
    
    /**
     * @var Save/restore the GLOBALS
     */
    private $globals;
    
    /**
     * SetUp a test (called before each test)
     */
    public function setUp() {
        $this->globals = array();  // it is too simple to do a $g = $GLOBALS;
        foreach ($GLOBALS as $key => $value) {
            $this->globals[$key] = $value;
        }
        $GLOBALS['Language'] = new MockBaseLanguage();
        $GLOBALS['HTML']     = new MockLayout();
        $GLOBALS['Response'] = $GLOBALS['HTML'];
    }
    
    /**
     * tearDown a test (called after each test)
     */
    function tearDown() {
        unset($GLOBALS['Response']);
        unset($GLOBALS['Language']);
        unset($GLOBALS['HTML']);
        if ($this->globals !== null) {
            $GLOBALS = $this->globals;
        }
    }
    /**
     *    Tests to see if the method is a test that should
     *    be run, override default by searching methods that starts with 'it'
     *    is a candidate unless it is the constructor.
     *    @param string $method        Method name to try.
     *    @return boolean              True if test method.
     *    @access protected
     */
    function _isTest($method) {
        if (strtolower(substr($method, 0, 2)) == 'it') {
            return ! SimpleTestCompatibility::isA($this, strtolower($method));
        }
        return parent::_isTest($method);
    }
    
    function getLabel() {
        $label = parent::getLabel();
        return $this->cleanCamelCase($label);
    }

    /**
     *    Announces the start of the test.
     *    @param string $method    Test method just started.
     *    @access public
     */
    function before($method) {
        parent::before($this->cleanCamelCase($method));
    }
    /**
     *    Announces the end of the test. Includes private clean up.
     *    @param string $method    Test method just finished.
     *    @access public
     */
    function after($method) {
        parent::after($this->cleanCamelCase($method));
    }
    
    function cleanCamelCase($textInCamelCase) {
        $return = preg_replace_callback('@(?<!=[A-Z])[A-Z]@', array($this, 'replaceCamelUpperCase'), $textInCamelCase);
        $return = str_replace('test ', '', $return);
        return '<strong>' . ucfirst($return) . '</strong> ('. $textInCamelCase .')';
    }
    
    function replaceCamelUpperCase($match) {
        return ' '.strtolower($match[0]);
    }
    
    public function expectRedirectTo($url) {
        $GLOBALS['Response']->expectOnce('redirect', array($url));
    }
    
    public function expectFeedback($level, $message) {
        $GLOBALS['Response']->expectOnce('addFeedback', array($level, $message));
    }
    
    protected function setText($text, $args) {
        $GLOBALS['Language']->setReturnValue('getText', $text, $args);
    }
    
    protected function assertNotEmpty($string) {
        return $this->assertNotNull($string) && $this->assertNotEqual($string, '');
    }

    protected function assertArrayNotEmpty($all_artifact_nodes) {
        $this->assertFalse(count($all_artifact_nodes) == 0, "expected array not to be empty, but it contains 0 elements");
    }
   

    protected function assertNotBlank($string) {
        // What about trim() ?
        return $this->assertNotEmpty($string) && $this->assertNoPattern('/^[ ]+$/', $string);
    }
    
    /**
     * assert that $substring is present $string
     * @param string $string
     * @param string $substring
     * @return boolean true if $substring is present in $string
     */
    protected function assertStringContains($string, $substring) {
        return $this->assertPattern("/$substring/", $string);
    }
    
    /**
     * assert that uri has the specified parameters, no matter the possition in the uri
     * @param type $uri
     * @param type $param
     * @param type $value 
     */
    protected function assertUriHasArgument($uri, $param, $value) {
        $query_string = parse_url($uri, PHP_URL_QUERY);
        parse_str($query_string, $args);
        return $this->assertTrue(isset($args[$param]) && $args[$param] == $value);
    }
    
    /**
     * asserts that $string starts with the $start_sequence
     * @param type $string
     * @param type $start_sequence 
     */
    protected function assertStringBeginsWith($string, $start_sequence) {
        return $this->assertPattern("%^$start_sequence%", $string);
    }
    
    /**
     * Passes if var is inside or equal to either of the two bounds
     * 
     * @param type $var
     * @param type $lower_bound
     * @param type $higher_bound
     */
    protected function assertBetweenClosedInterval($var, $lower_bound, $higher_bound) {
        $this->assertTrue($var <= $higher_bound, "$var should be lesser than or equal to $higher_bound");
        $this->assertTrue($var >= $lower_bound,  "$var should be greater than or equal to $lower_bound");
    }

    /**
     * Asserts that an array has the expected number of items.
     * 
     * @param array $array
     * @param int $expected_count
     */
    protected function assertCount($array, $expected_count) {
        return $this->assertEqual(count($array), $expected_count);
    }
}
?>
