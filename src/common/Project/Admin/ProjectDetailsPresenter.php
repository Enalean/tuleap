<?php
/**
 * Copyright (c) Enalean, 2016 - present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Project\Admin;

use EventManager;
use Project;
use ProjectManager;
use TemplateSingleton;
use Tuleap\Project\ProjectAccessPresenter;
use Tuleap\Project\Registration\Template\TemplateFactory;

class ProjectDetailsPresenter
{
    /**
     * Display additional links in admin » projects » project
     *
     * Parameters:
     *  - $project  => (in) Project
     *  - $links    => (out) Array of {href, label}
     */
    public const GET_MORE_INFO_LINKS = 'get_more_info_links';

    public $public_name;
    public $short_name;
    public $id;
    public $description;
    public $information_label;
    public $history_label;
    public $project_details_label;
    public $access_label;
    public $description_label;
    public $more_label;
    public $homepage_label;
    public $admin_label;
    public $members_label;
    public $pending_label;
    public $links;
    public $manage_access_label;
    public $unix_name;
    public $save_label;
    public $unix_name_label;
    public $status_label;
    public $status;
    public $can_change_status;
    public $type_label;
    public $type;
    public $instructions_label;
    public $send_email_label;
    public $custom_fields;
    public $has_custom_fields;
    public $instructions_desc;
    public $is_system;
    public $is_active;
    public $is_status_invalid;
    public readonly bool $has_suspension_warnings_to_display;
    /**
     * @var ProjectAccessPresenter
     */
    public $access_presenter;
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var array
     */
    public $built_from_project;
    public $built_from_xml_template;
    public $built_from_label;
    /**
     * @var bool
     */
    public $is_unix_name_editable;
    /**
     * @var string
     */
    public $not_editable_reason;

    /**
     * @param list<string> $plugin_suspended_and_not_blocked_warnings
     */
    public function __construct(
        Project $project,
        $all_custom_fields,
        ProjectAccessPresenter $access_presenter,
        \CSRFSynchronizerToken $csrf_token,
        public readonly array $plugin_suspended_and_not_blocked_warnings,
        bool $is_unix_name_editable,
        string $not_editable_reason,
    ) {
        $this->id                    = $project->getID();
        $this->public_name           = $project->getPublicName();
        $this->unix_name             = $project->getUnixNameMixedCase();
        $this->short_name            = $project->getUnixName();
        $this->description           = $project->getDescription();
        $this->is_system             = $project->getStatus() === Project::STATUS_SYSTEM;
        $this->is_active             = $project->isActive();
        $this->is_unix_name_editable = $is_unix_name_editable;
        $this->not_editable_reason   = $not_editable_reason;

        $this->is_status_invalid = ! array_key_exists(
            $project->getStatus(),
            $this->getAssignableProjectStatuses($project)
        );

        $this->links = [];
        EventManager::instance()->processEvent(
            self::GET_MORE_INFO_LINKS,
            ['project' => $project, 'links' => &$this->links]
        );

        $this->status            = $this->getStatus($project);
        $this->can_change_status = $project->getStatus() === Project::STATUS_DELETED;
        $this->types             = $this->getTypes($project);

        $template_factory = TemplateFactory::build();
        $xml_template     = $template_factory->getTemplateForProject($project);
        if ($xml_template) {
            $this->built_from_xml_template = [
                'name' => $xml_template->getId(),
            ];
        } else {
            $template                 = ProjectManager::instance()->getProject($project->getTemplate());
            $this->built_from_project = [
                'href' => '/admin/groupedit.php?group_id=' . $template->getID(),
                'name' => $template->getPublicname(),
            ];
        }

        $this->custom_fields     = $all_custom_fields;
        $this->has_custom_fields = count($this->custom_fields) > 0;

        $this->information_label                  = $GLOBALS['Language']->getText('admin_project', 'information_label');
        $this->history_label                      = $GLOBALS['Language']->getText('admin_project', 'history_label');
        $this->project_details_label              = $GLOBALS['Language']->getText('admin_project', 'project_details_label');
        $this->access_label                       = $GLOBALS['Language']->getText('admin_project', 'access_label');
        $this->description_label                  = $GLOBALS['Language']->getText('admin_project', 'description_label');
        $this->more_label                         = $GLOBALS['Language']->getText('admin_project', 'more_label');
        $this->homepage_label                     = $GLOBALS['Language']->getText('admin_project', 'homepage_label');
        $this->admin_label                        = $GLOBALS['Language']->getText('admin_project', 'admin_label');
        $this->members_label                      = $GLOBALS['Language']->getText('admin_project', 'members_label');
        $this->pending_label                      = $GLOBALS['Language']->getText('admin_project', 'pending_label');
        $this->manage_access_label                = $GLOBALS['Language']->getText('admin_project', 'manage_access_label');
        $this->unix_name_label                    = $GLOBALS['Language']->getText('admin_project', 'unix_name_label');
        $this->save_label                         = $GLOBALS['Language']->getText('admin_project', 'save_label');
        $this->status_label                       = $GLOBALS['Language']->getText('admin_project', 'status_label');
        $this->type_label                         = _('Project Type');
        $this->built_from_label                   = _('Built from template');
        $this->instructions_label                 = $GLOBALS['Language']->getText('admin_project', 'instructions_label');
        $this->send_email_label                   = $GLOBALS['Language']->getText('admin_project', 'send_email_label');
        $this->instructions_desc                  = $GLOBALS['Language']->getText('admin_project', 'instructions_desc');
        $this->access_presenter                   = $access_presenter;
        $this->csrf_token                         = $csrf_token;
        $this->has_suspension_warnings_to_display = $project->getStatus() === Project::STATUS_SUSPENDED && count($this->plugin_suspended_and_not_blocked_warnings) > 0;
    }

    private function getTypes(Project $project): array
    {
        $localized_types = TemplateSingleton::instance()->getLocalizedTypes();

        $types = [];
        foreach ($localized_types as $id => $type) {
            $types[] = [
                'key'        => $id,
                'type'       => $type,
                'is_current' => $id == $project->getType(),
            ];
        }

        return $types;
    }

    private function getStatus(Project $project): array
    {
        $labels = $this->getAssignableProjectStatuses($project);

        $all_status = [];
        foreach ($labels as $key => $status) {
            $all_status[] = [
                'key'             => $key,
                'status'          => $status,
                'is_current'      => $project->getStatus() === $key,
                'can_be_selected' => ! ((int) $project->getID() === Project::DEFAULT_ADMIN_PROJECT_ID && $key === Project::STATUS_DELETED),
            ];
        }

        return $all_status;
    }

    private function getAssignableProjectStatuses(Project $project): array
    {
        $status = [
            Project::STATUS_ACTIVE => _('Active'),
            Project::STATUS_SUSPENDED => _('Suspended'),
            Project::STATUS_DELETED => _('Deleted'),
        ];

        if ($project->getStatus() === Project::STATUS_PENDING) {
            $status[Project::STATUS_PENDING] = _('Pending');
        }

        return $status;
    }
}
