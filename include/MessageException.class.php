<?php
/**
 * GitPHP Message exception
 *
 * Custom exception for signalling display of a message to user
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 */

/**
 * Message Exception
 *
 * @package GitPHP
 */
class GitPHP_MessageException extends Exception
{

	public $Error;

	public $StatusCode;
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @param string $message message string
	 * @param boolean $error true if this is an error rather than informational
	 * @param integer $statusCode HTTP status code to return
	 * @param integer $code exception code
	 * @param Exception $previous previous exception
	 * @return Exception message exception object
	 */
	public function __construct($message, $error = false, $statusCode = 200, $code = 0) {
		$this->Error = $error;
		$this->StatusCode = $statusCode;
		parent::__construct($message, $code);
	}
}
