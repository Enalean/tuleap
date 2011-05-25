<?php
/**
 * GitPHP Controller Blobdiff
 *
 * Controller for displaying a blobdiff
 *
 * @author Mattias Ulbrich
 * @package GitPHP
 * @subpackage Controller
 */

/**
 * private little helper
 */
function toH($in) {
	if(!$in || strlen($in) == 0) {
		return "&nbsp";
	} else {
		$in = htmlentities($in);
		$in = str_replace(" ", "&nbsp;", $in);
		return $in;
	}
}

/**
 * Blobdiff controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Sidediff extends GitPHP_ControllerBase
{
	private $gitexe;

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
		$this->gitexe = new GitPHP_GitExe($this->project);

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
		return 'sidebyside.tpl';
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
		return (isset($this->params['hashbase']) ? $this->params['hashbase'] : '') . '|' . (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['hashparent']) ? $this->params['hashparent'] : '') . '|' . (isset($this->params['file']) ? sha1($this->params['file']) : '');
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
			return __('blobdiff_sidebyside');
		}
		return 'blobdiff_sidebyside';
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

		$diffData = $this->makeDiffData();
		$this->tpl->assign('diffdata', $diffData);

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

	/**
	 * construct the side by side diff data from the git data
	 * The result is an array of ternary arrays with 3 elements each:
	 * First the mode ("" or "-added" or "-deleted" or "-modified"),
	 * then the first column, then the second.
	 *
	 * @return an array of line elements (see above)
	 */
	private function makeDiffData()
	{
		$rawBlob = $this->gitexe->Execute(GIT_CAT_FILE,
			array("blob", $this->params['hashparent']));
		$blob  = explode("\n", $rawBlob);

		$diffLines = explode("\n", $this->gitexe->Execute("diff",
			array("-U0", $this->params['hashparent'],
				$this->params['hash'])));

		//
		// parse diffs
		$diffs = array();
		$currentDiff = FALSE;
		foreach($diffLines as $d) {
			if(strlen($d) == 0)
				continue;
			switch($d[0]) {
				case '@':
					if($currentDiff)
						$diffs[] = $currentDiff;
					$comma = strpos($d, ",");
					$line = -intval(substr($d, 2, $comma-2));
					$currentDiff = array("line" => $line,
						"left" => array(), "right" => array());
					break;
				case '+':
					if($currentDiff)
						$currentDiff["right"][] = substr($d, 1);
					break;
				case '-':
					if($currentDiff)
						$currentDiff["left"][] = substr($d, 1);
					break;
				case ' ':
					echo "should not happen!";
					if($currentDiff) {
						$currentDiff["left"][] = substr($d, 1);
						$currentDiff["right"][] = substr($d, 1);
					}
					break;
			}
		}
		if($currentDiff)
			$diffs[] = $currentDiff;
		// echo "<pre>"; print_r($diffs);

		//
		// iterate over diffs
		$output = array();
		$idx = 0;
		foreach($diffs as $d) {
			while($idx+1 < $d['line']) {
				$h = toH($blob[$idx]);
				$output[] = array(' ', $h, $h);
				$idx ++;
			}

			if(count($d['left']) == 0) {
				$mode = '-added';
			} elseif(count($d['right']) == 0) {
				$mode = '-deleted';
			} else {
				$mode = '-modified';
			}

			for($i = 0; $i < count($d['left']) || $i < count($d['right']); $i++) {
				$left = $i < count($d['left']) ? $d['left'][$i] : FALSE;
				$right = $i < count($d['right']) ? $d['right'][$i] : FALSE;
				$output[] = array($mode, toH($left), toH($right));
			}

			$idx += count($d['left']);
		}

		while($idx < count($blob)) {
			$h = toH($blob[$idx]);
			$output[] = array(' ', $h, $h);
			$idx ++;
		}

		return $output;
	}

}
