<?php
/**
 * GitPHP Controller
 *
 * Controller factory
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Controller
 */

/**
 * Controller
 *
 * @package GitPHP
 * @subpackage Controller
 */
class GitPHP_Controller
{

	/**
	 * GetController
	 *
	 * Gets a controller for an action
	 *
	 * @access public
	 * @static
	 * @param string $action action
	 * @return mixed controller object
	 */
	public static function GetController($action)
	{
		$controller = null;

		switch ($action) {
			case 'search':
				$controller = new GitPHP_Controller_Search();
				break;
			case 'commitdiff':
			case 'commitdiff_plain':
				$controller = new GitPHP_Controller_Commitdiff();
				if ($action === 'commitdiff_plain')
					$controller->SetParam('plain', true);
				break;
			case 'blobdiff':
			case 'blobdiff_plain':
				$controller = new GitPHP_Controller_Blobdiff();
				if ($action === 'blobdiff_plain')
					$controller->SetParam('plain', true);
				break;
			case 'history':
				$controller = new GitPHP_Controller_History();
				break;
			case 'shortlog':
			case 'log':
				$controller = new GitPHP_Controller_Log();
				if ($action === 'shortlog')
					$controller->SetParam('short', true);
				break;
			case 'snapshot':
				$controller = new GitPHP_Controller_Snapshot();
				break;
			case 'tree':
				$controller = new GitPHP_Controller_Tree();
				break;
			case 'tag':
				$controller = new GitPHP_Controller_Tag();
				break;
			case 'tags':
				$controller = new GitPHP_Controller_Tags();
				break;
			case 'heads':
				$controller = new GitPHP_Controller_Heads();
				break;
			case 'blame':
				$controller = new GitPHP_Controller_Blame();
				break;
			case 'blob':
			case 'blob_plain':	
				$controller = new GitPHP_Controller_Blob();
				if ($action === 'blob_plain')
					$controller->SetParam('plain', true);
				break;
			case 'atom':
			case 'rss':
				$controller = new GitPHP_Controller_Feed();
				if ($action == 'rss')
					$controller->SetParam('format', GITPHP_FEED_FORMAT_RSS);
				else if ($action == 'atom')
					$controller->SetParam('format', GITPHP_FEED_FORMAT_ATOM);
				break;
			case 'commit':
				$controller = new GitPHP_Controller_Commit();
				break;
			case 'summary':
				$controller = new GitPHP_Controller_Project();
				break;
			case 'project_index':
				$controller = new GitPHP_Controller_ProjectList();
				$controller->SetParam('txt', true);
				break;
			case 'opml':
				$controller = new GitPHP_Controller_ProjectList();
				$controller->SetParam('opml', true);
				break;
			default:
				if (isset($_GET['p'])) {
					$controller = new GitPHP_Controller_Project();
				} else {
					$controller = new GitPHP_Controller_ProjectList();
				}
		}

		return $controller;
	}

}
