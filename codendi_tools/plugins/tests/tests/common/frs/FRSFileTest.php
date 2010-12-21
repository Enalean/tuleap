<?php

require_once('common/frs/FRSFile.class.php');

class FRSFileTest extends UnitTestCase {

    function __construct($name = 'FRSFile test') {
        parent::__construct($name);
    }

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
/*
    function testWithBigFile() {
        $path    = realpath(dirname(__FILE__).'/../include/_fixtures/big_file');
        $newPath = realpath(dirname(__FILE__).'/_fixtures/big_file2');

        $this->assertTrue(is_writeable($newPath), "$newPath should be writable");
        $writeFile = fopen(PHP_BigFile::stream($newPath), 'wb');
        $this->assertTrue($writeFile);
        
        $file = new FRSFile();
        $file->file_location = $path;

        $fileSize  = PHP_BigFile::getSize($path);
        $chunkSize = 8*1024*1024;
        $nbChunks  = ceil($fileSize / $chunkSize);
        for ($i = 0; $i < $nbChunks; $i++) {
            $data    = $file->getContent($i * $chunkSize, $chunkSize);
            $written = fwrite($writeFile, $data);
            $this->assertEqual(strlen($data), $written);
        }
        $this->assertIdentical(md5_file($path), md5_file($newPath));
    }
*/
}


?>