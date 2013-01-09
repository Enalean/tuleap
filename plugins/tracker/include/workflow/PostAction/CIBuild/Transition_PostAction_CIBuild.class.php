<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once(dirname(__FILE__) .'/../Transition_PostAction.class.php');
require_once 'common/Jenkins/Client.class.php';

class Transition_PostAction_CIBuild extends Transition_PostAction {

    const SHORT_NAME                          = 'ci_build';
    const XML_TAG_NAME                        = 'postaction_ci_build';
    const BUILD_PARAMETER_USER                = 'userId';
    const BUILD_PARAMETER_PROJECT_ID          = 'projectId';
    const BUILD_PARAMETER_ARTIFACT_ID         = 'artifactId';
    const BUILD_PARAMETER_TRACKER_ID          = 'trackerId';
    const BUILD_PARAMETER_TRIGGER_FIELD_VALUE = 'triggerFieldValue';
    
    /**
     * @var string Pattern to validate a job url
     */
    private $job_url_pattern = 'https?://.+';

    /**
     *
     * @var String job_name : name of the job to build
     */
    private $job_url;

    /**
     * Constructor
     *
     * @param Transition                   $transition The transition the post action belongs to
     * @param Integer                      $id         Id of the post action
     * @param String                       $host       host of the jenkins server
     * @param String                       $job_url   name of the job
     */
    public function __construct(Transition $transition, $id, $job_url, Jenkins_Client $client) {
        parent::__construct($transition, $id);
        $this->job_url   = $job_url;
        $this->ci_client = $client;
    }

    /** @return string */
    public function getJobUrl() {
        return $this->job_url;
    }

    /**
     * Get the shortname of the post action
     *
     * @return string
     */
    public function getShortName() {
        return self::SHORT_NAME;
    }

    /** @return string */
    public static function getLabel() {
        return $GLOBALS['Language']->getText('workflow_postaction', 'ci_build');
    }

    /** @return string html */
    public function fetch() {
        $html  = '';
        $title = $GLOBALS['Language']->getText('workflow_admin','ci_url');
        $text_field = '<input type="text"
            title="'. $title .'"
            required
            class="required"
            pattern="'. $this->job_url_pattern .'"
            name="workflow_postaction_ci_build['.$this->id.']"
            value="'. $this->getJobUrl() .'"
            size="50"
            maxsize="255" />';
        $html .= $GLOBALS['Language']->getText('workflow_admin', 'ci_build', array($text_field));
        return $html;
    }

    /** @return bool */
    public function isDefined() {
        return !empty($this->job_url);
    }

    public function process(Codendi_Request $request) {
        if ($request->getInArray('remove_postaction', $this->id)) {
            $this->getDao()->deletePostAction($this->id);
        } else {
            $value = $request->getInArray('workflow_postaction_ci_build', $this->id);
            $this->updateJobUrl($value);
        }
    }

    /**
     * Export postactions to XML
     *
     * @param SimpleXMLElement &$root     the node to which the postaction is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(&$root, $xmlMapping) {
        if ($this->isDefined()) {
            $child = $root->addChild(Transition_PostAction_CIBuild::XML_TAG_NAME);
            $child->addAttribute('job_url', $this->getJobUrl());
        }
    }

    /**
     * @see Transition_PostAction::after()
     * @param Tracker_Artifact_Changeset $changeset
     */
    public function after(Tracker_Artifact_Changeset $changeset) {
        if (! $this->isDefined()) {
            return;
        }

        $build_parameters = array(
            self::BUILD_PARAMETER_USER                => $changeset->getSubmittedBy(),
            self::BUILD_PARAMETER_PROJECT_ID          => $changeset->getArtifact()->getTracker()->getProject()->getID(),
            self::BUILD_PARAMETER_ARTIFACT_ID         => $changeset->getArtifact()->getId(),
            self::BUILD_PARAMETER_TRACKER_ID          => $changeset->getArtifact()->getTracker()->getId(),
            self::BUILD_PARAMETER_TRIGGER_FIELD_VALUE => $this->getTransition()->getFieldValueFrom()->getLabel(),
        );
        
        try {
            $this->ci_client->launchJobBuild($this->job_url, $build_parameters);
            $feedback = $GLOBALS['Language']->getText('workflow_postaction', 'ci_build_succeeded', array($this->job_url));
            $GLOBALS['Response']->addFeedback('info', $feedback);
        } catch (Jenkins_ClientUnableToLaunchBuildException $exception) {
            $GLOBALS['Response']->addFeedback('error', $exception->getMessage());
        }
    }

    /** @return Transition_PostAction_CIBuildDao */
    protected function getDao() {
        return new Transition_PostAction_CIBuildDao();
    }

    private function urlIsValid($url) {
        return preg_match("#$this->job_url_pattern#", $url) > 0;
    }

    private function updateJobUrl($new_job_url) {
        if ($new_job_url != $this->job_url) {
            if ($this->urlIsValid($new_job_url)) {
                $this->getDao()->updatePostAction($this->id, $new_job_url);
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('workflow_postaction', 'invalid_job_url', array($new_job_url)));
            }
        }
    }
}

?>
