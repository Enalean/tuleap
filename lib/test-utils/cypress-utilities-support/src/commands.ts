/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import type { ConditionPredicate, ReloadCallback } from "./commands-type-definitions";

Cypress.Commands.add("clearSessionCookie", () => {
    cy.clearCookie("__Host-TULEAP_session_hash");
});

Cypress.Commands.add("preserveSessionCookies", () => {
    Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
});

Cypress.Commands.add("projectAdministratorSession", () => {
    sessionThroughWebUI("ProjectAdministrator", "Correct Horse Battery Staple");
});

Cypress.Commands.add("projectMemberSession", () => {
    sessionThroughWebUI("ProjectMember", "Correct Horse Battery Staple");
});

Cypress.Commands.add("projectAdministratorLogin", () => {
    loginThroughWebUI("ProjectAdministrator", "Correct Horse Battery Staple");
});

Cypress.Commands.add("projectMemberLogin", () => {
    loginThroughWebUI("ProjectMember", "Correct Horse Battery Staple");
});

Cypress.Commands.add("platformAdminLogin", () => {
    loginThroughWebUI("admin", "welcome0");
});

Cypress.Commands.add("restrictedMemberLogin", () => {
    loginThroughWebUI("RestrictedMember", "Correct Horse Battery Staple");
});

Cypress.Commands.add("restrictedRegularUserLogin", () => {
    loginThroughWebUI("RestrictedRegularUser", "Correct Horse Battery Staple");
});

Cypress.Commands.add("permissionDelegationLogin", () => {
    loginThroughWebUI("PermissionDelegation", "Correct Horse Battery Staple");
});

Cypress.Commands.add("regularUserLogin", () => {
    loginThroughWebUI("RegularUser", "Correct Horse Battery Staple");
});

Cypress.Commands.add("heisenbergLogin", () => {
    loginThroughWebUI("heisenberg", "Correct Horse Battery Staple");
});

Cypress.Commands.add("secondProjectAdministratorLogin", () => {
    loginThroughWebUI("SecondProjectAdministrator", "Correct Horse Battery Staple");
});

function loginThroughWebUI(username: string, password: string): void {
    cy.visit("/");
    cy.get("[data-test=form_loginname]").type(username);
    cy.get("[data-test=form_pw]").type(`${password}{enter}`);
}

function sessionThroughWebUI(username: string, password: string): void {
    cy.session(["WebUI", username], () => {
        loginThroughWebUI(username, password);
    });
}

Cypress.Commands.add("userLogout", () => {
    cy.get("[data-test=user_logout]").click({ force: true });
});

interface CacheServiceUrls {
    [key: string]: string;
}

interface CacheProjectUrls {
    [key: string]: CacheServiceUrls;
}
const cache_service_urls: CacheProjectUrls = {};
Cypress.Commands.add(
    "visitProjectService",
    (project_unixname: string, service_label: string): void => {
        if (
            Object.prototype.hasOwnProperty.call(cache_service_urls, project_unixname) &&
            Object.prototype.hasOwnProperty.call(
                cache_service_urls[project_unixname],
                service_label
            )
        ) {
            cy.visit(cache_service_urls[project_unixname][service_label]);
            return;
        }

        cy.visit("/projects/" + project_unixname);
        visitServiceInCurrentProject(service_label, (href) => {
            cache_service_urls[project_unixname] = cache_service_urls[project_unixname] || {};
            cache_service_urls[project_unixname][service_label] = href;
        });
    }
);

Cypress.Commands.add("visitProjectAdministration", (project_unixname: string) => {
    cy.visit("/projects/" + project_unixname);
    cy.get('[data-test="project-administration-link"]', { includeShadowDom: true }).click();
});

Cypress.Commands.add("visitProjectAdministrationInCurrentProject", () => {
    cy.get('[data-test="project-administration-link"]', { includeShadowDom: true }).click();
});

Cypress.Commands.add("visitServiceInCurrentProject", (service_label: string) => {
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    visitServiceInCurrentProject(service_label, () => {});
});

function visitServiceInCurrentProject(
    service_label: string,
    before_visit_callback: (href: string) => void
): void {
    cy.get("[data-test=project-sidebar-tool]", { includeShadowDom: true })
        .contains("[data-test=project-sidebar-tool]", service_label, { includeShadowDom: true })
        .should("have.attr", "href")
        .then((href) => {
            before_visit_callback(String(href));
            cy.visit(String(href));
        });
}

Cypress.Commands.add("updatePlatformVisibilityAndAllowRestricted", (): void => {
    cy.platformAdminLogin();

    cy.get("[data-test=platform-administration-link]").click();
    cy.get("[data-test=global_access_right]").click({ force: true });

    cy.get("[data-test=access_mode-restricted]").check();

    cy.get("[data-test=update_forge_access_button]").click({ force: true });

    cy.get("[data-test=global-admin-search-user]").type("RestrictedMember{enter}");
    cy.get("[data-test=user-status]").select("Restricted");
    cy.get("[data-test=save-user]").click();

    cy.get("[data-test=global-admin-search-user]").type("RestrictedRegularUser{enter}");
    cy.get("[data-test=user-status]").select("Restricted");
    cy.get("[data-test=save-user]").click();

    cy.userLogout();
});

Cypress.Commands.add("updatePlatformVisibilityForAnonymous", (): void => {
    cy.platformAdminLogin();

    cy.get("[data-test=platform-administration-link]").click();
    cy.get("[data-test=global_access_right]").click({ force: true });

    cy.get("[data-test=access_mode-anonymous]").check();

    cy.get("[data-test=update_forge_access_button]").click({ force: true });

    cy.userLogout();
});

Cypress.Commands.add("getProjectId", (project_shortname: string): Cypress.Chainable<number> => {
    return cy
        .getFromTuleapAPI(
            `/api/projects?limit=1&query=${encodeURIComponent(
                JSON.stringify({ shortname: project_shortname })
            )}`
        )
        .then((response) => response.body[0].id);
});

Cypress.Commands.add("switchProjectVisibility", (visibility: string): void => {
    cy.get("[data-test=admin-nav-details]").click();
    cy.get("[data-test=project_visibility]").select(visibility);
    cy.get("[data-test=project-details-short-description-input]").type("My short description");
    cy.get("[data-test=project-details-submit-button]").click();
    cy.get("[data-test=term_of_service]").click({ force: true });

    cy.get("[data-test=project-details-submit-button]").click();
});

Cypress.Commands.add(
    "createNewIssueProject",
    (project_short_name: string, project_public_name: string): void => {
        cy.visit("/project/new");
        cy.get(
            "[data-test=project-registration-card-label][for=project-registration-tuleap-template-issues]"
        ).click();
        cy.get("[data-test=project-registration-next-button]").click();

        cy.get("[data-test=new-project-name]").type(project_public_name);
        cy.get("[data-test=project-shortname-slugified-section]").click();
        cy.get("[data-test=new-project-shortname]").type("{selectall}" + project_short_name);
        cy.get("[data-test=approve_tos]").click();
        cy.get("[data-test=project-registration-next-button]").click();
        cy.get("[data-test=start-working]").click({
            timeout: 20000,
        });
    }
);

const MAX_ATTEMPTS = 10;

Cypress.Commands.add(
    "reloadUntilCondition",
    (
        reloadCallback: ReloadCallback,
        conditionCallback: ConditionPredicate,
        max_attempts_reached_message: string,
        number_of_attempts = 0
    ): PromiseLike<void> => {
        if (number_of_attempts > MAX_ATTEMPTS) {
            throw new Error(max_attempts_reached_message);
        }
        return conditionCallback(number_of_attempts, MAX_ATTEMPTS).then(
            (is_condition_fulfilled) => {
                if (is_condition_fulfilled) {
                    return Promise.resolve();
                }

                cy.wait(100);
                reloadCallback();
                return cy.reloadUntilCondition(
                    reloadCallback,
                    conditionCallback,
                    max_attempts_reached_message,
                    number_of_attempts + 1
                );
            }
        );
    }
);

export {};
