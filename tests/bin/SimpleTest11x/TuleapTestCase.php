<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

require_once __DIR__ . '/../../simpletest/common/include/builders/aRequest.php';
require_once __DIR__ . '/../../lib/SimpleMockOngoingInterlligentStub.php';
require_once __DIR__ . '/../../lib/MockeryOngoingIntelligentStub.php';
require_once __DIR__ . '/../../lib/TestHelper.class.php';
require_once __DIR__ . '/../../lib/MockBuilder.php';

abstract class TuleapTestCase extends UnitTestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    /**
     * @var Save/restore the GLOBALS
     */
    private $globals;

    /**
     * @var String Path to a directory where temporary things can be done
     */
    private $tmp_dir;

    /**
     * @var Array keep original $_SERVER[] values
     */
    private $original_server = array();

    public function setUp()
    {
        $this->globals = array();  // it is too simple to do a $g = $GLOBALS;
        foreach ($GLOBALS as $key => $value) {
            $this->globals[$key] = $value;
        }

        Mock::generate('BaseLanguage');
        Mock::generate('Response');
        Mock::generate('Layout');

        $GLOBALS['Language'] = new MockBaseLanguage();
        $GLOBALS['HTML']     = new MockLayout();
        $GLOBALS['Response'] = $GLOBALS['HTML'];
    }

    protected function setUpGlobalsMockery()
    {
        $GLOBALS['Language'] = \Mockery::spy(BaseLanguage::class);
        $GLOBALS['HTML']     = \Mockery::spy(Layout::class);
        $GLOBALS['Response'] = $GLOBALS['HTML'];
    }

    public function tearDown()
    {
        unset($GLOBALS['Response']);
        unset($GLOBALS['Language']);
        unset($GLOBALS['HTML']);
        if ($this->globals !== null) {
            $GLOBALS = $this->globals;
        }

        $this->removeTmpDir();
        $this->restoreOriginalServer();

        // Sometime, somewhere, you (yes I'm looking at you crappy developer) will
        // include code that set time limit. Later on, tests will start to fail and
        // you will not understand why. You will be like "I don't give a damn, CI server is too slow."
        // of "It's a yet another PHP crappiness".
        // So to avoid a huge shame during daily stand-up we do some cleaning
        // for you.
        set_time_limit(0);

        // Include mocker assertions into SimpleTest results
        if ($container = \Mockery::getContainer()) {
            for ($i = 0; $i < $container->mockery_getExpectationCount(); $i++) {
                $this->pass();
            }
        }
        \Mockery::close();

        parent::tearDown();
    }

    protected function isTest($method)
    {
        if (strtolower(substr($method, 0, 2)) == 'it') {
            return ! is_a($this, strtolower($method));
        }
        return parent::isTest($method);
    }

    public function expectRedirectTo($url)
    {
        $GLOBALS['Response']->expectOnce('redirect', array($url));
    }

    public function expectFeedback($level, $message)
    {
        $GLOBALS['Response']->expectOnce('addFeedback', array($level, $message));
    }

    protected function setText($text, $args)
    {
        $GLOBALS['Language']->setReturnValue('getText', $text, $args);
    }

    protected function assertNotEmpty($string)
    {
        $this->assertTrue(is_string($string));
        return $this->assertNotNull($string) && $this->assertNotEqual($string, '');
    }

    protected function assertArrayNotEmpty($a)
    {
        $this->assertFalse(count($a) == 0, "expected array not to be empty, but it contains 0 elements");
    }

    protected function assertArrayEmpty($a)
    {
        return $this->assertTrue(is_array($a), "expected an array") &&
            $this->assertTrue(empty($a), "expected array to be empty, but it contains " . count($a) . " elements");
    }

    protected function assertNotBlank($string)
    {
        // What about trim() ?
        return $this->assertNotEmpty($string) && $this->assertNoPattern('/^[ ]+$/', $string);
    }

    /**
     * assert that $substring is present $string
     * @param string $string
     * @param string $substring
     * @return bool true if $substring is present in $string
     */
    protected function assertStringContains($string, $substring)
    {
        return $this->assertPattern("/$substring/", $string);
    }

    /**
     * assert that uri has the specified parameters, no matter the possition in the uri
     * @param type $uri
     * @param type $param
     * @param type $value
     */
    protected function assertUriHasArgument($uri, $param, $value)
    {
        $query_string = parse_url($uri, PHP_URL_QUERY);
        parse_str($query_string, $args);
        return $this->assertTrue(isset($args[$param]) && $args[$param] == $value);
    }

    /**
     * asserts that $string starts with the $start_sequence
     * @param type $string
     * @param type $start_sequence
     */
    protected function assertStringBeginsWith($string, $start_sequence)
    {
        return $this->assertPattern("%^$start_sequence%", $string);
    }

    /**
     * Passes if var is inside or equal to either of the two bounds
     *
     * @param type $var
     * @param type $lower_bound
     * @param type $higher_bound
     */
    protected function assertBetweenClosedInterval($var, $lower_bound, $higher_bound)
    {
        $this->assertTrue($var <= $higher_bound, "$var should be lesser than or equal to $higher_bound");
        $this->assertTrue($var >= $lower_bound, "$var should be greater than or equal to $lower_bound");
    }

    /**
     * Asserts that an array has the expected number of items.
     *
     * @param array $array
     * @param int $expected_count
     */
    protected function assertCount($array, $expected_count)
    {
        return $this->assertEqual(count($array), $expected_count);
    }

    protected function assertFileExists($path)
    {
        return $this->assertTrue(is_file($path));
    }

    protected function assertFileDoesntExist($path)
    {
        return $this->assertFalse(is_file($path));
    }


    /**
     * Creates a tmpDir and returns the path (dir automtically deleted in tearDown)
     */
    protected function getTmpDir()
    {
        if (!$this->tmp_dir) {
            clearstatcache();
            do {
                $this->tmp_dir = '/tmp/tuleap_tests_' . rand(0, 10000);
            } while (file_exists($this->tmp_dir));
        }
        if (!is_dir($this->tmp_dir)) {
            mkdir($this->tmp_dir, 0700, true);
        }
        return $this->tmp_dir;
    }

    private function removeTmpDir()
    {
        if ($this->tmp_dir && file_exists($this->tmp_dir)) {
            $this->recurseDeleteInDir($this->tmp_dir);
            rmdir($this->tmp_dir);
        }
        clearstatcache();
    }

    /**
     * Recursive rm function.
     * see: http://us2.php.net/manual/en/function.rmdir.php#87385
     * Note: the function will empty everything in the given directory but won't remove the directory itself
     *
     * @param string $mypath Path to the directory
     *
     * @return void
     */
    protected function recurseDeleteInDir($mypath)
    {
        $mypath = rtrim($mypath, '/');
        $d      = opendir($mypath);
        if (! $d) {
            return;
        }
        while (($file = readdir($d)) !== false) {
            if ($file != "." && $file != "..") {
                $typepath = $mypath . "/" . $file;

                if (is_file($typepath) || is_link($typepath)) {
                    unlink($typepath);
                } else {
                    $this->recurseDeleteInDir($typepath);
                    rmdir($typepath);
                }
            }
        }
        closedir($d);
    }

    protected function setServerValue($variable, $value)
    {
        $this->preserveServer($variable);
        $_SERVER[$variable] = $value;
    }

    protected function preserveServer($variable)
    {
        $this->original_server[$variable] = isset($_SERVER[$variable]) ? $_SERVER[$variable] : null;
        unset($_SERVER[$variable]);
    }

    protected function restoreOriginalServer()
    {
        foreach ($this->original_server as $variable => $value) {
            if ($value === null) {
                unset($_SERVER[$variable]);
            } else {
                $_SERVER[$variable] = $value;
            }
        }
    }
}
