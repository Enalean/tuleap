<?php

require_once(dirname(__FILE__).'/../include/simpletest/reporter.php');
require_once(dirname(__FILE__).'/../include/simpletest/extensions/junit_xml_reporter.php');

// Need to install php code coverage.
// @see: https://github.com/sebastianbergmann/php-code-coverage
@include_once 'PHP/CodeCoverage/Autoload.php';

require_once 'common/TreeNode/InjectPaddingInTreeNodeVisitor.class.php';
require_once 'common/TreeNode/TreeNode.class.php';

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
        parent::__construct();
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

    /**
     *    Paints the test failure with a breadcrumbs
     *    trail of the nesting test suites below the
     *    top level test.
     *    @param string $message    Failure message displayed in
     *                              the context of the other tests.
     *    @access public
     */
    function paintFail($message) {
        ob_end_flush();
        $this->_fails++;
        print "<span class=\"fail\">Fail</span>: ";
        $breadcrumb = $this->getTestListAsTreeNode();
        $breadcrumb->accept($this);
        print '<pre style="clear:both; margin-left:6em;">' . $this->_htmlEntities($message) . '</pre>';
    }
    
    function visit(TreeNode $node) {
        $data = $node->getData();
        echo '<div style="clear:both;">';
        echo $data['tree-padding'] . $data['title'];
        echo '</div>';
        foreach ($node->getChildren() as $child) {
            $child->accept($this);
        }
    }
    
    function getTestListAsTreeNode() {
        $breadcrumb = $this->getTestList();
        array_shift($breadcrumb);
        $root = new TreeNode();
        $parent = $root;
        foreach ($breadcrumb as $b) {
            $node = new TreeNode(array('title' => $b));
            $parent->addChild($node);
            $parent = $node;
        }
        $root->accept(new TreeNode_InjectPaddingInTreeNodeVisitor());
        return $root;
    }
    
    function paintException($exception) {
        parent::paintException($exception);
        echo '<pre>'. $exception->getTraceAsString() .'</pre>';
    }

    /**
     *    Paints a PHP error or exception.
     *    @param string $message        Message to be shown.
     *    @access public
     *    @abstract
     */
    function paintError($message) {
        $this->_exceptions++;
        print "<span class=\"fail\">Exception</span>: ";
        $this->getTestListAsTreeNode()->accept($this);
        print '<pre style="clear:both; margin-left:6em; background:#fcc;">' . $this->_htmlEntities($message) .'</pre>';
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

/**
 * ColorTextReporter
 * Adapted from: http://code.sixapart.com/svn/movabletype/prunings/feature-php5-migration/php/extlib/simpletest/ColorTextReporter.php
 */
class ColorTextReporter extends SimpleReporter {

    /**
     *    Does nothing yet. The first output will
     *    be sent on the first test start.
     *    @access public
     */
    function ColorTextReporter() {
        $this->SimpleReporter();
    }

    /**
     *    Paints the title only.
     *    @param string $test_name        Name class of test.
     *    @access public
     */
    function paintHeader($test_name) {
        if (! SimpleReporter::inCli()) {
            header('Content-type: text/plain');
        }
        print "$test_name\n";
        flush();
    }

    /**
     *    Paints the end of the test with a summary of
     *    the passes and failures.
     *    @param string $test_name        Name class of test.
     *    @access public
     */
    function paintFooter($test_name) {
        if ($this->getFailCount() + $this->getExceptionCount() == 0) {
            print "\n\033[37;1;42m        ALL OK        \033[0m\n";
        } else {
            print "\n\033[37;1;41m        FAILURES!!!        \033[0m\n";
        }
        print "Test cases run: " . $this->getTestCaseProgress() .
                "/" . $this->getTestCaseCount() .
                ", Passes: " . $this->getPassCount() .
                ", Failures: " . $this->getFailCount() .
                ", Exceptions: " . $this->getExceptionCount() . "\n";
    }

    protected $buffer = '';
    
    /**
     *    Paints the test failure as a stack trace.
     *    @param string $message    Failure message displayed in
     *                              the context of the other tests.
     *    @access public
     */
    function paintFail($message) {
        parent::paintFail($message);
        $this->buffer .= "\n\033[1;31m\t" . $this->getFailCount() . ") $message\033[0m\n";
        $breadcrumb = $this->getTestList();
        array_shift($breadcrumb);
        $this->buffer .= "\tin " . implode("\n\tin ", array_reverse($breadcrumb));
        $this->buffer .= "\n";
    }

    /**
     *    Paints a PHP error or exception.
     *    @param string $message        Message to be shown.
     *    @access public
     *    @abstract
     */
    function paintException($exception) {
        parent::paintException($exception);
        $breadcrumb = $this->getTestList();
        array_shift($breadcrumb);
        error_log("Exception: \n\033[1;31m\t" . $this->getExceptionCount() . ") ". $exception->getMessage() ."\033[0m\n". "\tin " . substr($exception->getTraceAsString(), 0, strpos($exception->getTraceAsString(), "\n")) ."\n");
    }

    /**
     *    Paints formatted text such as dumped variables.
     *    @param string $message        Text to show.
     *    @access public
     */
    function paintFormattedMessage($message) {
        print "$message\n";
        flush();
    }

    function paintMethodStart($test_name)
    {
        //print "Start {$test_name} Test\n";
        $this->buffer = '';
        $this->before_fails = $this->_fails;
    }

    var $before_fails = 0;

    function paintMethodEnd($test_name)
    {
        //print "End {$test_name} Test\n";
        if ($this->before_fails != $this->_fails) {
            if (!$this->buffer_case_displayed) {
                echo $this->buffer_case;
                $this->buffer_case_displayed = true;
            }
            print "  |--- {$test_name} - \033[1;31mKO\033[0m";
            print $this->buffer;
            print "\n";
        } else {
            //print " - \033[1;32mOK\033[0m";
            //print "\n";
        }
    }

    protected $buffer_case           = '';
    protected $buffer_case_displayed = false;
    function paintCaseStart($test_name)
    {
        $this->buffer_case           = "\n {$test_name}\n";
        $this->buffer_case_displayed = false;
        return parent::paintCaseStart($test_name);
    }
}

class CodendiReporterFactory {

    public static function getCodeCoverage($enableCoverage = false) {
        if ($enableCoverage && class_exists('PHP_CodeCoverage')) {
            $filter = new PHP_CodeCoverage_Filter();
            $filter->addDirectoryToBlacklist($GLOBALS['codendi_dir'].'/tools');
            $filter->addDirectoryToBlacklist($GLOBALS['codendi_dir'].'/tools', '.inc');
            $filter->addDirectoryToBlacklist($GLOBALS['codendi_dir'].'/tools', '.txt');
            $filter->addDirectoryToBlacklist($GLOBALS['codendi_dir'].'/tools', '.dist');
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
                return new ColorTextReporter();
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