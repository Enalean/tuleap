<?php

require_once(dirname(__FILE__).'/../../../bin/DocmanImport/DateParser.class.php');

class DateParserTest extends TuleapTestCase {

    public function testParseIso8601 () {
        $currentTimeStamp = time();
        $curentIsoDate = date('c', $currentTimeStamp);
        $this->assertEqual($currentTimeStamp, DateParser::parseIso8601($curentIsoDate));
        
        $date1 = "20001201T0154+0100";
        $date2 = "2000-12-01T02:54+0200";
        $date3 = "2000-12-01T00:54:00Z";
        $date4 = "20001201T02:54+0200";
        //$ts = 975632040;
        $ts = gmmktime(0,54,0,12,1,2000);
        
        $this->assertEqual(DateParser::parseIso8601($date1), $ts);
        $this->assertEqual(DateParser::parseIso8601($date2), $ts);
        $this->assertEqual(DateParser::parseIso8601($date3), $ts);
        $this->assertEqual(DateParser::parseIso8601($date4), $ts);
        
        $this->assertEqual(DateParser::parseIso8601($date1), DateParser::parseIso8601($date2));
        $this->assertEqual(DateParser::parseIso8601($date1), DateParser::parseIso8601($date3));
        $this->assertEqual(DateParser::parseIso8601($date1), DateParser::parseIso8601($date4));
        $this->assertEqual(DateParser::parseIso8601($date2), DateParser::parseIso8601($date3));
        $this->assertEqual(DateParser::parseIso8601($date2), DateParser::parseIso8601($date4));
        $this->assertEqual(DateParser::parseIso8601($date3), DateParser::parseIso8601($date4));
    }
}

?>