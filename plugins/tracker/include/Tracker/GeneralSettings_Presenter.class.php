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

class Tracker_GeneralSettings_Presenter // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @var bool
     */
    public $has_excessive_shortname_length;
    /**
     * @var int
     */
    public $max_tracker_length;

    /**
     * @var string
     */
    public $action_url;

    /** @var bool */
    public $cannot_configure_instantiate_for_new_projects;
    /**
     * @var bool
     */
    public $is_in_new_dropdown;
    /**
     * @var bool
     */
    public $is_semantic_configured_for_insecure_emailgateway;
    /**
     * @var bool
     */
    public $are_required_fields_configured_for_insecure_emailgateway;
    /**
     * @var bool
     */
    public $is_insecure_emailgateway_properly_configured;
    /**
     * @var bool
     */
    public $enable_insecure_emailgateway;
    /**
     * @var bool
     */
    public $is_emailgateway_used;
    /**
     * @var Tracker_ColorPresenterCollection
     */
    public $colors;
    /**
     * @var string
     */
    public $tracker_name;
    /**
     * @var string
     */
    public $tracker_shortname;
    /**
     * @var string
     */
    public $tracker_description;
    /**
     * @var bool
     */
    public $is_instatiate_for_new_projects;
    /**
     * @var bool
     */
    public $is_log_priority_changes;
    /**
     * @var string
     */
    public $tracker_color;
    /**
     * @var string
     */
    public $submit_instructions;
    /**
     * @var string
     */
    public $browse_instructions;
    /**
     * @var string
     */
    public $submit_button;
    /**
     * @var string
     */
    public $tracker_emailgateway;
    /**
     * @var string
     */
    public $html_tags;
    /**
     * @var string
     */
    public $tracker_name_label;
    /**
     * @var string
     */
    public $tracker_description_label;
    /**
     * @var string
     */
    public $tracker_shortname_label;
    /**
     * @var string
     */
    public $tracker_instantiate_label;
    /**
     * @var string
     */
    public $tracker_log_priority_changes;
    /**
     * @var string
     */
    public $tracker_color_label;
    /**
     * @var string
     */
    public $preview_label;
    /**
     * @var string
     */
    public $submit_instructions_label;
    /**
     * @var string
     */
    public $browse_instructions_label;
    /**
     * @var string
     */
    public $reply_possible;
    /**
     * @var string
     */
    public $create_not_possible;
    /**
     * @var string
     */
    public $semantic_ok;
    /**
     * @var string
     */
    public $required_ok;
    /**
     * @var string
     */
    public $semantic_ko;
    /**
     * @var string
     */
    public $required_ko;

    public function __construct(
        Tracker $tracker,
        $action_url,
        Tracker_ColorPresenterCollection $color_presenter_collection,
        MailGatewayConfig $config,
        Tracker_ArtifactByEmailStatus $artifactbyemail_status,
        $cannot_configure_instantiate_for_new_projects,
        bool $is_in_new_dropdown
    ) {
        $this->action_url                                    = $action_url;
        $this->cannot_configure_instantiate_for_new_projects = $cannot_configure_instantiate_for_new_projects;
        $this->has_excessive_shortname_length                = strlen(
            $tracker->getItemName()
        ) > Tracker::MAX_TRACKER_SHORTNAME_LENGTH;
        $this->max_tracker_length                            = Tracker::MAX_TRACKER_SHORTNAME_LENGTH;

        $this->is_in_new_dropdown = $is_in_new_dropdown;

        $this->is_semantic_configured_for_insecure_emailgateway         = $artifactbyemail_status->isSemanticConfigured(
            $tracker
        );
        $this->are_required_fields_configured_for_insecure_emailgateway = $artifactbyemail_status->isRequiredFieldsConfigured(
            $tracker
        );
        $this->is_insecure_emailgateway_properly_configured             = $this->is_semantic_configured_for_insecure_emailgateway
            && $this->are_required_fields_configured_for_insecure_emailgateway;

        $this->enable_insecure_emailgateway   = $config->isInsecureEmailgatewayEnabled();
        $this->is_emailgateway_used           = $tracker->isEmailgatewayEnabled();
        $this->colors                         = $color_presenter_collection;
        $this->tracker_name                   = $tracker->getName();
        $this->tracker_shortname              = $tracker->getItemName();
        $this->tracker_description            = $tracker->getDescription();
        $this->is_instatiate_for_new_projects = (bool) $tracker->instantiate_for_new_projects;
        $this->is_log_priority_changes        = (bool) $tracker->log_priority_changes;
        $this->tracker_color                  = $tracker->getColor()->getName();
        $this->submit_instructions            = (string) $tracker->submit_instructions;
        $this->browse_instructions            = (string) $tracker->browse_instructions;

        $this->submit_button                = $GLOBALS['Language']->getText('global', 'save_change');
        $this->tracker_emailgateway         = dgettext('tuleap-tracker', 'Enable to create/reply to artifacts by mail');
        $this->html_tags                    = dgettext('tuleap-tracker', '(HTML tags allowed)');
        $this->tracker_name_label           = dgettext('tuleap-tracker', 'Name');
        $this->tracker_description_label    = dgettext('tuleap-tracker', 'Description');
        $this->tracker_shortname_label      = dgettext('tuleap-tracker', 'Short name');
        $this->tracker_instantiate_label    = dgettext('tuleap-tracker', 'Instantiate for new projects');
        $this->tracker_log_priority_changes = dgettext('tuleap-tracker', 'Log priority changes in follow-up comments');
        $this->tracker_color_label          = dgettext('tuleap-tracker', 'Color');
        $this->preview_label                = dgettext('tuleap-tracker', 'Preview:');
        $this->submit_instructions_label    = dgettext('tuleap-tracker', 'Submit instructions');
        $this->browse_instructions_label    = dgettext('tuleap-tracker', 'Browse instructions');
        $this->reply_possible               = dgettext('tuleap-tracker', 'Reply to artifact by mail is possible');
        $this->create_not_possible          = dgettext('tuleap-tracker', 'Create artifact by mail is <b>not</b> possible:');
        $this->semantic_ok                  = dgettext('tuleap-tracker', 'Semantic is properly configured');
        $this->required_ok                  = dgettext('tuleap-tracker', 'Required fields are properly configured');
        $this->semantic_ko                  = dgettext('tuleap-tracker', 'Semantic title and/or description are not configured');
        $this->required_ko                  = dgettext('tuleap-tracker', 'Other fields than title or description cannot be required');
    }
}
