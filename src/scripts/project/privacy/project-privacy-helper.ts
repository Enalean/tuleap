/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

export interface ProjectPrivacy {
    readonly are_restricted_users_allowed: boolean;
    readonly project_is_private: boolean;
    readonly project_is_public_incl_restricted: boolean;
    readonly project_is_public: boolean;
    readonly project_is_private_incl_restricted: boolean;
    readonly explanation_text: string;
    readonly privacy_title: string;
}

export function getProjectPrivacyIcon(privacy: ProjectPrivacy): string {
    if (privacy.are_restricted_users_allowed) {
        if (privacy.project_is_public) {
            return "fa-lock-open";
        }
        if (privacy.project_is_public_incl_restricted) {
            return "fa-tlp-unlock-plus-r";
        }
        if (privacy.project_is_private) {
            return "fa-lock";
        }

        return "fa-tlp-lock-plus-r";
    }

    if (privacy.project_is_public) {
        return "fa-lock-open";
    }

    return "fa-lock";
}
