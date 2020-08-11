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

export interface Campaign {
    readonly id: number;
    readonly label: string;
    readonly nb_of_notrun: number;
    readonly nb_of_blocked: number;
    readonly nb_of_failed: number;
    readonly nb_of_passed: number;
    readonly is_being_refreshed: boolean;
    readonly is_just_refreshed: boolean;
    readonly is_error: boolean;
}

interface ArtifactReference {
    readonly id: number;
}

interface TestExecutionUsedToDefineStatus extends ArtifactReference {
    submitted_on: string;
    submitted_by: {
        display_name: string;
    };
}

export interface BacklogItemFromREST {
    readonly id: number;
    readonly label: string;
    readonly short_type: string;
    readonly color: string;
    readonly artifact: ArtifactReference;
    readonly can_add_a_test: boolean;
}

export interface BacklogItem extends BacklogItemFromREST {
    readonly is_expanded: boolean;
    readonly is_just_refreshed: boolean;
    readonly are_test_definitions_loaded: boolean;
    readonly is_loading_test_definitions: boolean;
    readonly has_test_definitions_loading_error: boolean;
    readonly test_definitions: TestDefinition[];
}

interface TestDefinitionFromRESTWithNoStatusInformation {
    readonly id: number;
    readonly short_type: string;
    readonly summary: string;
    readonly automated_tests: string;
    readonly category: string | null;
}

export interface PlannedTestDefinitionFromREST
    extends TestDefinitionFromRESTWithNoStatusInformation {
    readonly test_status: "passed" | "failed" | "blocked" | "notrun";
    readonly test_execution_used_to_define_status: TestExecutionUsedToDefineStatus;
    readonly test_campaign_defining_status: ArtifactReference;
}

interface NotPlannedTestDefinitionFromREST extends TestDefinitionFromRESTWithNoStatusInformation {
    readonly test_status: null;
    readonly test_execution_used_to_define_status: null;
    readonly test_campaign_defining_status: null;
}

export type TestDefinitionFromREST =
    | PlannedTestDefinitionFromREST
    | NotPlannedTestDefinitionFromREST;

interface TestDefinitionRefreshInformation {
    readonly is_just_refreshed: boolean;
}

export type TestDefinition = TestDefinitionFromREST & TestDefinitionRefreshInformation;
