<?php
/**
 * GitPHP Controller Search
 *
 * Controller for running a search
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

require_once(GITPHP_INCLUDEDIR . 'util.highlight.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_filesearch.php');

define('GITPHP_SEARCH_COMMIT', 'commit');
define('GITPHP_SEARCH_AUTHOR', 'author');
define('GITPHP_SEARCH_COMMITTER', 'committer');
define('GITPHP_SEARCH_FILE', 'file');

/**
 * Search controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Search extends GitPHP_ControllerBase
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
		if (!GitPHP_Config::GetInstance()->GetValue('search', true)) {
			throw new GitPHP_MessageException('Search has been disabled', true);
		}

		parent::__construct();

		if (!$this->project) {
			throw new GitPHP_MessageException('Project is required for search', true);
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
		if ($this->params['searchtype'] == GITPHP_SEARCH_FILE) {
			return 'searchfiles.tpl';
		}
		return 'search.tpl';
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
		return (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['searchtype']) ? sha1($this->params['searchtype']) : '') . '|' . (isset($this->params['search']) ? sha1($this->params['search']) : '') . '|' . (isset($this->params['page']) ? $this->params['page'] : 0);
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
		if (!isset($this->params['searchtype']))
			$this->params['searchtype'] = GITPHP_SEARCH_COMMIT;

		if ($this->params['searchtype'] == GITPHP_SEARCH_FILE) {
			if (!GitPHP_Config::GetInstance()->GetValue('filesearch', true)) {
				throw new GitPHP_MessageException('File search has been disabled', true);
			}

		}

		if ((!isset($this->params['search'])) || (strlen($this->params['search']) < 2)) {
			throw new GitPHP_MessageException('You must enter search text of at least 2 characters', true);
		}

		if (isset($_GET['h']))
			$this->params['hash'] = $_GET['h'];
		else
			$this->params['hash'] = 'HEAD';
		if (isset($_GET['pg']))
			$this->params['page'] = $_GET['pg'];
		else
			$this->params['page'] = 0;
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
		if ($this->params['searchtype'] == GITPHP_SEARCH_FILE) {
			$this->LoadFilesearchData();
			return;
		}

		$results = array();
		switch ($this->params['searchtype']) {

			case GITPHP_SEARCH_COMMIT:
				$results = $this->project->SearchCommit($this->params['search'], $this->params['hash'], 101, ($this->params['page'] * 100));
				break;

			case GITPHP_SEARCH_AUTHOR:
				$results = $this->project->SearchAuthor($this->params['search'], $this->params['hash'], 101, ($this->params['page'] * 100));
				break;

			case GITPHP_SEARCH_COMMITTER:
				$results = $this->project->SearchCommitter($this->params['search'], $this->params['hash'], 101, ($this->params['page'] * 100));
				break;

			default:
				throw new GitPHP_MessageException('Invalid search type');

		}

		if (count($results) < 1) {
			throw new GitPHP_MessageException('No matches for "' . $this->params['search'] . '"', false);
		}

		if (count($results) > 100) {
			$this->tpl->assign('hasmore', true);
			$results = array_slice($results, 0, 100);
		}
		$this->tpl->assign('results', $results);

		$co = $this->project->GetCommit($this->params['hash']);
		$this->tpl->assign('hash', $co);
		$this->tpl->assign('tree', $co->GetTree());
		$this->tpl->assign('treehash', $co->GetTree());

		$this->tpl->assign('page', $this->params['page']);

	}
	
	/**
	 * LoadFilesearchData
	 *
	 * TODO temporary until templates are cleaned up
	 */
	private function LoadFilesearchData()
	{
		$filesearch = git_filesearch($this->params['hash'], $this->params['search'], false, ($this->params['page'] * 100), 101);

		if (count($filesearch) < 1) {
			throw new GitPHP_MessageException('No matches for "' . $this->params['search'] . '"');
		}

		$this->tpl->assign("hash",$this->params['hash']);

		$co = $this->project->GetCommit($this->params['hash']);

		if ($co) {
			$tree = $co->GetTree();
			if ($tree)
				$this->tpl->assign("treehash", $tree->GetHash());
			$this->tpl->assign("title", $co->GetTitle());
		}

		$this->tpl->assign("search",$this->params['search']);
		$this->tpl->assign("searchtype","file");
		$this->tpl->assign("page",$this->params['page']);
		$filesearchcount = count($filesearch);
		$this->tpl->assign("filesearchcount",$filesearchcount);


		$filesearchlines = array();
		$i = 0;
		foreach ($filesearch as $file => $data) {
			$filesearchline = array();
			$filesearchline["file"] = $file;
			if (strpos($file,"/") !== false) {
				$f = basename($file);
				$d = dirname($file);
				if ($d == "/")
					$d = "";
				$hlt = highlight($f, $this->params['search'], "searchmatch");
				if ($hlt)
					$hlt = $d . "/" . $hlt;
			} else
				$hlt = highlight($file, $this->params['search'], "searchmatch");
			if ($hlt)
				$filesearchline["filename"] = $hlt;
			else
				$filesearchline["filename"] = $file;
			$filesearchline["hash"] = $data['hash'];
			if ($data['type'] == "tree")
				$filesearchline["tree"] = TRUE;
			if (isset($data['lines'])) {
				$matches = array();
				foreach ($data['lines'] as $line) {
					$hlt = highlight($line,$this->params['search'],"searchmatch",floor(GITPHP_TRIM_LENGTH*1.5),true);
					if ($hlt)
						$matches[] = $hlt;
				}
				if (count($matches) > 0)
					$filesearchline["matches"] = $matches;
			}
			$filesearchlines[] = $filesearchline;
			++$i;
			if ($i >= 100)
				break;
		}
		$this->tpl->assign("filesearchlines",$filesearchlines);
	}

}
