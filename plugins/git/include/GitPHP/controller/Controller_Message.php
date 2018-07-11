<?php


namespace Tuleap\Git\GitPHP;

/**
 * GitPHP Controller Message
 *
 * Controller for displaying a message page
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
/**
 * Message controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class Controller_Message extends ControllerBase // @codingStandardsIgnoreLine
{
    /**
     * __construct
     *
     * Constructor
     *
     * @access public
     * @return controller
     */
    public function __construct()
    {
        try {
            parent::__construct();
        } catch (\Exception $e) {
        }
    }

    /**
     * GetTemplate
     *
     * Gets the template for this controller
     *
     * @access protected
     * @return string template filename
     */
    protected function GetTemplate() // @codingStandardsIgnoreLine
    {
        return 'message.tpl';
    }

    /**
     * GetName
     *
     * Gets the name of this controller's action
     *
     * @access public
     * @param boolean $local true if caller wants the localized action name
     * @return string action name
     */
    public function GetName($local = false) // @codingStandardsIgnoreLine
    {
        // This isn't a real controller
        return '';
    }

    /**
     * ReadQuery
     *
     * Read query into parameters
     *
     * @access protected
     */
    protected function ReadQuery() // @codingStandardsIgnoreLine
    {
    }

    /**
     * LoadHeaders
     *
     * Loads headers for this template
     *
     * @access protected
     */
    protected function LoadHeaders() // @codingStandardsIgnoreLine
    {
        if (isset($this->params['statuscode']) && !empty($this->params['statuscode'])) {
            $partialHeader = $this->StatusCodeHeader($this->params['statuscode']);
            if (!empty($partialHeader)) {
                if (substr(php_sapi_name(), 0, 8) == 'cgi-fcgi') {
                    /*
                     * FastCGI requires a different header
                     */
                    $this->headers[] = 'Status: ' . $partialHeader;
                } else {
                    $this->headers[] = 'HTTP/1.1 ' . $partialHeader;
                }
            }
        }
    }

    /**
     * LoadData
     *
     * Loads data for this template
     *
     * @access protected
     */
    protected function LoadData() // @codingStandardsIgnoreLine
    {
        $this->tpl->assign('message', $this->params['message']);
        if (isset($this->params['error']) && ($this->params['error'])) {
            $this->tpl->assign('error', true);
        }
    }

    /**
     * StatusCodeHeader
     *
     * Gets the header for an HTTP status code
     *
     * @access private
     * @param integer $code status code
     * @return string header
     */
    private function StatusCodeHeader($code) // @codingStandardsIgnoreLine
    {
        switch ($code) {
            case 500:
                return '500 Internal Server Error';
        }
    }
}
