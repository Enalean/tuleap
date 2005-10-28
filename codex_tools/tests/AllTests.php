<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * This script is intented to allow CodeX developers to do some unit tests.
 *
 * $Id$
 */

//We want to be able to run one test AND many tests
define('CODEX_RUNNER', true);

require_once('tests/simpletest/unit_tester.php');
require_once('tests/CodexReporter.class');	
require(getenv('SF_LOCAL_INC_PREFIX').'/etc/codex/conf/local.inc');

//We define a group of test
$alltest = &new GroupTest('All CodeX tests');

//To add tests, you have to :
//     1. Create file base on the template : $ cp TemplateTest.php path/to/your/tests/MyNewTest.php
//     2. Modifie and ajust MyNewTest to fill your requirements
//     3. Add the test MyNewTest to $alltest in this file : $alltest->addTestFile('path/to/your/tests/MyNewTest.php');
//     4. That's all. You can run your tests individually or by groups
// Don't forget the documentation of SimpleTest : http://www.lastcraft.com/simple_test.php
$alltest->addTestFile('TemplateTest.php');


// {{{ Include
$alltest->addTestFile('common/include/AllTests.php');
// }}}

// {{{ Mail
$alltest->addTestFile('common/mail/MailTest.php');
// }}}

// {{{ Dao
$alltest->addTestFile('common/dao/AllTests.php');
// }}}

// {{{ Codex Collection Framework
$alltest->addTestFile('common/collection/AllTests.php');
// }}}

// {{{ Codex Event Framework
$alltest->addTestFile('common/event/AllTests.php');
// }}}

// {{{ Codex Plugin Framework
$alltest->addTestFile('common/plugin/AllTests.php');
// }}}

//We run the tests
$alltest->run(new CodexReporter());

?>
