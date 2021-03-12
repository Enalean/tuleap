/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import type { ProjectFlag, ProjectPrivacy } from "@tuleap/vue-breadcrumb-privacy";

let project_public_name: string;
let project_short_name: string;
let project_privacy: ProjectPrivacy;
let project_flags: Array<ProjectFlag>;
let id_program: number;
let user_with_accessibility_mode: boolean;
let locale: string;
let does_user_can_create_increments: boolean;
let tracker_program_incrment_id: number;
let artifact_link_id: number | null;

export function build(
    public_name: string,
    short_name: string,
    privacy: ProjectPrivacy,
    flags: Array<ProjectFlag>,
    program_id: number,
    accessibility: boolean,
    user_locale: string,
    can_create_program_increment: boolean,
    program_increment_id: number,
    program_increment_artifact_link_id: number | null
): void {
    project_public_name = public_name;
    project_short_name = short_name;
    project_privacy = privacy;
    project_flags = flags;
    id_program = program_id;
    user_with_accessibility_mode = accessibility;
    locale = user_locale;
    does_user_can_create_increments = can_create_program_increment;
    tracker_program_incrment_id = program_increment_id;
    artifact_link_id = program_increment_artifact_link_id;
}

export function getProjectPublicName(): string {
    return project_public_name;
}

export function projectShortName(): string {
    return project_short_name;
}

export function projectPrivacy(): ProjectPrivacy {
    return project_privacy;
}

export function projectFlags(): Array<ProjectFlag> {
    return project_flags;
}

export function programId(): number {
    return id_program;
}

export function userHasAccessibilityMode(): boolean {
    return user_with_accessibility_mode;
}

export function getUserLocale(): string {
    return locale;
}

export function canCreateProgramIncrement(): boolean {
    return does_user_can_create_increments;
}

export function programIncrementId(): number {
    return tracker_program_incrment_id;
}

export function artifactLinkId(): number | null {
    return artifact_link_id;
}
