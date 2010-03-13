<?php
/**
 * GitPHP Controller Blob
 *
 * Controller for displaying a blob
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

require_once(GITPHP_INCLUDEDIR . 'gitutil.git_get_hash_by_path.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.git_path_trees.php');
require_once(GITPHP_INCLUDEDIR . 'gitutil.read_info_ref.php');

/**
 * Blob controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Blob extends GitPHP_ControllerBase
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
			throw new GitPHP_MessageException('Project is required for blob', true);
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
		if ($this->params['plain'])
			return 'blobplain.tpl';
		return 'blob.tpl';
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
		return (isset($this->params['hashbase']) ? $this->params['hashbase'] : '') . '|' . (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['file']) ? sha1($this->params['file']) : '');
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
		if (isset($_GET['hb']))
			$this->params['hashbase'] = $_GET['hb'];
		else
			$this->params['hashbase'] = 'HEAD';
		if (isset($_GET['f']))
			$this->params['file'] = $_GET['f'];
		if (isset($_GET['h'])) {
			$this->params['hash'] = $_GET['h'];
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
		if ($this->params['plain']) {
			// XXX: Nasty hack to cache headers
			if (!$this->tpl->is_cached('blobheaders.tpl', $this->GetFullCacheKey())) {
				if (isset($this->params['file']))
					$saveas = $this->params['file'];
				else
					$saveas = $this->params['hash'] . ".txt";

				$blob = $this->project->GetBlob($this->params['hash']);
				$blob->SetName($this->params['file']);

				$headers = array();

				if (GitPHP_Config::GetInstance()->GetValue('filemimetype', true))
					$mime = $blob->FileMime();

				if ($mime)
					$headers[] = "Content-type: " . $mime;
				else
					$headers[] = "Content-type: text/plain; charset=UTF-8";

				$headers[] = "Content-disposition: inline; filename=\"" . $saveas . "\"";

				$this->tpl->assign("blobheaders", serialize($headers));
			}
			$out = $this->tpl->fetch('blobheaders.tpl', $this->GetFullCacheKey());

			$this->headers = unserialize($out);
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
		if ((!isset($this->params['hash'])) && (isset($this->params['file']))) {
			$this->params['hash'] = git_get_hash_by_path($this->params['hashbase'], $this->params['file'], 'blob');
		}

		$blob = $this->project->GetBlob($this->params['hash']);
		$blob->SetName($this->params['file']);

		if ($this->params['plain']) {
			$this->tpl->assign("blob", $blob->GetData());
			return;
		}

		$head = $this->project->GetHeadCommit()->GetHash();
		$catout = $blob->GetData();
		$this->tpl->assign("hash",$this->params['hash']);
		$this->tpl->assign("hashbase",$this->params['hashbase']);
		$this->tpl->assign("head", $head);
		$co = $this->project->GetCommit($this->params['hashbase']);
		if ($co) {
			$this->tpl->assign("fullnav",TRUE);
			$refs = read_info_ref();
			$this->tpl->assign("tree",$co->GetTree()->GetHash());
			$this->tpl->assign("title",$co->GetTitle());
			if (isset($this->params['file']))
				$this->tpl->assign("file",$this->params['file']);
			if ($this->params['hashbase'] == "HEAD") {
				if (isset($refs[$head]))
					$this->tpl->assign("hashbaseref",$refs[$head]);
			} else {
				if (isset($refs[$this->params['hashbase']]))
					$this->tpl->assign("hashbaseref",$refs[$this->params['hashbase']]);
			}
		}
		$paths = git_path_trees($this->params['hashbase'], $this->params['file']);
		$this->tpl->assign("paths",$paths);

		if (GitPHP_Config::GetInstance()->GetValue('filemimetype', true)) {
			$mime = $blob->FileMime();
			if ($mime)
				$mimetype = strtok($mime, '/');
		}

		if ($mimetype == "image") {
			$this->tpl->assign("mime", $mime);
			$this->tpl->assign("data", base64_encode($catout));
		} else {
			$usedgeshi = GitPHP_Config::GetInstance()->GetValue('geshi', true);
			if ($usedgeshi) {
				$usedgeshi = FALSE;
				include_once(GitPHP_Config::GetInstance()->GetValue('geshiroot', 'lib/geshi/') . "geshi.php");
				if (class_exists("GeSHi")) {
					$geshi = new GeSHi("",'php');
					if ($geshi) {
						$lang = "";
						if (isset($this->params['file']))
							$lang = $geshi->get_language_name_from_extension(substr(strrchr($this->params['file'],'.'),1));
						if (isset($lang) && (strlen($lang) > 0)) {
							$geshi->enable_classes();
							$geshi->set_source($catout);
							$geshi->set_language($lang);
							$geshi->set_header_type(GESHI_HEADER_DIV);
							$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
							$this->tpl->assign("geshiout",$geshi->parse_code());
							$this->tpl->assign("extracss",$geshi->get_stylesheet());
							$usedgeshi = TRUE;
						}
					}
				}
			}

			if (!$usedgeshi) {
				$lines = explode("\n",$catout);
				$this->tpl->assign("lines",$lines);
			}
		}
	}

}
