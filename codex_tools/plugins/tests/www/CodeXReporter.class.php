<?php

require_once('../include/simpletest/reporter.php');


class CodeXHtmlReporter extends HtmlReporter {
    protected $_timer;
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
}

class CodeXReporterFactory {
    /* public static */ function reporter($type = "html") {
        return $type == "text" ? new TextReporter() : new CodeXHtmlReporter();
    }
}
?>