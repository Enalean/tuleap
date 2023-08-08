<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 *
 */

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Project\Event\GetUriFromCrossReference;
use Tuleap\Reference\GetReferenceEvent;
use Tuleap\Reference\ReferenceGetTooltipChainJson;
use Tuleap\Reference\ReferenceGetTooltipChainLegacy;
use Tuleap\Reference\ReferenceGetTooltipChainOpenGraph;

$reference_manager = ReferenceManager::instance();
$request           = HTTPRequest::instance();
$project_manager   = ProjectManager::instance();

$project = null;
if ($request->exist('project')) {
    try {
        if (is_numeric($request->get('project'))) {
            $project = $project_manager->getValidProject((int) $request->get('project'));
        } else {
            $project = $project_manager->getProjectByCaseInsensitiveUnixName($request->get('project'));
        }
    } catch (Project_NotFoundException $e) {
    }
} elseif ($request->exist('group_id')) {
    $vGroupId = new Valid_GroupId();
    if ($request->valid($vGroupId)) {
        try {
            $project = $project_manager->getValidProject((int) $request->get('group_id'));
        } catch (Project_NotFoundException $e) {
        }
    }
}
if (! $project) {
    $project = $project_manager->getProject(Project::DEFAULT_TEMPLATE_PROJECT_ID);
}
$group_id = $project->getID();

$vKey = new Valid_String('key');
$vKey->required();
$vVal = new Valid_String('val');
$vVal->required();
if ((! $request->valid($vKey)) || (! $request->valid($vVal))) {
    $GLOBALS['Response']->sendStatusCode(400);
    exit_error(
        $GLOBALS['Language']->getText('global', 'error'),
        $GLOBALS['Language']->getText('include_exit', 'missing_param_err')
    );
}

$keyword = $request->get('key');
$value   = $request->get('val');
$args    = explode("/", $value);

if ($keyword === 'wiki') {
    $wiki = new WikiDao();
    // Wiki Specific
    //If wiki page exists, we keep the value to handle 'toto/titi' wiki page
    //If wiki page does not exist, we check if there is a version number in the
    //wiki page name to handle 'toto/titi/1' (1 is the version number)
    if ($wiki->retrieveWikiPageId($value, $group_id) != null) {
        $args = [$value];
    } elseif (preg_match('%^(.*)/(\d+)$%', $value, $matches)) {
        $args = [$matches[1], $matches[2]];
    }
}

$event_manager = EventManager::instance();
$event         = new GetReferenceEvent(
    $reference_manager,
    $project,
    $keyword,
    $value
);

$event_manager->dispatch($event);

$ref = $event->getReference();
if ($ref === null) {
    if ($keyword == ReferenceManager::KEYWORD_ARTIFACT_LONG || $keyword == ReferenceManager::KEYWORD_ARTIFACT_SHORT) {
        $ref = $reference_manager->loadReferenceFromKeyword($keyword, $value);

        $event_manager->processEvent(
            Event::SET_ARTIFACT_REFERENCE_GROUP_ID,
            [
                'artifact_id' => $value,
                'reference'   => &$ref,
            ]
        );
    } else {
        $ref = $reference_manager->loadReferenceFromKeywordAndNumArgs($keyword, $group_id, count($args), $request->get('val'));
    }
}

if ($ref) {
    if ($project) {
        $project_name = $project->getUnixName();
    }
    $ref->replaceLink($args, $project_name);
} else {
    $cross_reference = $reference_manager->getCrossReferenceByKeyword($keyword);

    if (isset($cross_reference['source_id'])) {
        $source_id                   = (int) $cross_reference['source_id'];
        $target_type                 = $cross_reference['target_type'];
        $get_uri_from_crossreference = new GetUriFromCrossReference($source_id, $target_type);

        $event_manager->processEvent($get_uri_from_crossreference);
        if ($get_uri_from_crossreference->getUri() !== null) {
            $GLOBALS['Response']->redirect($get_uri_from_crossreference->getUri());
        }
    }
    $GLOBALS['Response']->sendStatusCode(404);
    exit_error(
        $GLOBALS['Language']->getText('global', 'error'),
        $GLOBALS['Language']->getText('include_exit', 'missing_param_err')
    );
}

if ($request->isAjax()) {
    $html_purifier = Codendi_HTMLPurifier::instance();
    switch ($ref->getServiceShortName()) {
        case 'svn':
            require_once __DIR__ . '/../www/svn/svn_data.php';
            $group_id = $request->get('group_id');
            $rev_id   = $request->get('val');
            $result   = svn_data_get_revision_detail($group_id, 0, $rev_id);
            $date     = format_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($result, 0, 'date'));

            $description = db_result($result, 0, 'description');
            $description = htmlspecialchars_decode($description, ENT_QUOTES);
            $list_log    = util_line_wrap($description);

            echo '<table>';
            echo ' <tr>';
            echo '  <td><strong>' . $GLOBALS['Language']->getText('svn_utils', 'date') . ':</strong></td>';
            echo '  <td>' . $html_purifier->purify($date) . '</td>';
            echo ' </tr>';
            echo ' <tr>';
            echo '  <td><strong>' . $GLOBALS['Language']->getText('svn_browse_revision', 'log_message') . ':</strong></td>';
            echo '  <td>' . $html_purifier->purify($list_log) . '</td>';
            echo ' </tr>';
            echo '</table>';
            break;
        case 'cvs':
            require_once __DIR__ . '/../www/cvs/commit_utils.php';
            $commit_id = $request->get('val');
            $result    =  cvs_get_revision_detail($commit_id);
            if (db_numrows($result) < 1) {
                echo sprintf(_('Commit #%1$s not found in this project'), $commit_id);
            } else {
                $date     = uniformat_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($result, 0, 'c_when'));
                $list_log = util_line_wrap(db_result($result, 0, 'description'), $group_id);
                echo '<table>';
                echo ' <tr>';
                echo '  <td><strong>' . _('Date') . ':</strong></td>';
                echo '  <td>' . $html_purifier->purify($date) . '</td>';
                echo ' </tr>';
                echo ' <tr>';
                echo '  <td><strong>' . _('Log message') . ':</strong></td>';
                echo '  <td>' . $html_purifier->purify($list_log) . '</td>';
                echo ' </tr>';
                echo '</table>';
            }
            break;
        case 'file':
            $group_id = $request->get('group_id');
            switch ($ref->getNature()) {
                case ReferenceManager::REFERENCE_NATURE_RELEASE:
                    $rf         = new FRSReleaseFactory();
                    $release_id = $request->get('val');
                    $release    = $rf->getFRSReleaseFromDb($release_id);
                    $package_id = $release->getPackageID();
                    if ($rf->userCanRead($group_id, $package_id, $release_id)) {
                        echo $release->getReferenceTooltip();
                    }
                    break;
                case ReferenceManager::REFERENCE_NATURE_FILE:
                    $ff         = new FRSFileFactory();
                    $file_id    = $request->get('val');
                    $file       = $ff->getFRSFileFromDb($file_id);
                    $rf         = new FRSReleaseFactory();
                    $release_id = $file->getReleaseID();
                    $release    = $rf->getFRSReleaseFromDb($release_id);
                    $package_id = $release->getPackageID();
                    if ($rf->userCanRead($group_id, $package_id, $release_id)) {
                        echo $file->getReferenceTooltip();
                    }
                    break;
            }
            break;
        default:
            $get_tooltip = new ReferenceGetTooltipChainJson(
                $event_manager,
                new JSONResponseBuilder(
                    HTTPFactoryBuilder::responseFactory(),
                    HTTPFactoryBuilder::streamFactory()
                ),
                new SapiEmitter()
            );
            $get_tooltip
                ->chain(new ReferenceGetTooltipChainLegacy($event_manager))
                ->chain(
                    new ReferenceGetTooltipChainOpenGraph(
                        $html_purifier,
                        new \Embed\Embed(
                            new \Embed\Http\Crawler(
                                HttpClientFactory::createClient(),
                                HTTPFactoryBuilder::requestFactory(),
                                HTTPFactoryBuilder::URIFactory()
                            )
                        )
                    )
                );
            $get_tooltip->process($ref, $project, $request->getCurrentUser(), $keyword, $request->get('val'));
            break;
    }
} else {
    $feed     = isset($feed) ? $feed : "";
    $location = "Location: " . $ref->getLink() . $feed;
    header($location);
    exit;
}
