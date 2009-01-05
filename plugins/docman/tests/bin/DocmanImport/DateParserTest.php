<?php

require_once(dirname(__FILE__).'/../../../bin/DocmanImport/DateParser.class.php');

class DateParserTest extends UnitTestCase {
    
    function DateParserTest($name = 'DateParser test') {
        $this->UnitTestCase($name);
    }

    public function testParseIso8601 () {
        $currentTimeStamp = time();
        $curentIsoDate = date('c', $currentTimeStamp);
        $this->assertEqual($currentTimeStamp, DateParser::parseIso8601($curentIsoDate));
        
        $date1 = "20001201T0154+01";
        $date2 = "2000-12-01T02:54+0200";
        $date3 = "2000-12-01T00:54:00Z";
        $ts = 975632040;
        $this->assertEqual(DateParser::parseIso8601($date1), $ts);
        $this->assertEqual(DateParser::parseIso8601($date2), $ts);
        $this->assertEqual(DateParser::parseIso8601($date3), $ts);
    }
}

?>