<?php
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
class GitPHP_Controller_Message extends GitPHP_ControllerBase
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
		} catch (Exception $e) {
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
	protected function GetTemplate()
	{
		return 'message.tpl';
	}

	/**
	 * GetCacheKey
	 *
	 * Gets the cache key for this controller
	 *
	 * @access protected
	 * @return string cache key
	 */
	protected function GetCacheKey()
	{
		return sha1($this->params['message']) . '|' . ($this->params['error'] ? '1' : '0');;
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
	public function GetName($local = false)
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
	protected function ReadQuery()
	{
	}

	/**
	 * LoadHeaders
	 *
	 * Loads headers for this template
	 *
	 * @access protected
	 */
	protected function LoadHeaders()
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
	protected function LoadData()
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
	private function StatusCodeHeader($code)
	{
		switch ($code) {
			case 500:
				return '500 Internal Server Error';
		}
	}

}
