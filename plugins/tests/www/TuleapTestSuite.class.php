<?php

class TuleapTestSuite extends TestSuite {

    function __construct($dir) {
        parent::__construct();
        if (is_array($dir)) {
            foreach ($dir as $element) {
                $this->append($element);
            }
        } else {
            $this->append($dir);
        }
    }
    
    function append($dir) {
        if (is_dir($dir)) {
            $this->collect($dir, new TuleapTestCollector());
        } else {
            if (substr($dir, -8) === 'Test.php') {
                $this->addFile($dir);
            } else {
                foreach (file($dir) as $file) {
                    $this->addFile(trim($file));
                }
            }
        }
    }

    public function randomize()
    {
        shuffle($this->_test_cases);
    }
}

class TuleapTestCollector extends SimpleCollector {

    public function collect($testSuite, $path) {
        $rii = new FilterTestCase(
            new RecursiveIteratorIterator(
                new FilterTestDirectory(
                    new RecursiveDirectoryIterator($path)
                ),
                RecursiveIteratorIterator::SELF_FIRST
            )
        );
        foreach ($rii as $file) {
            $this->_handle($testSuite, $file->getPathname());
        }
    }
}
