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
		if (isset($this->params['plain']) && $this->params['plain'])
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
		if (isset($this->params['plain']) && $this->params['plain']) {
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
		$commit = $this->project->GetCommit($this->params['hashbase']);
		$this->tpl->assign('commit', $commit);

		if ((!isset($this->params['hash'])) && (isset($this->params['file']))) {
			$this->params['hash'] = $commit->PathToHash($this->params['file']);
		}

		$blob = $this->project->GetBlob($this->params['hash']);
		if ($this->params['file'])
			$blob->SetName($this->params['file']);
		$blob->SetCommit($commit);
		$this->tpl->assign('blob', $blob);

		if (isset($this->params['plain']) && $this->params['plain']) {
			return;
		}

		$head = $this->project->GetHeadCommit();
		$this->tpl->assign('head', $head);

		$this->tpl->assign('tree', $commit->GetTree());

		if (GitPHP_Config::GetInstance()->GetValue('filemimetype', true)) {
			$mime = $blob->FileMime();
			if ($mime)
				$mimetype = strtok($mime, '/');
		}

		if ($mime && (strtok($mime, '/') == 'image')) {
			$this->tpl->assign('datatag', true);
			$this->tpl->assign('mime', $mime);
			$this->tpl->assign('data', base64_encode($blob->GetData()));
			return;
		}

		if (GitPHP_Config::GetInstance()->GetValue('geshi', true)) {
			include_once(GitPHP_Config::GetInstance()->GetValue('geshiroot', 'lib/geshi/') . "geshi.php");
			if (class_exists('GeSHi')) {
				$geshi = new GeSHi("",'php');
				if ($geshi) {
					$lang = $geshi->get_language_name_from_extension(substr(strrchr($blob->GetPath(),'.'),1));
					if (!empty($lang)) {
						$geshi->enable_classes();
						$geshi->enable_strict_mode(GESHI_MAYBE);
						$geshi->set_source($blob->GetData());
						$geshi->set_language($lang);
						$geshi->set_header_type(GESHI_HEADER_PRE_TABLE);
						$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
						$this->tpl->assign('geshiout', $geshi->parse_code());
						$this->tpl->assign('extracss', $geshi->get_stylesheet());
						$this->tpl->assign('geshi', true);
						return;
					}
				}
			}
		}

		$this->tpl->assign('bloblines', $blob->GetData(true));
	}

}
