<?php
/**
 * GitPHP Controller Snapshot
 *
 * Controller for getting a snapshot
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

/**
 * Snapshot controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller_Snapshot extends GitPHP_ControllerBase
{

	/**
	 * archive
	 *
	 * Stores the archive object
	 *
	 * @access private
	 */
	private $archive = null;

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
		if (isset($_GET['p'])) {
			$this->project = GitPHP_ProjectList::GetInstance()->GetProject(str_replace(chr(0), '', $_GET['p']));
			if (!$this->project) {
				throw new GitPHP_MessageException(sprintf(__('Invalid project %1$s'), $_GET['p']), true);
			}
		}

		if (!$this->project) {
			throw new GitPHP_MessageException(__('Project is required'), true);
		}

		$this->ReadQuery();
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
		return (isset($this->params['hash']) ? $this->params['hash'] : '') . '|' . (isset($this->params['path']) ? $this->params['path'] : '') . '|' . (isset($this->params['prefix']) ? $this->params['prefix'] : '') . '|' . $this->params['format'];
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
			return __('snapshot');
		}
		return 'snapshot';
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
		if (isset($_GET['h'])) $this->params['hash'] = $_GET['h'];
		if (isset($_GET['f'])) $this->params['path'] = $_GET['f'];
		if (isset($_GET['prefix'])) $this->params['prefix'] = $_GET['prefix'];
		if (isset($_GET['fmt']))
			$this->params['format'] = $_GET['fmt'];
		else
			$this->params['format'] = GitPHP_Config::GetInstance()->GetValue('compressformat', GITPHP_COMPRESS_ZIP);
			
		GitPHP_Log::GetInstance()->SetEnabled(false);
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
		$this->archive = new GitPHP_Archive($this->project, null, $this->params['format'], (isset($this->params['path']) ? $this->params['path'] : ''), (isset($this->params['prefix']) ? $this->params['prefix'] : ''));

		switch ($this->archive->GetFormat()) {
			case GITPHP_COMPRESS_TAR:
				$this->headers[] = 'Content-Type: application/x-tar';
				break;
			case GITPHP_COMPRESS_BZ2:
				$this->headers[] = 'Content-Type: application/x-bzip2';
				break;
			case GITPHP_COMPRESS_GZ:
				$this->headers[] = 'Content-Type: application/x-gzip';
				break;
			case GITPHP_COMPRESS_ZIP:
				$this->headers[] = 'Content-Type: application/x-zip';
				break;
			default:
				throw new Exception('Unknown compression type');
		}

		$this->headers[] = 'Content-Disposition: attachment; filename=' . $this->archive->GetFilename();
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
		$commit = null;

		if (!isset($this->params['hash']))
			$commit = $this->project->GetHeadCommit();
		else
			$commit = $this->project->GetCommit($this->params['hash']);

		$this->archive->SetObject($commit);
	}

	/**
	 * Render
	 *
	 * Render this controller
	 *
	 * @access public
	 */
	public function Render()
	{
		$this->LoadData();

		$cache = GitPHP_Config::GetInstance()->GetValue('cache', false);
		$cachehandle = false;
		$cachefile = '';
		if ($cache && is_dir(GITPHP_CACHE)) {
			$key = ($this->archive->GetObject() ? $this->archive->GetObject()->GetHash() : '') . '|' . (isset($this->params['path']) ? $this->params['path'] : '') . '|' . (isset($this->params['prefix']) ? $this->params['prefix'] : '');
			$cachefile = sha1($key) . '-' . $this->archive->GetFilename();
			$cachedfilepath = GITPHP_CACHE . $cachefile;

			if (file_exists($cachedfilepath)) {
				// read cached file
				$cachehandle = fopen($cachedfilepath, 'rb');
				if ($cachehandle) {
					while (!feof($cachehandle)) {
						print fread($cachehandle, 1048576);
						flush();
					}
					fclose($cachehandle);
					return;
				}
			}
		}

		if ($this->archive->Open()) {

			$tmpcachefile = '';

			if ($cache && !empty($cachefile)) {
				// write cached file too
				$tmpcachefile = 'tmp-' . $cachefile;
				$cachehandle = fopen(GITPHP_CACHE . $tmpcachefile, 'wb');
			}

			while (($data = $this->archive->Read()) !== false) {

				print $data;
				flush();

				if ($cache && $cachehandle) {
					fwrite($cachehandle, $data);
				}

			}
			$this->archive->Close();

			if ($cachehandle) {
				fclose($cachehandle);
				sleep(1);
				rename(GITPHP_CACHE . $tmpcachefile, GITPHP_CACHE . $cachefile);
			}
		}
	}

}
