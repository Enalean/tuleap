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
            $this->addFile($dir);
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
                new FilterDirectoryLikeASir(
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

class FilterTestCase extends FilterIterator {
    public function accept() {
        $file = $this->getInnerIterator()->current();
        if ($this->fileCanBeSelectedIntoTestSuite($file)) {
            return true;
        }
        return false;
    }

    private function fileCanBeSelectedIntoTestSuite($file) {
        return (strpos($file->getPathname(), '/_') === false &&
               $this->isNotATestsRestDirectory($file->getPathname()) &&
               $this->isNotATestsSoapDirectory($file->getPathname()) &&
               (preg_match('/Test.php$/', $file->getFilename()))
        );
    }

    private function isNotATestsRestDirectory($pathName) {
        return !(preg_match("/^.*\/tests\/rest(\/.+|$)$/", $pathName));
    }

    private function isNotATestsSoapDirectory($pathName) {
        return !(preg_match("/^.*\/tests\/soap(\/.+|$)$/", $pathName));
    }
}

class FilterDirectoryLikeASir extends RecursiveFilterIterator {
    public function accept() {
        $file = $this->getInnerIterator()->current();
        if ($this->isForbiddenForSimpleTest($file)) {
            return false;
        }
        return true;
    }

    private function isForbiddenForSimpleTest($file) {
        return file_exists($file->getPathname() . '/.simpletest_skip');
    }
}
