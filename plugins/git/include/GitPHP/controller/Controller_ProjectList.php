<?php


namespace Tuleap\Git\GitPHP;

/**
 * GitPHP Controller ProjectList
 *
 * Controller for listing projects
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */
/**
 * ProjectList controller class
 *
 * @package GitPHP
 * @subpackage Controller
 */
class Controller_ProjectList extends ControllerBase
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
		if (isset($this->params['opml']) && ($this->params['opml'] === true)) {
			return 'opml.tpl';
		} else if (isset($this->params['txt']) && ($this->params['txt'] === true)) {
			return 'projectindex.tpl';
		}
		return 'projectlist.tpl';
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
		if (isset($this->params['opml']) && ($this->params['opml'] === true)) {
			return '';
		} else if (isset($this->params['txt']) && ($this->params['txt'] === true)) {
			return '';
		}
		return $this->params['order'] . '|' . (isset($this->params['search']) ? $this->params['search'] : '');
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
		if (isset($this->params['opml']) && ($this->params['opml'] === true)) {
			if ($local) {
				return __('opml');
			}
			return 'opml';
		} else if (isset($this->params['txt']) && ($this->params['txt'] === true)) {
			if ($local) {
				return __('project index');
			}
			return 'project index';
		}
		if ($local) {
			return __('projects');
		}
		return 'projects';
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
		if (isset($_GET['o']))
			$this->params['order'] = $_GET['o'];
		else
			$this->params['order'] = 'project';
		if (isset($_GET['s']))
			$this->params['search'] = $_GET['s'];
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
		if (isset($this->params['opml']) && ($this->params['opml'] === true)) {
			$this->headers[] = "Content-type: text/xml; charset=UTF-8";
			Log::GetInstance()->SetEnabled(false);
		} else if (isset($this->params['txt']) && ($this->params['txt'] === true)) {
			$this->headers[] = "Content-type: text/plain; charset=utf-8";
			$this->headers[] = "Content-Disposition: inline; filename=\"index.aux\"";
			Log::GetInstance()->SetEnabled(false);
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
		$this->tpl->assign('order', $this->params['order']);
		
		$projectList = ProjectList::GetInstance();
		$projectList->Sort($this->params['order']);

		if ((empty($this->params['opml']) || ($this->params['opml'] !== true)) &&
		    (empty($this->params['txt']) || ($this->params['txt'] !== true)) &&
		    (!empty($this->params['search']))) {
		    	$this->tpl->assign('search', $this->params['search']);
			$matches = $projectList->Filter($this->params['search']);
			if (count($matches) > 0) {
				$this->tpl->assign('projectlist', $matches);
			}
		} else {
			if ($projectList->Count() > 0)
				$this->tpl->assign('projectlist', $projectList);
		}

		if ((empty($this->params['opml']) || ($this->params['opml'] !== true)) &&
		    (empty($this->params['txt']) || ($this->params['txt'] !== true))) {
			$this->tpl->assign('extrascripts', array('projectsearch'));
		}
	}

}