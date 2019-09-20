<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 *
 * abstract
 */
class SanitizerTestCase extends TuleapTestCase
{
    function testSanitize()
    {
        trigger_error("testSanitize() not yet implemented");
    }
}

//We just tells SimpleTest to always ignore this testcase
SimpleTest::ignore('SanitizerTestCase');
