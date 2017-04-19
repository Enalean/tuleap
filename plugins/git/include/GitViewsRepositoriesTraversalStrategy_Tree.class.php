<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'PathJoinUtil.php';
require_once 'html.php';

/**
 * Traverse a list of repositories and provides a tree in a table view
 */
class GitViewsRepositoriesTraversalStrategy_Tree extends GitViewsRepositoriesTraversalStrategy {
    private $view;
    private $lastPushes;

    /** @var Git_GitRepositoryUrlManager */
    private $url_manager;

    /**
     * Constructor
     *
     * @param GitViews $view The GitViews
     */
    public function __construct($lastPushes, Git_GitRepositoryUrlManager $url_manager) {
        parent::__construct();
        $this->lastPushes  = $lastPushes;
        $this->url_manager = $url_manager;
    }

    /**
     * Get the main wrapper of the whole representation
     *
     * @param string $inner The inner string
     *
     * @return string the $inner encapsuled in the wrapper
     */
    protected function getMainWrapper($inner) {
        return '<tr>'. $inner .'</tr>';
    }

    /**
     * Get Item wrapper
     *
     * @param GitRepository $repo  the string representation of the item
     * @param string        $inner the string representation of the item
     *
     * @return string the $inner encapsulated in its own wrapper
     */
    protected function getItemWrapper(GitRepository $repo, $inner) {
        return '<td>'. $inner .'</td>';
    }

    /**
     * Obtain the tree of git repositories for a user
     *
     * @param Array $repositories Array of raw representation of repositories, indexed by repository id (the person that made the choice of the format must be executed)
     * @param PFUser  $user         The user who traverse the forest (yet another foolish expression)
     *
     * @result Array
     */
    public function getTree(array $repositories, PFUser $user) {
        $tree = array();
        foreach ($repositories as $repoId => $row) {
            $path = explode('/', unixPathJoin(array($row['repository_namespace'], $row['repository_name'])));
            $repo = $this->getRepository($row);
            if ($repo->userCanRead($user)) {
                $this->insertInTree($tree, $repo, $path);
            }
        }
        return $tree;
    }

    public function insertInTree(&$tree, GitRepository $repository, array $path) {
        if (count($path)) {
            $head = array_shift($path);
            if (count($path)) {
                $this->insertInTree($tree[$head], $repository, $path);
            } else {
                $tree[$head] = $repository;
            }
        }
    }

    /**
     * Display the list of repositories
     *
     * @param array $repositories Array of raw representation of repositories, indexed by repository id
     * @param PFUser  $user         The user who traverse the forest
     *
     * @return string
     */
    public function fetch(array $repositories, PFUser $user) {
        $html = '';
        if (empty($repositories)) {
            return '';
        }
        $tree = $this->getTree($repositories, $user);
        if (!empty($tree)) {
            $html .= '<table id="git_repositories_list" class="table">';

            // header
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>'. $GLOBALS['Language']->getText('plugin_git', 'tree_view_repository') .'</th>';
            $html .= '<th>'. $GLOBALS['Language']->getText('plugin_git', 'tree_view_description') .'</th>';
            $html .= '<th>'. $GLOBALS['Language']->getText('plugin_git', 'tree_view_last_push') .'</th>';
            $html .= '</tr>';
            $html .= '</thead>';

            // body
            $rowCount = 0;
            $html .= '<tbody>'. $this->fetchRows($tree, 0) .'</tbody>';

            $html .= '</table>';
        } else {
            $html .= "<h3>".$GLOBALS['Language']->getText('plugin_git', 'tree_msg_no_available_repo')."</h3>";
        }
        return $html;
    }

    protected function fetchRows($repositories, $depth) {
        $nodeHtml   = '';
        $leavesHtml = '';
        foreach ($repositories as $folder => $child) {
            if ($child instanceof GitRepository) {
                $leavesHtml .= $this->fetchGitRepositoryRow($child, $folder, $depth);
            } else {
                $nodeHtml .= $this->fetchFolderRow($child, $folder, $depth);
            }
        }
        return $nodeHtml.$leavesHtml;
    }

    protected function fetchGitRepositoryRow(GitRepository $repository, $name, $depth) {
        $trclass     = 'boxitem';

        $label       = $repository->getBasenameHTMLLink($this->url_manager);
        $description = $repository->getDescription();

        $lastPush    = '&nbsp;';
        if (isset($this->lastPushes[$repository->getId()])) {
            $row = $this->lastPushes[$repository->getId()];
            $lastPushDate = html_time_ago($row['push_date']);
            $who = UserHelper::instance()->getLinkOnUserFromUserId($row['user_id']);
            $lastPush = $GLOBALS['Language']->getText('plugin_git', 'tree_view_by', array($lastPushDate, $who));
        }

        return $this->fetchHTMLRow($trclass, $depth, $label, $description, $lastPush);
    }

    protected function fetchFolderRow(array $children, $name, $depth) {
        $trclass     = 'boxitemalt';
        $description = '';
        $lastPush    = '';

        $html  = '';
        $html .= $this->fetchHTMLRow($trclass, $depth, $name, $description, $lastPush);
        $html .= $this->fetchRows($children, $depth + 1);

        return $html;
    }

    protected function fetchHTMLRow($class, $depth, $label, $description, $lastPush) {
        $HTMLPurifier = Codendi_HTMLPurifier::instance();
        $description = $HTMLPurifier->purify($description, CODENDI_PURIFIER_CONVERT_HTML);
        $html = '';
        $html .= '<tr class="' . $class . '">';
        $html .= '<td style="padding-left: ' . ($depth + 1) . 'em;">' . $label . '</td>';
        $html .= '<td>' . ($description ? $description : '&nbsp;') . '</td>';
        $html .= '<td>' . ($lastPush    ? $lastPush    : '&nbsp;') . '</td>';
        $html .= '</tr>';
        return $html;
    }
}
?>
