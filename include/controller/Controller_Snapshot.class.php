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
		return 'snapshot.tpl';
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

		$headers = $this->archive->GetHeaders();
		
		if (count($headers) > 0)
			$this->headers = array_merge($this->headers, $headers);
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

		$this->tpl->assign('archive', $this->archive->GetData());
	}

}
