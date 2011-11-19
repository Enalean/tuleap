<?php

require_once('../include/simpletest/reporter.php');
require_once('../include/simpletest/extensions/junit_xml_reporter.php');

// Need to install php code coverage. 
// @see: https://github.com/sebastianbergmann/php-code-coverage
@include_once 'PHP/CodeCoverage/Autoload.php';

/**
 * Invoker decorator to target code coverage only on executed tests
 *
 * 
 */
class CodeCoverageInvokerDecorator extends SimpleInvokerDecorator {
        protected $coverage;

        function __construct($coverage, $invoker) {
            $this->SimpleInvokerDecorator($invoker);
            $this->coverage = $coverage;
        }

        /**
         *    Runs test level set up. Used for changing
         *    the mechanics of base test cases.
         *    @param string $method    Test method to call.
         *    @access public
         */
        function before($method) {
            $this->_invoker->before($method);
            $this->coverage->start($method.'('.$this->_invoker->getTestCase()->getLabel().')');
        }

        /**
         *    Runs test level clean up. Used for changing
         *    the mechanics of base test cases.
         *    @param string $method    Test method to call.
         *    @access public
         */
        function after($method) {
            $this->coverage->stop();
            $this->_invoker->after($method);
        }

}

interface iCodeCoverageReporter {
    /**
     *    Can wrap the invoker in preperation for running
     *    a test.
     *    @param SimpleInvoker $invoker   Individual test runner.
     *    @return SimpleInvoker           Wrapped test runner.
     *    @access public
     */
    public function createInvoker($invoker);

    public function generateCoverage($path);
}


class CodendiHtmlReporter extends HtmlReporter implements iCodeCoverageReporter {
    protected $_timer;
    protected $coverage;

    function __construct($coverage) {
        $this->HtmlReporter();
        $this->coverage = $coverage;
    }

    function paintHeader($test_name) {
        print "<h1>$test_name</h1>\n";
        $this->_timer = microtime(true);
        flush();
    }
    function paintFooter($test_name) {
        $duration = microtime(true) - $this->_timer;
        $micro = round($duration - floor($duration), 2);
        $seconds = floor($duration);
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        $d = $minutes ? $minutes .' minute' .($minutes > 1 ? 's ' : ' ') : '';
        $d .= ($seconds + $micro) .' seconds';
        echo '<div style="border:1px solid orange; background: lightyellow; color:orange">Time taken: '. $d .'</div>'; 
        parent::paintFooter($test_name);
    }
    function paintPass($message) {
        parent::paintPass($message);
        if (isset($_REQUEST['show_pass'])) {
            print '<span class="pass">Pass</span>: ';
            $breadcrumb = $this->getTestList();
            array_shift($breadcrumb);
            print implode(" -&gt; ", $breadcrumb);
            print " -&gt; " . $this->_htmlEntities($message) . "<br />\n";
        }
    }
    function paintFail($message) {
        echo '<p><input type="checkbox" onclick="$(this).siblings().invoke(\'toggle\');" /><span>';
        parent::paintFail($message);
        echo '</span></p>';
    }

    function createInvoker($invoker) {
        if ($this->coverage) {
            return new CodeCoverageInvokerDecorator($this->coverage, $invoker);
        }
        return $invoker;
    }

    public function generateCoverage($path) {
        if ($this->coverage) {
            $writer = new PHP_CodeCoverage_Report_HTML();
            $writer->process($this->coverage, $path);
            return true;
        }
        return false;
    }

}
 
class CodendiJUnitXMLReporter extends JUnitXMLReporter implements iCodeCoverageReporter {
    protected $coverage;

    function __construct($coverage) {
        $this->JUnitXMLReporter();
        $this->coverage = $coverage;
    }

    public function getXML() {
        return $this->doc->saveXML();
    }
    
    public function writeXML($filename) {
        $fh = fopen($filename, 'w');
        fwrite($fh, $this->getXML());
        fclose($fh);        
    }

    function createInvoker($invoker) {
        if ($this->coverage) {
            return new CodeCoverageInvokerDecorator($this->coverage, $invoker);
        }
        return $invoker;
    }

    public function generateCoverage($path) {
        if ($this->coverage) {
            $writer = new PHP_CodeCoverage_Report_Clover();
            $writer->process($this->coverage, $path);

            $writer = new PHP_CodeCoverage_Report_HTML();
            $writer->process($this->coverage, dirname(__FILE__).'/code-coverage-report');
            return true;
        }
        return false;
    }
}

class CodendiReporterFactory {

    public static function getCodeCoverage($enableCoverage = false) {
        if ($enableCoverage && class_exists('PHP_CodeCoverage')) {
            $filter = new PHP_CodeCoverage_Filter();
            $filter->addDirectoryToBlacklist($GLOBALS['codendi_dir'].'/codendi_tools');
            $filter->addDirectoryToBlacklist($GLOBALS['codendi_dir'].'/codendi_tools', '.inc');
            $filter->addDirectoryToBlacklist($GLOBALS['codendi_dir'].'/codendi_tools', '.txt');
            $filter->addDirectoryToBlacklist($GLOBALS['codendi_dir'].'/codendi_tools', '.dist');
            $filter->addDirectoryToBlacklist($GLOBALS['htmlpurifier_dir']);
            $filter->addDirectoryToBlacklist($GLOBALS['jpgraph_dir']);
            $filter->addDirectoryToBlacklist($GLOBALS['codendi_dir'].'/src/www');
            return new PHP_CodeCoverage(null, $filter);
        }
        return null;
    }

    public static function reporter($type = "html", $enableCoverage = false) {
        $coverage = self::getCodeCoverage($enableCoverage);
        switch ($type) {
            case "text":
                return new TextReporter();
                break;
            case "junit_xml":
                return new CodendiJUnitXMLReporter($coverage);
                break;
            default:
                return new CodendiHtmlReporter($coverage);
        }
    }
}
?>