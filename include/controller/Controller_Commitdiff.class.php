<?php
/**
 * GitPHP Controller Commitdiff
 *
 * Controller for displaying a commitdiff
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

/**
 * Constants for blobdiff modes
 */
define('GITPHP_BLOBDIFF_UNIFIED', 1);
define('GITPHP_BLOBDIFF_SIDEBYSIDE', 2);

/**
 * Constant of the blobdiff mode cookie in the user's browser
 */
define('GITPHP_BLOBDIFF_MODE_COOKIE', 'GitPHPBlobdiffMode');

/**
 * Blobdiff mode cookie lifetime
 */
define('GITPHP_BLOBDIFF_MODE_COOKIE_LIFETIME', 60*60*24*365);           // 1 year

/**
 * Commitdiff controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Commitdiff extends GitPHP_ControllerBase
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
		parent::__construct();
		if (!$this->project) {
			throw new GitPHP_MessageException(__('Project is required'), true);
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
		if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
			return 'commitdiffplain.tpl';
		}
		return 'commitdiff.tpl';
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
		$key = (isset($this->params['hash']) ? $this->params['hash'] : '')
		. '|' . (isset($this->params['hashparent']) ? $this->params['hashparent'] : '')
		. '|' . (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true) ? '1' : '');

		return $key;
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
		if ($local) {
			return __('commitdiff');
		}
		return 'commitdiff';
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
		if (isset($_GET['h']))
			$this->params['hash'] = $_GET['h'];
		if (isset($_GET['hp']))
			$this->params['hashparent'] = $_GET['hp'];

		if (!isset($this->params['plain']) || $this->params['plain'] != true) {

			$mode = GITPHP_BLOBDIFF_UNIFIED;        // default


			/*
			 * Check cookie
			 */
			if (!empty($_COOKIE[GITPHP_BLOBDIFF_MODE_COOKIE])) {
				$mode = $_COOKIE[GITPHP_BLOBDIFF_MODE_COOKIE];
			} else {
				/*
				 * Create cookie to prevent browser delay
				 */
				setcookie(GITPHP_BLOBDIFF_MODE_COOKIE, $mode, time()+GITPHP_BLOBDIFF_MODE_COOKIE_LIFETIME);
			}

			if (isset($_GET['o'])) {
				/*
				 * User is choosing a new mode
				 */
				if ($_GET['o'] == 'sidebyside') {
					$mode = GITPHP_BLOBDIFF_SIDEBYSIDE;
					setcookie(GITPHP_BLOBDIFF_MODE_COOKIE, GITPHP_BLOBDIFF_SIDEBYSIDE, time()+GITPHP_BLOBDIFF_MODE_COOKIE_LIFETIME);
				} else if ($_GET['o'] == 'unified') {
					$mode = GITPHP_BLOBDIFF_UNIFIED;
					setcookie(GITPHP_BLOBDIFF_MODE_COOKIE, GITPHP_BLOBDIFF_UNIFIED, time()+GITPHP_BLOBDIFF_MODE_COOKIE_LIFETIME);
				}
			}

			if ($mode == GITPHP_BLOBDIFF_SIDEBYSIDE) {
				$this->params['sidebyside'] = true;
			}
		}
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
			GitPHP_Log::GetInstance()->SetEnabled(false);

			$this->headers[] = 'Content-type: text/plain; charset=UTF-8';
			$this->headers[] = 'Content-disposition: inline; filename="git-' . $this->params['hash'] . '.patch"';
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
		$co = $this->project->GetCommit($this->params['hash']);
		$this->tpl->assign('commit', $co);

		if (isset($this->params['hashparent'])) {
			$this->tpl->assign("hashparent", $this->params['hashparent']);
		}

		if (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true)) {
			$this->tpl->assign('sidebyside', true);
		}

		$treediff = new GitPHP_TreeDiff($this->project, $this->params['hash'], (isset($this->params['hashparent']) ? $this->params['hashparent'] : ''));
		$this->tpl->assign('treediff', $treediff);
	}

}
