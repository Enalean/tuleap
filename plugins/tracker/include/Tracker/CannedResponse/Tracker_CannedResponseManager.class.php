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

class Tracker_CannedResponseManager
{
    /**
     * @var Tracker
     */
    protected $tracker;

    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function process(TrackerManager $tracker_manager, $request, $current_user)
    {
        if ($request->get('create')) {
            if ($request->existAndNonEmpty('title') && $request->existAndNonEmpty('body')) {
                if (Tracker_CannedResponseFactory::instance()->create($this->tracker, $request->get('title'), $request->get('body'))) {
                    $GLOBALS['Response']->addFeedback('info', 'Created');
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?' . http_build_query(array(
                                                        'tracker' => (int) $this->tracker->id,
                                                        'func'    => 'admin-canned')));
                }
            }
        } elseif ($canned_id = (int) $request->get('delete')) {
            if (Tracker_CannedResponseFactory::instance()->delete($canned_id)) {
                $GLOBALS['Response']->addFeedback('info', 'Deleted');
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?' . http_build_query(array(
                                                    'tracker' => (int) $this->tracker->id,
                                                    'func'    => 'admin-canned')));
            }
        } elseif ($canned_id = (int) $request->get('update')) {
            if (Tracker_CannedResponseFactory::instance()->update($canned_id, $this->tracker, trim($request->get('title')), $request->get('body'))) {
                $GLOBALS['Response']->addFeedback('info', 'Updated');
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?' . http_build_query(array(
                                                    'tracker' => (int) $this->tracker->id,
                                                    'func'    => 'admin-canned')));
            }
        } elseif ($canned_id = (int) $request->get('edit')) {
            $this->displayAdminResponse($tracker_manager, $request, $current_user);
        }
        $this->displayAdminAllResponses($tracker_manager, $request, $current_user);
    }

    protected function displayAdminAllResponses(TrackerManager $tracker_manager, $request, $current_user)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $title = dgettext('tuleap-tracker', 'Canned responses');

        $this->tracker->displayAdminItemHeader($tracker_manager, 'editcanned', $title);

        echo '<h2 class="almost-tlp-title">' . $title . '</h2>';

        //Display existing responses
        $responses = Tracker_CannedResponseFactory::instance()->getCannedResponses($this->tracker);
        if (count($responses)) {
            echo '<h3>' . $GLOBALS['Language']->getText('plugin_tracker_include_canned', 'existing_responses') . '</h3>';

            echo '<table cellspacing="0" cellpadding="4" border="0">';
            $i = 0;
            foreach ($responses as $response) {
                echo '<tr class="' . util_get_alt_row_color($i++) . '" valign="top">';
                //title
                echo '<td><a href="' . TRACKER_BASE_URL . '/?' . http_build_query(array(
                                                        'tracker' => (int) $this->tracker->id,
                                                        'func'    => 'admin-canned',
                                                        'edit'    => (int) $response->id)) . '">';
                echo '<strong>' . $hp->purify($response->title, CODENDI_PURIFIER_CONVERT_HTML) . '</strong></a>';
                //excerpt
                echo '<pre>' . $hp->purify(substr($response->body, 0, 160), CODENDI_PURIFIER_CONVERT_HTML);
                echo strlen($response->body) > 160 ? '<b>...</b>' : '';
                echo '</pre>';

                echo '</td>';

                //delete
                echo '<td><a href="' . TRACKER_BASE_URL . '/?' . http_build_query(array(
                                                        'tracker' => (int) $this->tracker->id,
                                                        'func'    => 'admin-canned',
                                                        'delete'  => (int) $response->id)) . '" 
                             onClick="return confirm(\'' . addslashes($GLOBALS['Language']->getText('plugin_tracker_include_canned', 'delete_canned', $response->title)) . '\')">';
                echo $GLOBALS['HTML']->getImage('ic/cross.png');
                echo '</a></td></tr>';
            }
            echo '</table>';
        } else {
            echo '<h3>' . $GLOBALS['Language']->getText('plugin_tracker_include_canned', 'no_canned_response') . '</h3>';
        }

        //Display creation form
        echo '<h3>' . $GLOBALS['Language']->getText('plugin_tracker_include_canned', 'create_response') . '</h3>';
        echo '<p>';
        echo $GLOBALS['Language']->getText('plugin_tracker_include_canned', 'save_time');
        echo '<p>';
        echo '<form action="' . TRACKER_BASE_URL . '/?' . http_build_query(array(
                                                        'tracker' => (int) $this->tracker->id,
                                                        'func'    => 'admin-canned')) . '" 
                    method="POST">';
        echo '<b>' . $GLOBALS['Language']->getText('plugin_tracker_include_canned', 'title') . ':</b><br />';
        echo '<input type="text" name="title" value="" size="50">';
        echo '<p>';
        echo '<b>' . $GLOBALS['Language']->getText('plugin_tracker_include_canned', 'message_body') . '</b><br />';
        echo '<textarea name="body" rows="20" cols="65" wrap="hard"></textarea>';
        echo '<p>';
        echo '<input type="submit" name="create" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" />';
        echo '</form>';

        $this->tracker->displayFooter($tracker_manager);
    }

    protected function displayAdminResponse(TrackerManager $tracker_manager, $request, $current_user)
    {
        if ($response = Tracker_CannedResponseFactory::instance()->getCannedResponse($this->tracker, (int) $request->get('edit'))) {
            $hp = Codendi_HTMLPurifier::instance();
            $title = $GLOBALS['Language']->getText('plugin_tracker_admin_index', 'modify_cannedresponse');
            $this->tracker->displayAdminItemHeader(
                $tracker_manager,
                'editcanned',
                $title
            );
            //Display creation form
            echo '<h2 class="almost-tlp-title">' . $title . '</h2>';
            echo '<p>';
            echo $GLOBALS['Language']->getText('plugin_tracker_include_canned', 'save_time');
            echo '<p>';
            echo '<form action="' . TRACKER_BASE_URL . '/?' . http_build_query(array(
                                                            'tracker' => (int) $this->tracker->id,
                                                            'func'    => 'admin-canned',
                                                            'update'  => (int) $response->id)) . '" 
                        method="POST">';
            echo '<b>' . $GLOBALS['Language']->getText('plugin_tracker_include_canned', 'title') . ':</b><br />';
            echo '<input type="text" name="title" value="' . $hp->purify($response->title, CODENDI_PURIFIER_CONVERT_HTML) . '" size="50">';
            echo '<p>';
            echo '<b>' . $GLOBALS['Language']->getText('plugin_tracker_include_canned', 'message_body') . '</b><br />';
            echo '<textarea name="body" rows="20" cols="65" wrap="hard">' . $hp->purify($response->body, CODENDI_PURIFIER_CONVERT_HTML) . '</textarea>';
            echo '<p>';
            echo '<input type="submit" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" />';
            echo '</form>';
            echo '<p><a href="' . TRACKER_BASE_URL . '/?' . http_build_query(array(
                                                            'tracker' => (int) $this->tracker->id,
                                                            'func'    => 'admin-canned')) . '">&laquo; Go back to canned responses</a></p>';
            $this->tracker->displayFooter($tracker_manager);
            exit;
        }
    }
}
