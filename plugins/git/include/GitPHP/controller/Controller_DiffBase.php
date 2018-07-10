<?php

namespace Tuleap\Git\GitPHP;

/**
 * GitPHP Controller DiffBase
 *
 * Base controller for diff-type views
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2011 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
/**
 * DiffBase controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
abstract class Controller_DiffBase extends ControllerBase
{
    const DIFF_UNIFIED    = 1;
    const DIFF_SIDEBYSIDE = 2;

	/**
	 * ReadQuery
	 *
	 * Read query into parameters
	 *
	 * @access protected
	 */
	protected function ReadQuery()
	{
		if (!isset($this->params['plain']) || $this->params['plain'] != true) {

			if ($this->DiffMode(isset($_GET['o']) ? $_GET['o'] : '') == self::DIFF_SIDEBYSIDE) {
				$this->params['sidebyside'] = true;
			}

		}
	}

	/**
	 * DiffMode
	 *
	 * Determines the diff mode to use
	 *
	 * @param string $overrideMode mode overridden by the user
	 * @access protected
	 */
	protected function DiffMode($overrideMode = '')
	{
		$mode = self::DIFF_UNIFIED;	// default

		if (!empty($overrideMode)) {
			/*
			 * User is choosing a new mode
			 */
			if ($overrideMode == 'sidebyside') {
				$mode = self::DIFF_SIDEBYSIDE;
			} else if ($overrideMode == 'unified') {
				$mode = self::DIFF_UNIFIED;
			}
		}

		return $mode;
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
		if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
			$this->headers[] = 'Content-type: text/plain; charset=UTF-8';
		}
	}

}