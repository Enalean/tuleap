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

import { describe, it, expect } from "vitest";
import type { ProjectPrivacy } from "./project-privacy-helper";
import { getProjectPrivacyIcon } from "./project-privacy-helper";

describe("project-privacy-helper", () => {
    it.each([
        [
            {
                are_restricted_users_allowed: true,
                project_is_public: true,
                project_is_public_incl_restricted: false,
                project_is_private: false,
                project_is_private_incl_restricted: false,
            } as ProjectPrivacy,
            "fa-lock-open",
        ],
        [
            {
                are_restricted_users_allowed: true,
                project_is_public: false,
                project_is_public_incl_restricted: true,
                project_is_private: false,
                project_is_private_incl_restricted: false,
            } as ProjectPrivacy,
            "fa-tlp-unlock-plus-r",
        ],
        [
            {
                are_restricted_users_allowed: true,
                project_is_public: false,
                project_is_public_incl_restricted: false,
                project_is_private: true,
                project_is_private_incl_restricted: false,
            } as ProjectPrivacy,
            "fa-lock",
        ],
        [
            {
                are_restricted_users_allowed: true,
                project_is_public: false,
                project_is_public_incl_restricted: false,
                project_is_private: false,
                project_is_private_incl_restricted: true,
            } as ProjectPrivacy,
            "fa-tlp-lock-plus-r",
        ],
        [
            {
                are_restricted_users_allowed: false,
                project_is_public: true,
                project_is_public_incl_restricted: false,
                project_is_private: false,
                project_is_private_incl_restricted: false,
            } as ProjectPrivacy,
            "fa-lock-open",
        ],
        [
            {
                are_restricted_users_allowed: false,
                project_is_public: false,
                project_is_public_incl_restricted: false,
                project_is_private: true,
                project_is_private_incl_restricted: false,
            } as ProjectPrivacy,
            "fa-lock",
        ],
    ])(
        `returns the icon matching the project privacy`,
        (privacy: ProjectPrivacy, expected_icon: string) => {
            expect(getProjectPrivacyIcon(privacy)).toBe(expected_icon);
        },
    );
});
