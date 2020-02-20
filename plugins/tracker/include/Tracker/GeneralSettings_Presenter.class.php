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
        return $GLOBALS['Language']->getText('plugin_tracker_include_type', 'emailgateway');
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
        return $GLOBALS['Language']->getText('plugin_tracker_include_type', 'html_tags');
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
        return $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'name');
    }

    public function tracker_description_label()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'desc');
    }

    public function tracker_shortname_label()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_include_type', 'short_name');
    }

    public function tracker_instantiate_label()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_include_type', 'instantiate');
    }

    public function tracker_log_priority_changes()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_include_type', 'priority_changes');
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
        return $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'color');
    }

    public function tracker_color()
    {
        return $this->tracker->getColor()->getName();
    }

    public function preview_label()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'preview');
    }

    public function submit_instructions_label()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_include_type', 'submit_instr');
    }

    public function submit_instructions()
    {
        return $this->tracker->submit_instructions;
    }

    public function browse_instructions_label()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_include_type', 'browse_instr');
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
        return $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'reply_possible');
    }

    public function create_not_possible()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'create_not_possible');
    }

    public function semantic_ok()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'semantic_ok');
    }

    public function required_ok()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'required_ok');
    }

    public function semantic_ko()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'semantic_ko');
    }

    public function required_ko()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'required_ko');
    }
}
