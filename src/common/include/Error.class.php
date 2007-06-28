<?php   

/**
 * A very base error class.
 *
 * Provides a basic uniform API for setting and testing error conditions and
 * error messages.
 *
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright 1999-2001 (c) VA Linux Systems
 * http://sourceforge.net
 *
 * @author Tim Perdue <tperdue@valnux.com>
 * @date 2000-08-28
 *
 */

class Error {
	/**
	 * The current error state.
	 *
	 * @var bool $error_state.
	 */
	var $error_state;

	/**
	 * The current error message(s).
	 *
	 * @var string $error_message.
	 */
	var $error_message;

	/**
	 * Error() - Constructor.
	 * Constructor for the Error class.
	 * Sets the error state to false.
	 *
	 */
	function Error() {
		//nothing
		$this->error_state=false;
	}

	/**
	 * setError() - Sets the error string.
	 * Set the error string $error_message to the value of $string
	 # and enable the $error_state flag.
	 *
	 * @param	string  The error string to set.
	 *
	 */
	function setError($string) {
		$this->error_state=true;
		$this->error_message=$string;
	}

	/**
	 * clearError() - Clear the current error.
	 * Clear the current error string and disable the $error_state flag.
	 *
	 */
	function clearError() {
		$this->error_state=false;
		$this->error_message='';
	}

	/**
	 * getErrorMessage() - Retrieve the error message string.
	 * Returns the value of $error_message.
	 *
	 * @return    $error_message The current error message string.
	 *
	 */
	function getErrorMessage() {
		global $Language;
		if ($this->error_state)	{
			return $this->error_message;
		} else {
			return $Language->getText('include_common_error','no_err');
		}
	}

	/**
	 * isError() - Determines the current error state.
	 * This function returns the current value of $error_state.
	 *
	 * @return    $error_state     The boolean error status.
	 *
	 */
	function isError() {
		return $this->error_state;
	}

}

?>
