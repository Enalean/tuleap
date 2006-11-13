<?php
rcs_id('$Id$');

/* Copyright (C) 2004, Dan Frankowski <dfrankow@cs.umn.edu>
 */

require_once 'lib/InlineParser.php';
require_once 'PHPUnit.php';

class InlineParserTest extends PHPUnit_TestCase {

    // constructor of the test suite
    function StringTest($name) {
       $this->PHPUnit_TestCase($name);
    }

    function testNoWikiWords() {
        $str1 = 'This has no wiki words, and is all text.';
        $xmlc1 = TransformInline($str1);
        $this->assertTrue(isa($xmlc1, 'XmlContent'));
        $c1 = $xmlc1->getContent();
        $this->assertEquals(1, count($c1)); 
        $this->assertEquals($str1, $c1[0]); 
    }

    function testWikiWord() {
        $ww = 'WikiWord';
        $str1 = "This has 1 $ww.";
        $xml = TransformInline($str1);
        $this->assertTrue(isa($xml, 'XmlContent'));
        $c1 = $xml->getContent();
        $this->assertEquals(3, count($c1));
        $this->assertTrue(isa($c1[1], 'Cached_WikiLink')); 

        $this->assertEquals('This has 1 ', $c1[0]); 
        $this->assertEquals($ww, $c1[1]->asString()); 
        $this->assertEquals('.', $c1[2]); 
    }
    
    // todo...
    function testLinks() {
        $tests = array("[label|link]",
                       "[ label | link.jpg ]",
                       "[ image.jpg | link ]",
                       "[ Upload:image.jpg | link ]",
                       "[ http://server/image.jpg | link ]",
                       "[ label | http://server/link ]",
                       "[ label | Upload:link ]",
                       "[ label | phpwiki:action=link ]",
                       "Upload:image.jpg",
                       "http://server/image.jpg",
                       "http://server/link",
                       "[http:/server/~name/]",
                       "http:/server/~name/"
                       );
        for ($i=0; $i < count($tests); $i++) {
            $xml = TransformInline($tests[$i]);
            $this->assertTrue(isa($xml, 'XmlContent'));
            $cl = $xml->getContent();
            $this->assertEquals(1, count($c1));
            $this->assertTrue(isa($c1[0], 'Cached_WikiLink')); 
        }
    }
    
}


// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:   
?>
