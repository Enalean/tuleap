<?php
require_once 'SetTestEnvironmentParameter.php';
require_once 'PHPUnit/Framework.php';
require_once 'sys/Logger.php';

class LoggerTest extends PHPUnit_Framework_TestCase {

    private $logger;

    public function setUp(){
        global $configArray;
        $configArray = array("Logging" => array("file" => "/Users/franckborel/NetBeansProjects/vufind/branches/franck/web/tests/log/messages.log:emerge-3,alert-3,error-1,notice-1,debug-1"));
        $this->logger = new Logger();
    }

    public function testLogger(){
        $this->assertNotNull($this->logger);
    }

    public function testLogToFile(){
        $this->logger->log(array(1 => 'Emerge', 2 => 'Emerge more!', 3 => 'Emerge even more'), PEAR_LOG_EMERG);
        $this->logger->log(array(1 => 'Alert', 2 => 'Alert more!', 3 => 'Alert even more'), PEAR_LOG_ALERT);
        $this->logger->log('Critical', PEAR_LOG_CRIT);
        $this->logger->log('Error', PEAR_LOG_ERR);
        $this->logger->log('Warning', PEAR_LOG_WARNING);
        $this->logger->log('Notice', PEAR_LOG_NOTICE);
        $this->logger->log('Info', PEAR_LOG_INFO);
        $this->logger->log('Debug', PEAR_LOG_DEBUG);
    }
}
?>
