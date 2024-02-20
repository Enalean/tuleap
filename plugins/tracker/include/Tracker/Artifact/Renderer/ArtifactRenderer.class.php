<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Renderer\BuildArtifactFormActionEvent;

/**
 * I'm responsible of rendering artifact to user (creation, update, view...)
 */
abstract class Tracker_Artifact_ArtifactRenderer
{
    /**
     * @var Tracker
     */
    protected $tracker;

    /**
     * @var EventManager
     */
    protected $event_manager;

    /**
     * @var Tracker_Artifact_Redirect
     */
    protected $redirect;

    public function __construct(Tracker $tracker, EventManager $event_manager)
    {
        $this->tracker            = $tracker;
        $this->event_manager      = $event_manager;
        $this->redirect           = new Tracker_Artifact_Redirect();
        $this->redirect->base_url = TRACKER_BASE_URL;
    }

    /**
     * Render artifact form
     *
     */
    public function display(Codendi_Request $request, PFUser $current_user)
    {
        $this->enhanceRedirect($request);

        $content  = $this->fetchFormContent($request, $current_user);
        $content .= $this->fetchRulesAsJavascript();

        $this->displayHeader();
        echo $content;
        $this->displayFooter();
    }

    /**
     * Render everything before artifact form
     */
    abstract protected function displayHeader();

    /**
     * Render everything after artifact form
     */
    abstract protected function displayFooter();

    /**
     * Render artifact form content
     *
     */
    abstract protected function fetchFormContent(Codendi_Request $request, PFUser $current_user);

    /**
     * @return string The HTML code for artifact fields
     */
    public function fetchFields(Artifact $artifact, array $submitted_values)
    {
        return $artifact->getTracker()->fetchFormElements($artifact, $submitted_values);
    }

    public function fetchFieldsForCopy(Artifact $artifact)
    {
        return $artifact->getTracker()->fetchFormElementsForCopy($artifact, []);
    }

    /**
     * Returns HTML code to display the submit buttons
     *
     * @return string The HTML code for submit buttons
     */
    public function fetchSubmitButton(PFUser $current_user)
    {
        return '<div class="hidden-artifact-submit-button">
                    <input type="hidden" id="submit-type" />
                    <div class="tracker-artifact-submit-buttons-bar">
                        <button type="submit" name="submit_and_stay" class="btn btn-large btn-outline-primary">' . $GLOBALS['Language']->getText('global', 'btn_submit_and_stay') . '</button>
                        <button class="btn btn-large btn-primary" type="submit">' . $GLOBALS['Language']->getText('global', 'btn_submit') . '</button>
                    </div>
                    <div class="btn-group dropup tracker-artifact-submit-buttons-bar-condensed">
                        <button class="btn btn-large btn-primary" type="submit" data-test="artifact-submit">' . $GLOBALS['Language']->getText('global', 'btn_submit') . '</button>
                        <button class="btn btn-large btn-primary dropdown-toggle artifact-submit-options" data-toggle="dropdown" data-test="artifact-submit-options">
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <input type="submit"
                                        name="submit_and_stay"
                                        data-test="artifact-submit-and-stay"
                                        class="btn btn-link"
                                        value="' . $GLOBALS['Language']->getText('global', 'btn_submit_and_stay') . '" />
                            </li>
                        </ul>
                    </div>' . $this->getConcurrentEditMessage() . '
                </div>';
    }

    protected function getConcurrentEditMessage()
    {
        return '<div id="artifact-submit-keeper-message">
                    <span class="help_title">' . dgettext('tuleap-tracker', 'Warning: concurrent edit') . '</span>
                    ' . dgettext('tuleap-tracker', '<p>Someone updated this artifact while you were editing it.</p><p>Please acknowledge all notifications before submit.</p><p>Beware, on submit, your modifications will be applied on top of previous changes.</p>') . '
                </div>';
    }

    /**
     * Return HTML code to display an input for anonymous user email
     *
     * @return string
     */
    public function fetchAnonymousEmailForm()
    {
        $html  = '<p>';
        $html .= sprintf(dgettext('tuleap-tracker', '<strong><span class="highlight">You Are NOT Logged In<br />Please <a href="%1$s">log in,</a> so update notifications can be emailed to you.</span></strong><br />If you don\'t have a user account, then enter your email address instead:'), '/account/login.php?return_to=' . urlencode($_SERVER['REQUEST_URI']));
        $html .= '<br />';
        $html .= '<input type="text" name="email" id="email" size="50" maxsize="100" />';
        $html .= '</p>';
        return $html;
    }

    public function fetchArtifactForm($html)
    {
        return '
        <form action="' . $this->redirect->toUrl() . '" method="POST" enctype="multipart/form-data" class="artifact-form">
            ' . $html . '
        </form>';
    }

    protected function fetchRulesAsJavascript()
    {
        return $this->tracker->displayRulesAsJavascript();
    }

    protected function enhanceRedirect(Codendi_Request $request)
    {
        $this->event_manager->processEvent(new BuildArtifactFormActionEvent($request, $this->redirect));
    }
}
