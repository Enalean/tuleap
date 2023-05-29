<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Admin;

use Project;
use Tuleap\Project\Admin\Visibility\UpdateVisibilityStatus;

final class ProjectVisibilityOptionsForPresenterGenerator
{
    /**
     * @psalm-return array<array{value: string, label: string, selected: string, disabled: bool, title: string}>
     */
    public function generateVisibilityOptions(
        bool $does_platform_allow_restricted_users,
        UpdateVisibilityStatus $private_without_restricted_visibility_switch_status,
        string $current_project_visibility,
    ): array {
        if ($does_platform_allow_restricted_users) {
            return [
                [
                    'value'    => Project::ACCESS_PRIVATE_WO_RESTRICTED,
                    'label'    => _('Private'),
                    'selected' => ($current_project_visibility === Project::ACCESS_PRIVATE_WO_RESTRICTED) ? 'selected = "selected"' : '',
                    'disabled' => ! $private_without_restricted_visibility_switch_status->canSwitch(),
                    'title'    => ! $private_without_restricted_visibility_switch_status->canSwitch() ? $private_without_restricted_visibility_switch_status->getReason() : '',
                ],
                [
                    'value'    => Project::ACCESS_PRIVATE,
                    'label'    => _('Private incl. restricted'),
                    'selected' => ($current_project_visibility === Project::ACCESS_PRIVATE) ? 'selected = "selected"' : '',
                    'disabled' => false,
                    'title'    => '',
                ],
                [
                    'value'    => Project::ACCESS_PUBLIC,
                    'label'    => _('Public'),
                    'selected' => ($current_project_visibility === Project::ACCESS_PUBLIC) ? 'selected = "selected"' : '',
                    'disabled' => false,
                    'title'    => '',
                ],
                [
                    'value'    => Project::ACCESS_PUBLIC_UNRESTRICTED,
                    'label'    => _('Public incl. restricted'),
                    'selected' => ($current_project_visibility === Project::ACCESS_PUBLIC_UNRESTRICTED) ? 'selected = "selected"' : '',
                    'disabled' => false,
                    'title'    => '',
                ],
            ];
        }
        return [
            [
                'value'    => Project::ACCESS_PRIVATE,
                'label'    => _('Private'),
                'selected' => ($current_project_visibility === Project::ACCESS_PRIVATE) ? 'selected = "selected"' : '',
                'disabled' => false,
                'title'    => '',
            ],
            [
                'value'    => Project::ACCESS_PUBLIC,
                'label'    => _('Public'),
                'selected' => ($current_project_visibility === Project::ACCESS_PUBLIC) ? 'selected = "selected"' : '',
                'disabled' => false,
                'title'    => '',
            ],
        ];
    }
}
