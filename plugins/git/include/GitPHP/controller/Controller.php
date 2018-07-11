<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) 2010 Christopher Han
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
    public static function GetController($action) // @codingStandardsIgnoreLine
    {
        $controller = null;

        switch ($action) {
            case 'search':
                $controller = new Controller_Search();
                break;
            case 'commitdiff':
            case 'commitdiff_plain':
                $controller = new Controller_Commitdiff();
                if ($action === 'commitdiff_plain') {
                    $controller->SetParam('plain', true);
                }
                break;
            case 'blobdiff':
            case 'blobdiff_plain':
                $controller = new Controller_Blobdiff();
                if ($action === 'blobdiff_plain') {
                    $controller->SetParam('plain', true);
                }
                break;
            case 'history':
                $controller = new Controller_History();
                break;
            case 'shortlog':
                $controller = new Controller_Log();
                break;
            case 'snapshot':
                $controller = new Controller_Snapshot();
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
                if ($action === 'blob_plain') {
                    $controller->SetParam('plain', true);
                }
                break;
            case 'atom':
            case 'rss':
                $controller = new Controller_Feed();
                if ($action == 'rss') {
                    $controller->SetParam('format', Controller_Feed::FEED_FORMAT_RSS);
                } elseif ($action == 'atom') {
                    $controller->SetParam('format', Controller_Feed::FEED_FORMAT_ATOM);
                }
                break;
            case 'commit':
                $controller = new Controller_Commit();
                break;
            case 'tree':
            default:
                $controller = new Controller_Tree();
        }

        return $controller;
    }
}
