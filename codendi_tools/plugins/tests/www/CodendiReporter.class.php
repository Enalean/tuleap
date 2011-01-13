<?php

require_once('../include/simpletest/reporter.php');
require_once('../include/simpletest/extensions/junit_xml_reporter.php');

require_once 'PHP/CodeCoverage.php';

class CodeCoverageInvokerDecorator extends SimpleInvokerDecorator {
        protected $coverage;

        function __construct($coverage, $invoker) {
            SimpleInvokerDecorator::SimpleInvokerDecorator($invoker);
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
            $this->coverage->start($method);
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

class CodendiHtmlReporter extends HtmlReporter {
    protected $_timer;
    protected $coverage;

    function __construct($coverage) {
        HtmlReporter::HtmlReporter();
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

    /**
     *    Can wrap the invoker in preperation for running
     *    a test.
     *    @param SimpleInvoker $invoker   Individual test runner.
     *    @return SimpleInvoker           Wrapped test runner.
     *    @access public
     */
    function createInvoker($invoker) {
        return new CodeCoverageInvokerDecorator($this->coverage, $invoker);
    }

    function getCodeCoverage() {
        return $this->coverage;
    }

}
 
class CodendiJUnitXMLReporter extends JUnitXMLReporter {
        
    public function getXML() {
        return $this->doc->saveXML();
    }
    
    public function writeXML($filename) {
        $fh = fopen($filename, 'w');
        fwrite($fh, $this->getXML());
        fclose($fh);        
    }
        
}

class CodendiReporterFactory {
    public static function reporter($type = "html") {
        $coverage = new PHP_CodeCoverage();
        switch ($type) {
            case "text":
                return new TextReporter();
                break;
            case "junit_xml":
                return new CodendiJUnitXMLReporter();
                break;
            default:
                return new CodendiHtmlReporter($coverage);
        }
    }
}
?>