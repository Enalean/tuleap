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

export type ReloadCallback = () => void;
export type ConditionPredicate = (
    number_of_attempts: number,
    max_attempts: number
) => PromiseLike<boolean>;

declare global {
    // Be consistent with Cypress declaration

    namespace Cypress {
        // Be consistent with Cypress declaration
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        interface Chainable<Subject> {
            clearSessionCookie(): void;

            preserveSessionCookies(): void;

            projectAdministratorLogin(): void;

            projectAdministratorSession(): void;

            projectMemberSession(): void;

            secondProjectAdministratorLogin(): void;

            projectMemberLogin(): void;

            permissionDelegationLogin(): void;

            platformAdminLogin(): void;

            restrictedMemberLogin(): void;

            restrictedRegularUserLogin(): void;

            regularUserLogin(): void;

            heisenbergLogin(): void;

            userLogout(): void;

            switchProjectVisibility(visibility: string): void;

            updatePlatformVisibilityAndAllowRestricted(): void;

            updatePlatformVisibilityForAnonymous(): void;

            getProjectId(project_shortname: string): Chainable<number>;

            visitProjectService(project_unixname: string, service_label: string): void;

            visitProjectAdministration(project_unixname: string): void;

            visitProjectAdministrationInCurrentProject(): void;

            visitServiceInCurrentProject(service_label: string): void;

            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            getFromTuleapAPI(url: string): Chainable<Response<any>>;

            postFromTuleapApi(url: string, payload: Record<string, unknown>): void;

            putFromTuleapApi(url: string, payload: Record<string, unknown>): void;

            reloadUntilCondition(
                reloadCallback: ReloadCallback,
                conditionCallback: ConditionPredicate,
                max_attempts_reached_message: string,
                number_of_attempts?: number
            ): PromiseLike<void>;

            createNewPublicProject(project_name: string, xml_template: string): void;

            createNewPrivateProject(project_name: string): void;

            addUser(user_name: string): void;
        }
    }
}
