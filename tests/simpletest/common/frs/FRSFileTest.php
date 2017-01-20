<?php

require_once('common/frs/FRSFile.class.php');

class FRSFileTest extends TuleapTestCase {

    function testGetContentWholeFile() {
        $file = new FRSFile();
        $file->file_location = dirname(__FILE__).'/_fixtures/file_sample';
        $file->file_size     = filesize(dirname(__FILE__).'/_fixtures/file_sample');

        $this->assertIdentical(file_get_contents(dirname(__FILE__).'/_fixtures/file_sample'), $file->getContent());
    }

    function testGetContentWithStartOffset() {
        $file = new FRSFile();
        $file->file_location = dirname(__FILE__).'/_fixtures/file_sample';

        $this->assertIdentical('"The quick', $file->getContent(0, 10));
    }

    function testGetContentWithOffsetAndSize() {
        $file = new FRSFile();
        $file->file_location = dirname(__FILE__).'/_fixtures/file_sample';

        $this->assertIdentical(' brown fox', $file->getContent(10, 10));
    }

    function testGetContentWithOffsetAndEof() {
        $file = new FRSFile();
        $file->file_location = dirname(__FILE__).'/_fixtures/file_sample';

        $this->assertIdentical("arts.\n", $file->getContent(380, 10));
    }

    function testGetContentWholeByOffset() {
        $file = new FRSFile();
        $file->file_location = dirname(__FILE__).'/_fixtures/file_sample';

        $content  = $file->getContent(0, 100);
        $content .= $file->getContent(100, 100);
        $content .= $file->getContent(200, 100);
        $content .= $file->getContent(300, 100);
        $this->assertIdentical(file_get_contents(dirname(__FILE__).'/_fixtures/file_sample'), $content);
    }

    function testGetfilePath() {
        $file = new FRSFile();
        $filepath = 'path';
        $file->setFilePath($filepath);
        $filename = 'name';
        $file->setFileName($filename);
        $this->assertequal($filepath, $file->getFilePath());

        $file->setFilePath(null);
        $this->assertequal($filename, $file->getFilePath());
    }
}


?>