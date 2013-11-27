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
}

class TuleapTestCollector extends SimpleCollector {

    public function collect($testSuite, $path) {
        $rii = new FilterTestCase(new RecursiveIteratorIterator(new RecursiveCachingIterator(new RecursiveDirectoryIterator($path)),
               RecursiveIteratorIterator::SELF_FIRST));
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
               (preg_match('/Test.php$/', $file->getFilename()) || ($this->phpVersionIsGreaterOrEqualThanPhp53() && preg_match('/TestPHP53.php$/', $file->getFilename())))
        );
    }

    private function phpVersionIsGreaterOrEqualThanPhp53() {
        return version_compare(phpversion(), '5.3', '>=');
    }
}

?>
