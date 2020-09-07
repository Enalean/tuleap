<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Project;

use ForgeConfig;
use Project;

/**
 * @psalm-immutable
 */
final class ProjectPrivacyPresenter
{
    /**
     * @var bool
     */
    public $are_restricted_users_allowed;
    /**
     * @var bool
     */
    public $project_is_public_incl_restricted = false;
    /**
     * @var bool
     */
    public $project_is_private = false;
    /**
     * @var bool
     */
    public $project_is_public = false;
    /**
     * @var bool
     */
    public $project_is_private_incl_restricted = false;
    /**
     * @var string
     */
    public $explanation_text;
    /**
     * @var string
     */
    public $privacy_title;
    /**
     * @var string
     */
    public $project_name;

    private function __construct(
        Project $project,
        string $explanation_text,
        string $privacy_title
    ) {
        $this->project_is_public  = $project->isPublic();
        $this->project_is_private = ! $this->project_is_public;
        $this->project_name       = (string) $project->getPublicName();
        $this->explanation_text   = $explanation_text;
        $this->privacy_title      = $privacy_title;

        $this->are_restricted_users_allowed = ForgeConfig::areRestrictedUsersAllowed();
        if ($this->are_restricted_users_allowed) {
            $this->project_is_public                  = $project->getAccess() === Project::ACCESS_PUBLIC;
            $this->project_is_public_incl_restricted  = $project->getAccess() === Project::ACCESS_PUBLIC_UNRESTRICTED;
            $this->project_is_private                 = $project->getAccess() === Project::ACCESS_PRIVATE_WO_RESTRICTED;
            $this->project_is_private_incl_restricted = $project->getAccess() === Project::ACCESS_PRIVATE;
        }
    }

    public static function fromProject(Project $project): self
    {
        return new self($project, self::getExplanationText($project), self::getPrivacyTitle($project));
    }

    private static function getExplanationText(Project $project): string
    {
        if (ForgeConfig::areRestrictedUsersAllowed()) {
            switch ($project->getAccess()) {
                case Project::ACCESS_PUBLIC:
                    return _('Project privacy set to public.') . ' ' .
                        _('By default, its content is available to all authenticated, but not restricted, users.') . ' ' .
                        _('Please note that more restrictive permissions might exist on some items.');
                    break;
                case Project::ACCESS_PUBLIC_UNRESTRICTED:
                    return _('Project privacy set to public including restricted.') . ' ' .
                        _('By default, its content is available to all authenticated users.') . ' ' .
                        _('Please note that more restrictive permissions might exist on some items.');
                    break;
                case Project::ACCESS_PRIVATE_WO_RESTRICTED:
                    return _('Project privacy set to private.') . ' ' .
                        _('Only project members can access its content.') . ' ' .
                        _('Restricted users are not allowed in this project.');
                    break;
                default:
                    return _('Project privacy set to private including restricted.') . ' ' .
                        _('Only project members can access its content.') . ' ' .
                        _('Restricted users are allowed in this project.');
            }
        } elseif (ForgeConfig::areAnonymousAllowed()) {
            if ($project->isPublic()) {
                return _('Project privacy set to public.') . ' ' .
                    _('By default, its content is available to everyone (authenticated or not).') . ' ' .
                    _('Please note that more restrictive permissions might exist on some items.');
            }

            return _('Project privacy set to private.') . ' ' .
                _('Only project members can access its content.');
        } else {
            if ($project->isPublic()) {
                return _('Project privacy set to public.') . ' ' .
                    _('By default, its content is available to all authenticated, but not restricted, users.') . ' ' .
                    _('Please note that more restrictive permissions might exist on some items.');
            }

            return _('Project privacy set to private.') . ' ' .
                _('Only project members can access its content.');
        }
    }

    private static function getPrivacyTitle(Project $project): string
    {
        if (ForgeConfig::areRestrictedUsersAllowed()) {
            switch ($project->getAccess()) {
                case Project::ACCESS_PUBLIC:
                    return _('Public');
                    break;
                case Project::ACCESS_PUBLIC_UNRESTRICTED:
                    return _('Public including restricted');
                    break;
                case Project::ACCESS_PRIVATE_WO_RESTRICTED:
                    return _('Private');
                    break;
                default:
                    return _('Private including restricted');
            }
        } else {
            if ($project->isPublic()) {
                return _('Public');
            }

            return _('Private');
        }
    }
}
