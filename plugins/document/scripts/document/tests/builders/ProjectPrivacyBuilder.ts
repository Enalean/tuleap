/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { ProjectPrivacy } from "@tuleap/project-privacy-helper";

export const ProjectPrivacyBuilder = {
    private(): ProjectPrivacy {
        return {
            are_restricted_users_allowed: false,
            project_is_public_incl_restricted: false,
            project_is_private: true,
            project_is_public: false,
            project_is_private_incl_restricted: false,
            explanation_text:
                "Project privacy set to private. Only project members can access its content.",
            privacy_title: "Private",
        };
    },
};
