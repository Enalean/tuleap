<?php

namespace Tuleap\Git\GitPHP;

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
class Controller
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
				$controller = new Controller_Search();
				break;
			case 'commitdiff':
			case 'commitdiff_plain':
				$controller = new Controller_Commitdiff();
				if ($action === 'commitdiff_plain')
					$controller->SetParam('plain', true);
				break;
			case 'blobdiff':
			case 'blobdiff_plain':
				$controller = new Controller_Blobdiff();
				if ($action === 'blobdiff_plain')
					$controller->SetParam('plain', true);
				break;
			case 'history':
				$controller = new Controller_History();
				break;
			case 'shortlog':
			case 'log':
				$controller = new Controller_Log();
				if ($action === 'shortlog')
					$controller->SetParam('short', true);
				break;
			case 'snapshot':
				$controller = new Controller_Snapshot();
				break;
			case 'tree':
				$controller = new Controller_Tree();
				break;
			case 'tag':
				$controller = new Controller_Tag();
				break;
			case 'tags':
				$controller = new Controller_Tags();
				break;
			case 'heads':
				$controller = new Controller_Heads();
				break;
			case 'blame':
				$controller = new Controller_Blame();
				break;
			case 'blob':
			case 'blob_plain':	
				$controller = new Controller_Blob();
				if ($action === 'blob_plain')
					$controller->SetParam('plain', true);
				break;
			case 'atom':
			case 'rss':
				$controller = new Controller_Feed();
				if ($action == 'rss')
					$controller->SetParam('format', GITPHP_FEED_FORMAT_RSS);
				else if ($action == 'atom')
					$controller->SetParam('format', GITPHP_FEED_FORMAT_ATOM);
				break;
			case 'commit':
				$controller = new Controller_Commit();
				break;
			case 'summary':
				$controller = new Controller_Project();
				break;
			case 'project_index':
				$controller = new Controller_ProjectList();
				$controller->SetParam('txt', true);
				break;
			case 'opml':
				$controller = new Controller_ProjectList();
				$controller->SetParam('opml', true);
				break;
			default:
				if (isset($_GET['p'])) {
					$controller = new Controller_Project();
				} else {
					$controller = new Controller_ProjectList();
				}
		}

		return $controller;
	}

}