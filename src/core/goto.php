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

use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Project\Event\GetUriFromCrossReference;
use Tuleap\Reference\ReferenceOpenGraph;
use Tuleap\Reference\ReferenceOpenGraphDispatcher;

$reference_manager = ReferenceManager::instance();
$request           = HTTPRequest::instance();

$vGroupId = new Valid_GroupId();
if (! $request->valid($vGroupId)) {
    $group_id = 100;
} else {
    $group_id = $request->get('group_id');
}

$vKey = new Valid_String('key');
$vKey->required();
$vVal = new Valid_String('val');
$vVal->required();
if ((!$request->valid($vKey)) || (!$request->valid($vVal))) {
    $GLOBALS['Response']->sendStatusCode(400);
    exit_error(
        $GLOBALS['Language']->getText('global', 'error'),
        $GLOBALS['Language']->getText('include_exit', 'missing_param_err')
    );
}

$keyword = $request->get('key');
$value   = $request->get('val');
$args    = explode("/", $value);

if ($keyword == 'wiki') {
    $wiki = new WikiDao();
    // Wiki Specific
    //If wiki page exists, we keep the value to handle 'toto/titi' wiki page
    //If wiki page does not exist, we check if there is a version number in the
    //wiki page name to handle 'toto/titi/1' (1 is the version number)
    if ($wiki->retrieveWikiPageId($value, $group_id) != null) {
        $args = array($value);
    } elseif (preg_match('%^(.*)/(\d+)$%', $val, $matches)) {
        $args = array($matches[1], $matches[2]);
    }
}

$project_manager = ProjectManager::instance();
$project         = $project_manager->getProject($group_id);

$ref = null;

$event_manager = EventManager::instance();
$event_manager->processEvent(
    Event::GET_REFERENCE,
    array(
        'reference_manager' => $reference_manager,
        'project'           => $project,
        'keyword'           => $keyword,
        'value'             => $value,
        'group_id'          => $group_id,
        'reference'         => &$ref,
    )
);
if ($ref === null) {
    if ($keyword == ReferenceManager::KEYWORD_ARTIFACT_LONG || $keyword == ReferenceManager::KEYWORD_ARTIFACT_SHORT) {
        $ref = $reference_manager->loadReferenceFromKeyword($keyword, $value);

        $event_manager->processEvent(
            Event::SET_ARTIFACT_REFERENCE_GROUP_ID,
            array(
                'artifact_id' => $value,
                'reference'   => &$ref
            )
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
        $source_id   = (int) $cross_reference['source_id'];
        $target_type = $cross_reference['target_type'];
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
        case 'tracker':
            $user_id = UserManager::instance()->getCurrentUser()->getId();
            $aid = $request->get('val');
            $sql = "SELECT group_artifact_id FROM artifact WHERE artifact_id= " . db_ei($aid);
            $result = db_query($sql);
            if (db_numrows($result) > 0) {
                $row = db_fetch_array($result);
                $atid = $row['group_artifact_id'];

                $at = new ArtifactType($project, $atid);
                $values = null;
                if (!$at->isError() && $at->isValid()) {
                    $art_field_fact = new ArtifactFieldFactory($at);
                    $ah = new ArtifactHtml($at, $aid);
                    $uh = new UserHelper();

                    $values = array();
                    foreach (array('summary', 'submitted_by', 'status_id') as $field_name) {
                        $field = $art_field_fact->getFieldFromName($field_name);
                        if ($field->userCanRead($group_id, $atid)) {
                            if ($field->isMultiSelectBox()) {
                                $field_value = $field->getValues($ah->getID());
                            } else {
                                $field_value = $ah->getValue($field_name);
                            }

                            $field_html = new ArtifactFieldHtml($field);

                            if ($field->getName() == 'submitted_by') {
                                $value = $html_purifier->purify($uh->getDisplayNameFromUserId($field_value));

                                $open_date = $art_field_fact->getFieldFromName($field_name);
                                if ($field->userCanRead($group_id, $atid)) {
                                    $value .= $html_purifier->purify(', ' . DateHelper::timeAgoInWords($ah->getValue('open_date')));
                                }
                            } else {
                                $value = $field_html->display($at->getID(), $field_value, false, false, true);
                            }

                            $html = $ah->_getFieldLabelAndValueForUser($group_id, $atid, $field, $user_id, true);
                            $values[] = '<tr><td>' . $field_html->labelDisplay() . '</td><td>' . $value . '</td></tr>';
                        }
                    }
                }

                if ($values && count($values)) {
                    echo '<table>';
                    echo implode('', $values);
                    echo '</table>';
                }
            }
            break;
        case 'svn':
            require_once __DIR__ . '/../www/svn/svn_data.php';
            $group_id = $request->get('group_id');
            $rev_id = $request->get('val');
            $result = svn_data_get_revision_detail($group_id, 0, $rev_id);
            $date = format_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($result, 0, 'date'));

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
            $result =  cvs_get_revision_detail($commit_id);
            if (db_numrows($result) < 1) {
                echo $GLOBALS['Language']->getText('cvs_detail_commit', 'error_notfound', array($commit_id));
            } else {
                $date = uniformat_date($GLOBALS['Language']->getText('system', 'datefmt'), db_result($result, 0, 'c_when'));
                $list_log = util_line_wrap(db_result($result, 0, 'description'), $group_id);
                echo '<table>';
                echo ' <tr>';
                echo '  <td><strong>' . $GLOBALS['Language']->getText('cvs_commit_utils', 'date') . ':</strong></td>';
                echo '  <td>' . $html_purifier->purify($date) . '</td>';
                echo ' </tr>';
                echo ' <tr>';
                echo '  <td><strong>' . $GLOBALS['Language']->getText('cvs_browse_commit', 'log_message') . ':</strong></td>';
                echo '  <td>' . $html_purifier->purify($list_log) . '</td>';
                echo ' </tr>';
                echo '</table>';
            }
            break;
        case 'file':
            $group_id = $request->get('group_id');
            switch ($ref->getNature()) {
                case ReferenceManager::REFERENCE_NATURE_RELEASE:
                    $rf = new FRSReleaseFactory();
                    $release_id = $request->get('val');
                    $release = $rf->getFRSReleaseFromDb($release_id);
                    $package_id = $release->getPackageID();
                    if ($rf->userCanRead($group_id, $package_id, $release_id)) {
                        echo $release->getReferenceTooltip();
                    }
                    break;
                case ReferenceManager::REFERENCE_NATURE_FILE:
                    $ff = new FRSFileFactory();
                    $file_id = $request->get('val');
                    $file = $ff->getFRSFileFromDb($file_id);
                    $rf = new FRSReleaseFactory();
                    $release_id = $file->getReleaseID();
                    $release = $rf->getFRSReleaseFromDb($release_id);
                    $package_id = $release->getPackageID();
                    if ($rf->userCanRead($group_id, $package_id, $release_id)) {
                        echo $file->getReferenceTooltip();
                    }
                    break;
            }
            break;
        default:
            $event = new \Tuleap\Reference\ReferenceGetTooltipContentEvent($ref, $project, $request->getCurrentUser(), $keyword, $request->get('val'));
            $event_manager->processEvent($event);
            $output = $event->getOutput();
            if ($output) {
                echo $output;
            } elseif ($ref->getNature() === ReferenceManager::REFERENCE_NATURE_OTHER) {
                $open_graph_dispatcher = new ReferenceOpenGraphDispatcher(
                    HttpClientFactory::createClient(),
                    HTTPFactoryBuilder::requestFactory()
                );
                echo (new ReferenceOpenGraph($html_purifier, $ref, $open_graph_dispatcher))->getContent();
            }
            break;
    }
} else {
    $feed = isset($feed) ? $feed : "";
    $location = "Location: " . $ref->getLink() . $feed;
    header($location);
    exit;
}
