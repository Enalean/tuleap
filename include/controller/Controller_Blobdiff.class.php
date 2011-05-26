<?php
/**
 * GitPHP Controller Blobdiff
 *
 * Controller for displaying a blobdiff
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
define('GITPHP_BLOBDIFF_MODE_COOKIE_LIFETIME', 60*60*24*365);		// 1 year

/**
 * Blobdiff controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Blobdiff extends GitPHP_ControllerBase
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
			return 'blobdiffplain.tpl';
		}
		return 'blobdiff.tpl';
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
		return (isset($this->params['hashbase']) ? $this->params['hashbase'] : '') . '|' . (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['hashparent']) ? $this->params['hashparent'] : '') . '|' . (isset($this->params['file']) ? sha1($this->params['file']) : '') . '|' . (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true) ? '1' : '');
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
			return __('blobdiff');
		}
		return 'blobdiff';
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
		if (isset($_GET['f']))
			$this->params['file'] = $_GET['f'];
		if (isset($_GET['h']))
			$this->params['hash'] = $_GET['h'];
		if (isset($_GET['hb']))
			$this->params['hashbase'] = $_GET['hb'];
		if (isset($_GET['hp']))
			$this->params['hashparent'] = $_GET['hp'];

		if (!isset($this->params['plain']) || $this->params['plain'] != true) {

			$mode = GITPHP_BLOBDIFF_UNIFIED;	// default

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
			$this->headers[] = 'Content-type: text/plain; charset=UTF-8';
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
		if (isset($this->params['file']))
			$this->tpl->assign('file', $this->params['file']);

		$filediff = new GitPHP_FileDiff($this->project, $this->params['hashparent'], $this->params['hash']);
		$this->tpl->assign('filediff', $filediff);

		if (isset($this->params['plain']) && ($this->params['plain'] === true)) {
			return;
		}

		if (isset($this->params['sidebyside']) && ($this->params['sidebyside'] === true)) {
			$this->tpl->assign('sidebyside', true);
		}

		$commit = $this->project->GetCommit($this->params['hashbase']);
		$this->tpl->assign('commit', $commit);

		$blobparent = $this->project->GetBlob($this->params['hashparent']);
		$blobparent->SetCommit($commit);
		$blobparent->SetPath($this->params['file']);
		$this->tpl->assign('blobparent', $blobparent);

		$blob = $this->project->GetBlob($this->params['hash']);
		$blob->SetPath($this->params['file']);
		$this->tpl->assign('blob', $blob);

		$tree = $commit->GetTree();
		$this->tpl->assign('tree', $tree);
	}

}
