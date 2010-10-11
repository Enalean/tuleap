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

require_once(GITPHP_CONTROLLERDIR . 'ControllerBase.class.php');

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
				require_once(GITPHP_CONTROLLERDIR . 'Controller_Search.class.php');
				$controller = new GitPHP_Controller_Search();
				break;
			case 'commitdiff':
			case 'commitdiff_plain':
				require_once(GITPHP_CONTROLLERDIR . 'Controller_Commitdiff.class.php');
				$controller = new GitPHP_Controller_Commitdiff();
				if ($action === 'commitdiff_plain')
					$controller->SetParam('plain', true);
				break;
			case 'blobdiff':
			case 'blobdiff_plain':
				require_once(GITPHP_CONTROLLERDIR . 'Controller_Blobdiff.class.php');
				$controller = new GitPHP_Controller_Blobdiff();
				if ($action === 'blobdiff_plain')
					$controller->SetParam('plain', true);
				break;
			case 'history':
				require_once(GITPHP_CONTROLLERDIR . 'Controller_History.class.php');
				$controller = new GitPHP_Controller_History();
				break;
			case 'shortlog':
			case 'log':
				require_once(GITPHP_CONTROLLERDIR . 'Controller_Log.class.php');
				$controller = new GitPHP_Controller_Log();
				if ($action === 'shortlog')
					$controller->SetParam('short', true);
				break;
			case 'snapshot':
				require_once(GITPHP_CONTROLLERDIR . 'Controller_Snapshot.class.php');
				$controller = new GitPHP_Controller_Snapshot();
				break;
			case 'tree':
				require_once(GITPHP_CONTROLLERDIR . 'Controller_Tree.class.php');
				$controller = new GitPHP_Controller_Tree();
				break;
			case 'tag':
				require_once(GITPHP_CONTROLLERDIR . 'Controller_Tag.class.php');
				$controller = new GitPHP_Controller_Tag();
				break;
			case 'tags':
				require_once(GITPHP_CONTROLLERDIR . 'Controller_Tags.class.php');
				$controller = new GitPHP_Controller_Tags();
				break;
			case 'heads':
				require_once(GITPHP_CONTROLLERDIR . 'Controller_Heads.class.php');
				$controller = new GitPHP_Controller_Heads();
				break;
			case 'blame':
				require_once(GITPHP_CONTROLLERDIR . 'Controller_Blame.class.php');
				$controller = new GitPHP_Controller_Blame();
				break;
			case 'blob':
			case 'blob_plain':	
				require_once(GITPHP_CONTROLLERDIR . 'Controller_Blob.class.php');
				$controller = new GitPHP_Controller_Blob();
				if ($action === 'blob_plain')
					$controller->SetParam('plain', true);
				break;
			case 'atom':
			case 'rss':
				require_once(GITPHP_CONTROLLERDIR . 'Controller_Feed.class.php');
				$controller = new GitPHP_Controller_Feed();
				if ($action == 'rss')
					$controller->SetParam('format', GITPHP_FEED_FORMAT_RSS);
				else if ($action == 'atom')
					$controller->SetParam('format', GITPHP_FEED_FORMAT_ATOM);
				break;
			case 'commit':
				require_once(GITPHP_CONTROLLERDIR . 'Controller_Commit.class.php');
				$controller = new GitPHP_Controller_Commit();
				break;
			case 'summary':
				require_once(GITPHP_CONTROLLERDIR . 'Controller_Project.class.php');
				$controller = new GitPHP_Controller_Project();
				break;
			case 'project_index':
				require_once(GITPHP_CONTROLLERDIR . 'Controller_ProjectList.class.php');
				$controller = new GitPHP_Controller_ProjectList();
				$controller->SetParam('txt', true);
				break;
			case 'opml':
				require_once(GITPHP_CONTROLLERDIR . 'Controller_ProjectList.class.php');
				$controller = new GitPHP_Controller_ProjectList();
				$controller->SetParam('opml', true);
				break;
			default:
				if (isset($_GET['p'])) {
					require_once(GITPHP_CONTROLLERDIR . 'Controller_Project.class.php');
					$controller = new GitPHP_Controller_Project();
				} else {
					require_once(GITPHP_CONTROLLERDIR . 'Controller_ProjectList.class.php');
					$controller = new GitPHP_Controller_ProjectList();
				}
		}

		return $controller;
	}

}
