#! /usr/local/bin/php -Cq
<?php
/* Copyright (C) 2004, Dan Frankowski <dfrankow@cs.umn.edu>
 *
 * This file is part of PhpWiki.
 * 
 * PhpWiki is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * PhpWiki is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with PhpWiki; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Unit tests for PhpWiki. 
 *
 * You must have PEAR's PHPUnit package <http://pear.php.net/package/PHPUnit>. 
 * These tests are unrelated to test/maketest.pl, which do not use PHPUnit.
 */

####################################################################
#
# Preamble needed to get the tests to run.
#
####################################################################


# Add root dir to the path
$rootdir = getcwd() . '/../../';
ini_set('include_path', ini_get('include_path') . (substr(PHP_OS,0,3) == 'WIN' ? ';' : ':') . $rootdir);

# This quiets a warning in config.php
$HTTP_SERVER_VARS['REMOTE_ADDR'] = '127.0.0.1';

# Other needed files
require_once 'index.php';
require_once 'lib/stdlib.php';
require_once 'lib/config.php';  // Needed for $WikiNameRegExp

# Show lots of detail when an assert() in the code fails
function assert_callback( $script, $line, $message ) {
   echo "assert failed: script ", $script," line ", $line," :";
   echo "$message";
   echo "Traceback:";
   print_r(debug_backtrace());
   exit;
}
$foo = assert_options( ASSERT_CALLBACK, 'assert_callback');

# This is the test DB backend
#require_once( 'lib/WikiDB/backend/cvs.php' );
$db_params                         = array();
$db_params['directory']            = getcwd() . '/testbox';
$db_params['dbtype']               = 'file';

# Mock objects to allow tests to run
require_once( 'lib/WikiDB.php' );
class MockRequest {
    function MockRequest(&$dbparams) {
        $this->_dbi = WikiDB::open(&$dbparams);
    }
    function addArg($arg, $value) {
        $this->args[$arg] = $value;
    }
    function getArg($arg) {
        return $this->args[$arg];
    }

    function getDbh() {
        return $this->_dbi;
    }
}

$request = new MockRequest($db_params);

####################################################################
#
# End of preamble, run the test suite ..
#
####################################################################

# Test files
require_once ('PHPUnit.php');
# lib/config.php might do a cwd()
require_once (dirname(__FILE__).'/lib/InlineParserTest.php');

print "Run tests ..\n";
$suite  = new PHPUnit_TestSuite("InlineParserTest");
$result = PHPUnit::run($suite);

echo $result -> toString();

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>
