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
            if ($dir === 'php7compatibletests.list') {
                $this->addTestFilesFromFile(__DIR__.'/../../../tests/php7compatibletests.list');
                $directory_iterator = new DirectoryIterator(__DIR__.'/../../');
                foreach ($directory_iterator as $directory) {
                    if ($directory->isDot()) {
                        continue;
                    }
                    $this->addTestFilesFromFile($directory->getPathname().'/tests/php7compatibletests.list');
                }
            } else {
                $this->addFile($dir);
            }
        }
    }

    private function addTestFilesFromFile($filepath)
    {
        if (is_file($filepath)) {
            foreach (file($filepath) as $file) {
                $this->addFile(trim($file));
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
