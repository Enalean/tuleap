<?php
/**
 * Codendi Command-line Interface
 *
 * Portion of this file is inspired from the  GForge Command-line Interface
 * contained in GForge.
 * Copyright 2005 GForge, LLC
 * http://gforge.org/
 *
 */

/**
 * Log - Class that allows logging of actions
 */
class Log {
	var $level;

	/**
	 * Log - Constructor
	 */
	function __construct() {
		$this->level = 0;		// By default, don't log
	}

	/**
	 * setLevel - Set the level of logging
	 *
	 * So far only 2 values are accepted: 0 (no logging) and 1 (log to console)
	 */
	function setLevel($level) {
		$this->level = $level;
	}

	/**
	 * add - Add some text to the log
	 *
	 * @parameter	string Text to log
	 */
	function add($text) {
		if ($this->level) {
			echo $text."\n";
		}
	}
}
