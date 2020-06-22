<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;

class Tracker_GeneralSettings_Presenter
{
    /**
     * @var bool
     */
    public $has_excessive_shortname_length;
    /**
     * @var int
     */
    public $max_tracker_length;

    /** @var Tracker */
    private $tracker;

    public $action_url;

    /** @var Tracker_ColorPresenterCollection */
    private $color_presenter_collection;

    /** @var MailGatewayConfig */
    private $config;

    /** @var Tracker_ArtifactByEmailStatus */
    private $artifactbyemail_status;
    /** @var bool*/
    public $cannot_configure_instantiate_for_new_projects;

    public function __construct(
        Tracker $tracker,
        $action_url,
        Tracker_ColorPresenterCollection $color_presenter_collection,
        MailGatewayConfig $config,
        Tracker_ArtifactByEmailStatus $artifactbyemail_status,
        $cannot_configure_instantiate_for_new_projects
    ) {
        $this->tracker                    = $tracker;
        $this->action_url                 = $action_url;
        $this->color_presenter_collection = $color_presenter_collection;
        $this->config                     = $config;
        $this->artifactbyemail_status     = $artifactbyemail_status;
        $this->cannot_configure_instantiate_for_new_projects = $cannot_configure_instantiate_for_new_projects;
        $this->has_excessive_shortname_length = strlen($tracker->getItemName()) > Tracker::MAX_TRACKER_SHORTNAME_LENGTH;
        $this->max_tracker_length = Tracker::MAX_TRACKER_SHORTNAME_LENGTH;
    }

    public function is_insecure_emailgateway_properly_configured()
    {
        return $this->is_semantic_configured_for_insecure_emailgateway()
            && $this->are_required_fields_configured_for_insecure_emailgateway();
    }

    public function is_semantic_configured_for_insecure_emailgateway()
    {
        return $this->artifactbyemail_status->isSemanticConfigured($this->tracker);
    }

    public function are_required_fields_configured_for_insecure_emailgateway()
    {
        return $this->artifactbyemail_status->isRequiredFieldsConfigured($this->tracker);
    }

    public function enable_insecure_emailgateway()
    {
        return $this->config->isInsecureEmailgatewayEnabled();
    }

    public function tracker_emailgateway()
    {
        return dgettext('tuleap-tracker', 'Enable to create/reply to artifacts by mail');
    }

    public function is_emailgateway_used()
    {
        return $this->tracker->isEmailgatewayEnabled();
    }

    public function colors()
    {
        return $this->color_presenter_collection;
    }

    public function html_tags()
    {
        return dgettext('tuleap-tracker', '(HTML tags allowed)');
    }

    public function tracker_name()
    {
        return $this->tracker->getName();
    }

    public function tracker_shortname()
    {
        return $this->tracker->getItemName();
    }

    public function tracker_description()
    {
        return $this->tracker->getDescription();
    }

    public function tracker_name_label()
    {
        return dgettext('tuleap-tracker', 'Name');
    }

    public function tracker_description_label()
    {
        return dgettext('tuleap-tracker', 'Description');
    }

    public function tracker_shortname_label()
    {
        return dgettext('tuleap-tracker', 'Short name');
    }

    public function tracker_instantiate_label()
    {
        return dgettext('tuleap-tracker', 'Instantiate for new projects');
    }

    public function tracker_log_priority_changes()
    {
        return dgettext('tuleap-tracker', 'Log priority changes in follow-up comments');
    }

    public function is_instatiate_for_new_projects()
    {
        return $this->tracker->instantiate_for_new_projects;
    }

    public function is_log_priority_changes()
    {
        return $this->tracker->log_priority_changes;
    }

    public function tracker_color_label()
    {
        return dgettext('tuleap-tracker', 'Color');
    }

    public function tracker_color()
    {
        return $this->tracker->getColor()->getName();
    }

    public function preview_label()
    {
        return dgettext('tuleap-tracker', 'Preview:');
    }

    public function submit_instructions_label()
    {
        return dgettext('tuleap-tracker', 'Submit instructions');
    }

    public function submit_instructions()
    {
        return $this->tracker->submit_instructions;
    }

    public function browse_instructions_label()
    {
        return dgettext('tuleap-tracker', 'Browse instructions');
    }

    public function browse_instructions()
    {
        return $this->tracker->browse_instructions;
    }

    public function submit_button()
    {
        return $GLOBALS['Language']->getText('global', 'save_change');
    }

    public function reply_possible()
    {
        return dgettext('tuleap-tracker', 'Reply to artifact by mail is possible');
    }

    public function create_not_possible()
    {
        return dgettext('tuleap-tracker', 'Create artifact by mail is <b>not</b> possible:');
    }

    public function semantic_ok()
    {
        return dgettext('tuleap-tracker', 'Semantic is properly configured');
    }

    public function required_ok()
    {
        return dgettext('tuleap-tracker', 'Required fields are properly configured');
    }

    public function semantic_ko()
    {
        return dgettext('tuleap-tracker', 'Semantic title and/or description are not configured');
    }

    public function required_ko()
    {
        return dgettext('tuleap-tracker', 'Other fields than title or description cannot be required');
    }
}
