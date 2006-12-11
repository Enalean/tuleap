<?php

require_once('../include/simpletest/reporter.php');


class CodeXHtmlReporter extends HtmlReporter {
    function paintHeader($test_name) {
        print "<h1>$test_name</h1>\n";
        flush();
    }
    function paintFooter($test_name) {
        $colour = ($this->getFailCount() + $this->getExceptionCount() > 0 ? "red" : "green");
        print "<div style=\"";
        print "padding: 8px; margin-top: 1em; background-color: $colour; color: white;";
        print "\">";
        print $this->getTestCaseProgress() . "/" . $this->getTestCaseCount();
        print " test cases complete:\n";
        print "<strong>" . $this->getPassCount() . "</strong> passes, ";
        print "<strong>" . $this->getFailCount() . "</strong> fails and ";
        print "<strong>" . $this->getExceptionCount() . "</strong> exceptions.";
        print "</div>\n";
    }
    function paintPass($message) {
        parent::paintPass($message);
        if (isset($_REQUEST['show_pass'])) {
            print "<span class=\"pass\">Pass</span>: ";
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