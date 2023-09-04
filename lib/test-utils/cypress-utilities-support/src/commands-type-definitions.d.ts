/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { ArtifactCreationPayload } from "./commands";
export { WEB_UI_SESSION } from "./commands";

export type ReloadCallback = () => void;
export type ConditionPredicate = (
    number_of_attempts: number,
    max_attempts: number,
) => PromiseLike<boolean>;

declare global {
    // Be consistent with Cypress declaration

    namespace Cypress {
        // Be consistent with Cypress declaration
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        interface Chainable<Subject> {
            projectAdministratorSession(): void;

            projectMemberSession(): void;
            siteAdministratorSession(): void;
            anonymousSession(): void;
            regularUserSession(): void;

            restrictedMemberSession(): void;

            restrictedRegularUserSession(): void;

            switchProjectVisibility(visibility: string): void;

            updatePlatformVisibilityAndAllowRestricted(): void;

            updatePlatformVisibilityForAnonymous(): void;

            getProjectId(project_shortname: string): Chainable<number>;

            visitProjectService(project_unixname: string, service_label: string): void;

            visitProjectAdministration(project_unixname: string): void;

            visitProjectAdministrationInCurrentProject(): void;

            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            getFromTuleapAPI(url: string): Chainable<Response<any>>;

            postFromTuleapApi(
                url: string,
                payload: Record<string, unknown>,
                // eslint-disable-next-line @typescript-eslint/no-explicit-any
            ): Chainable<Response<any>>;

            putFromTuleapApi(url: string, payload: Record<string, unknown>): void;

            patchFromTuleapAPI(url: string, payload: Record<string, unknown>): void;

            reloadUntilCondition(
                reloadCallback: ReloadCallback,
                conditionCallback: ConditionPredicate,
                max_attempts_reached_message: string,
                number_of_attempts?: number,
            ): PromiseLike<void>;

            createNewPublicProject(project_name: string, xml_template: string): Chainable<number>;

            createNewPrivateProject(project_name: string): void;

            addProjectMember(user_name: string): void;

            removeProjectMember(user_name: string): void;

            getTrackerIdFromREST(project_id: number, tracker_name: string): Chainable<number>;

            createArtifact(payload: ArtifactCreationPayload): Chainable<number>;

            createFRSPackage(project_id: number, package_name: string): void;

            getContains(selector: string, label: string): Chainable<JQuery<HTMLElement>>;

            searchItemInLazyboxDropdown(
                query: string,
                dropdown_item_label: string,
            ): Chainable<JQuery<HTMLElement>>;

            searchItemInListPickerDropdown(
                dropdown_item_label: string,
            ): Chainable<JQuery<HTMLElement>>;

            assertUserMessagesReceivedByWithSpecificContent(
                email: string,
                specific_content_of_mail: string,
            ): void;
        }
    }
}
