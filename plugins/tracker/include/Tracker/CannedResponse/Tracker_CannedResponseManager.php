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
 */

use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\Tracker;

class Tracker_CannedResponseManager //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    /**
     * @var Tracker
     */
    protected $tracker;

    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function process(TrackerManager $tracker_manager, \Tuleap\HTTPRequest $request, PFUser $current_user): void
    {
        if (! $request->isPost()) {
            if ($request->get('edit') !== false) {
                $this->displayAdminResponse($tracker_manager, $request);
            }
            $GLOBALS['HTML']->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset(
                new IncludeAssets(__DIR__ . '/../../../scripts/tracker-admin/frontend-assets', '/assets/trackers/tracker-admin'),
                'canned-responses.js'
            ));
            $this->displayAdminAllResponses($tracker_manager);
            return;
        }
        $this->getCSRFTokenAdmin()->check();
        if ($request->get('create')) {
            if ($request->existAndNonEmpty('title') && $request->existAndNonEmpty('body')) {
                if (Tracker_CannedResponseFactory::instance()->create($this->tracker, $request->get('title'), $request->get('body'))) {
                    $GLOBALS['Response']->addFeedback('info', 'Created');
                    $GLOBALS['Response']->redirect($this->getURLAdmin());
                }
            }
        } elseif ($canned_id = (int) $request->get('delete')) {
            if (Tracker_CannedResponseFactory::instance()->delete($canned_id)) {
                $GLOBALS['Response']->addFeedback('info', 'Deleted');
                $GLOBALS['Response']->redirect($this->getURLAdmin());
            }
        } elseif ($canned_id = (int) $request->get('update')) {
            if (Tracker_CannedResponseFactory::instance()->update($canned_id, $this->tracker, trim($request->get('title')), $request->get('body'))) {
                $GLOBALS['Response']->addFeedback('info', 'Updated');
                $GLOBALS['Response']->redirect($this->getURLAdmin());
            }
        }

        $GLOBALS['Response']->redirect($this->getURLAdmin());
    }

    protected function displayAdminAllResponses(TrackerManager $tracker_manager): void
    {
        $hp    = Codendi_HTMLPurifier::instance();
        $title = dgettext('tuleap-tracker', 'Canned responses');

        $this->tracker->displayAdminItemHeaderBurningParrot($tracker_manager, 'editcanned', $title);

        echo '<div class="tlp-framed">';

        //Display existing responses
        echo '<section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">
                        ' . $hp->purify($title) . '
                    </h1>
                </div>
                <div class="tlp-pane-section">';

        echo '<p>';
        echo dgettext('tuleap-tracker', 'Creating canned responses can save a lot of time if you frequently give the same answers to your users.');
        echo '<p>';

        echo '<div class="tlp-table-actions">
            <button type="button" class="tlp-table-actions-element tlp-button-primary" data-target-modal-id="add-canned-response-modal" id="add-canned-response-modal-trigger">
                <i class="fa-solid fa-plus tlp-button-icon" aria-hidden="true"></i>
                ' . $hp->purify(dgettext('tuleap-tracker', 'Create a new response')) . '
            </button>
        </div>';

        $responses = Tracker_CannedResponseFactory::instance()->getCannedResponses($this->tracker);
        echo '<table class="tlp-table">
            <thead>
                <tr>
                    <th class="tracker-admin-canned-response-column">' . $hp->purify(_('Canned response')) . '</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>';
        if (count($responses) > 0) {
            $i = 0;
            foreach ($responses as $response) {
                echo '<tr>';
                //title
                echo '<td>';
                echo '<strong>' . $hp->purify($response->title, CODENDI_PURIFIER_CONVERT_HTML) . '</strong>';
                //excerpt
                echo '<pre>' . $hp->purify(mb_substr($response->body, 0, 160), CODENDI_PURIFIER_CONVERT_HTML);
                echo mb_strlen($response->body) > 160 ? '<b>...</b>' : '';
                echo '</pre>';

                echo '</td>';

                echo '<td class="tlp-table-cell-actions">';
                $confirmation_message = sprintf(dgettext('tuleap-tracker', 'Delete this Canned Response: %1$s?'), $response->title);
                echo '<form class="delete-canned-response" method="post" href="' . $this->getURLAdmin() . '" data-confirmation-message="' . $hp->purify($confirmation_message) . '">';
                echo '<input type="hidden" name="delete" value="' . $hp->purify($response->id) . '"/>';
                echo $this->getCSRFTokenAdmin()->fetchHTMLInput();

                echo '<a class="tlp-button-primary tlp-button-outline tlp-button-small tlp-table-cell-actions-button" href="' . \trackerPlugin::TRACKER_BASE_URL . '/?' . http_build_query([
                    'tracker' => (int) $this->tracker->id,
                    'func'    => 'admin-canned',
                    'edit'    => (int) $response->id,
                ]) . '">';
                echo '<i class="fa-solid fa-pencil tlp-button-icon" aria-hidden="true"></i>';
                echo $hp->purify(_('Edit'));
                echo '</a>';

                echo '<button class="tlp-table-cell-actions-button tlp-button-danger tlp-button-outline tlp-button-small" type="submit">';
                echo '<i class="fa-solid fa-trash-alt tlp-button-icon" aria-hidden="true"></i>';
                echo $hp->purify(_('Delete'));
                echo '</button>';

                echo '</form></td></tr>';
            }
        } else {
            echo '<tr><td colspan="2" class="tlp-table-cell-empty">' . dgettext('tuleap-tracker', 'No canned responses set up yet for this tracker') . '</td></tr>';
        }
        echo '        </tbody>
                    </table>
                </div>
            </div>
        </section>';

        //Display creation form
        $url = \trackerPlugin::TRACKER_BASE_URL . '/?' . http_build_query([
            'tracker' => (int) $this->tracker->id,
            'func'    => 'admin-canned',
        ]);
        echo '<form action="' . $url . '"
            method="POST"
            class="tlp-modal"
            id="add-canned-response-modal"
            role="dialog"
            aria-labelledby="add-canned-response-modal-title"
        >
            <div class="tlp-modal-header">
                <h1 class="tlp-modal-title" id="add-canned-response-modal-title">
                    ' . $hp->purify(dgettext('tuleap-tracker', 'Create a new response')) . '
                </h1>
            </div>
            <div class="tlp-modal-body">';
        echo $this->getCSRFTokenAdmin()->fetchHTMLInput();

        echo '<div class="tlp-form-element">';
        echo '<label class="tlp-label" for="title">
            ' . dgettext('tuleap-tracker', 'Title') . '
            <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
        </label>';
        echo '<input type="text" name="title" id="title" class="tlp-input" required value="">';
        echo '</div>';

        echo '<div class="tlp-form-element">';
        echo '<label class="tlp-label" for="body">
            ' . dgettext('tuleap-tracker', 'Message Body') . '
            <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
        </label>';
        echo '<textarea name="body" id="body" class="tlp-textarea" rows="20" required wrap="hard"></textarea>';
        echo '</div>';

        echo '</div>';
        echo '<div class="tlp-modal-footer">';
        echo '<button type="button" class="tlp-button-primary tlp-button-outline tlp-modal-action" data-dismiss="modal">';
        echo $hp->purify(_('Delete'));
        echo '</button>';
        echo '<input type="submit" class="tlp-button-primary tlp-modal-action" name="create" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" />';
        echo '</div>';
        echo '</form>';

        echo '</div>';

        $this->tracker->displayFooter($tracker_manager);
    }

    protected function displayAdminResponse(TrackerManager $tracker_manager, $request): void
    {
        if ($response = Tracker_CannedResponseFactory::instance()->getCannedResponse($this->tracker, (int) $request->get('edit'))) {
            $hp    = Codendi_HTMLPurifier::instance();
            $title = dgettext('tuleap-tracker', 'Modify Canned Response');
            $this->tracker->displayAdminItemHeaderBurningParrot(
                $tracker_manager,
                'editcanned',
                $title
            );
            echo '<div class="tlp-framed">';


            echo '<section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">
                        ' . $hp->purify($title) . '
                    </h1>
                </div>
                <div class="tlp-pane-section">';
            echo '<p><a href="' . \trackerPlugin::TRACKER_BASE_URL . '/?' . http_build_query([
                'tracker' => (int) $this->tracker->id,
                'func'    => 'admin-canned',
            ]) . '">&laquo; Go back to canned responses</a></p>';

            echo '<form action="' . \trackerPlugin::TRACKER_BASE_URL . '/?' . http_build_query([
                'tracker' => (int) $this->tracker->id,
                'func'    => 'admin-canned',
                'update'  => (int) $response->id,
            ]) . '" method="POST">';
            echo $this->getCSRFTokenAdmin()->fetchHTMLInput();

            echo '<div class="tlp-form-element">';
            echo '<label class="tlp-label" for="title">
                ' . dgettext('tuleap-tracker', 'Title') . '
                <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
            </label>';
            echo '<input type="text" name="title" id="title" class="tlp-input" required value="' . $hp->purify($response->title, CODENDI_PURIFIER_CONVERT_HTML) . '" size="50">';
            echo '</div>';

            echo '<div class="tlp-form-element">';
            echo '<label class="tlp-label" for="body">
                ' . dgettext('tuleap-tracker', 'Message Body') . '
                <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
            </label>';
            echo '<textarea name="body" id="body" class="tlp-textarea" rows="20" required wrap="hard">' . $hp->purify($response->body, CODENDI_PURIFIER_CONVERT_HTML) . '</textarea>';
            echo '</div>';

            echo '<div class="tlp-pane-section-submit">';
            echo '<input type="submit" class="tlp-button-primary"  value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" />';
            echo '</div>';
            echo '</form>';
            echo '</div>
                </div>
            </section>';

            echo '</div>';
            $this->tracker->displayFooter($tracker_manager);
            exit;
        }
    }

    private function getURLAdmin(): string
    {
        return \trackerPlugin::TRACKER_BASE_URL . '/?' . http_build_query([
            'tracker' => (int) $this->tracker->id,
            'func'    => 'admin-canned',
        ]);
    }

    private function getCSRFTokenAdmin(): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken($this->getURLAdmin());
    }
}
