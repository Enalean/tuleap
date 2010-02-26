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

require_once(GITPHP_INCLUDEDIR . 'util.age_string.php');
require_once(GITPHP_INCLUDEDIR . 'util.highlight.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_read_revlist.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_filesearch.php');

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
		if (isset($this->params['searchtype']) && ($this->params['searchtype'] == 'file')) {
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
		if (isset($_GET['st']))
			$this->params['searchtype'] = $_GET['st'];

		if (isset($this->params['searchtype']) && ($this->params['searchtype'] == 'file')) {
			if (!GitPHP_Config::GetInstance()->GetValue('filesearch', true)) {
				throw new GitPHP_MessageException('File search has been disabled', true);
			}

		}

		if (isset($_GET['s']))
			$this->params['search'] = $_GET['s'];

		if ((!isset($this->params['search'])) || (strlen($this->params['search']) < 2)) {
			throw new GitPHP_MessageException('You must enter search text of at least 2 characters', true);
		}

		if (isset($_GET['h']))
			$this->params['hash'] = $_GET['h'];
		else
			$this->params['hash'] = 'HEAD';
		if (isset($_GET['pg']))
			$this->params['page'] = $_GET['pg'];
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
		if (isset($this->params['searchtype']) && ($this->params['searchtype'] == 'file')) {
			$this->LoadFilesearchData();
			return;
		}

		$co = $this->project->GetCommit($this->params['hash']);

		$revlist = git_read_revlist($this->params['hash'], 101, ($this->params['page'] * 100), FALSE, FALSE, $this->params['searchtype'], $this->params['search']);
		if (count($revlist) < 1 || (strlen($revlist[0]) < 1)) {
			throw new GitPHP_MessageException('No matches for "' . $this->params['search'] . '"', false);
		}

		$this->tpl->assign("hash",$this->params['hash']);
		$this->tpl->assign("treehash", $co->GetTree()->GetHash());

		$this->tpl->assign("search",$this->params['search']);
		$this->tpl->assign("searchtype",$this->params['searchtype']);
		$this->tpl->assign("page",$this->params['page']);
		$revlistcount = count($revlist);
		$this->tpl->assign("revlistcount",$revlistcount);

		$this->tpl->assign("title", $co->GetTitle());

		date_default_timezone_set('UTC');
		$commitlines = array();
		$commitcount = min(100,$revlistcount);
		for ($i = 0; $i < $commitcount; ++$i) {
			$commit = $revlist[$i];
			if (strlen(trim($commit)) > 0) {
				$commitline = array();
				$co2 = $this->project->GetCommit($commit);
				$commitline["commit"] = $commit;
				$age = $co2->GetAge();
				if ($age > 60*60*24*7*2) {
					$commitline['agestringdate'] = date('Y-m-d', $co2->GetCommitterEpoch());
					$commitline['agestringage'] = age_string($age);
				} else {
					$commitline['agestringdate'] = age_string($age);
					$commitline['agestringage'] = date('Y-m-d', $co2->GetCommitterEpoch());
				}
				$commitline["authorname"] = $co2->GetAuthorName();
				$title = $co2->GetTitle();
				$titleshort = $co2->GetTitle(GITPHP_TRIM_LENGTH);
				$commitline["title_short"] = $titleshort;
				if (strlen($titleshort) < strlen($title))
					$commitline["title"] = $title;
				$commitline["committree"] = $co2->GetTree()->GetHash();
				$matches = array();
				$commentlines = $co2->GetComment();
				foreach ($commentlines as $comline) {
					$hl = highlight($comline, $this->params['search'], "searchmatch", GITPHP_TRIM_LENGTH);
					if ($hl && (strlen($hl) > 0))
						$matches[] = $hl;
				}
				$commitline["matches"] = $matches;
				$commitlines[] = $commitline;
				unset($co2);
			}
		}
		
		$this->tpl->assign("commitlines",$commitlines);
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
