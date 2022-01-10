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

declare global {
    // Be consistent with Cypress declaration
    // eslint-disable-next-line @typescript-eslint/no-namespace
    namespace Cypress {
        // Be consistent with Cypress declaration
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        interface Chainable<Subject> {
            clearSessionCookie(): void;
            preserveSessionCookies(): void;
            projectAdministratorLogin(): void;
            secondProjectAdministratorLogin(): void;
            projectMemberLogin(): void;
            permissionDelegationLogin(): void;
            platformAdminLogin(): void;
            restrictedMemberLogin(): void;
            restrictedRegularUserLogin(): void;
            regularUserLogin(): void;
            heisenbergLogin(): void;
            userLogout(): void;
            updatePlatformVisibilityAndAllowRestricted(): void;
            updatePlatformVisibilityForAnonymous(): void;
            getProjectId(project_shortname: string): Chainable<JQuery<HTMLElement>>;
            visitProjectService(project_unixname: string, service_label: string): void;
            visitProjectAdministration(project_unixname: string): void;
            visitProjectAdministrationInCurrentProject(): void;
            visitServiceInCurrentProject(service_label: string): void;
            // eslint-disable-next-line @typescript-eslint/no-explicit-any
            getFromTuleapAPI(url: string): Chainable<Response<any>>;
            postFromTuleapApi(url: string, payload: Record<string, unknown>): void;
            putFromTuleapApi(url: string, payload: Record<string, unknown>): void;
        }
    }
}

import "cypress-file-upload";

Cypress.Commands.add("clearSessionCookie", () => {
    cy.clearCookie("__Host-TULEAP_session_hash");
});

Cypress.Commands.add("preserveSessionCookies", () => {
    Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
});

Cypress.Commands.add("projectAdministratorLogin", () => {
    cy.visit("/");
    cy.get("[data-test=form_loginname]").type("ProjectAdministrator");
    cy.get("[data-test=form_pw]").type("Correct Horse Battery Staple{enter}");
});

Cypress.Commands.add("projectMemberLogin", () => {
    cy.visit("/");
    cy.get("[data-test=form_loginname]").type("ProjectMember");
    cy.get("[data-test=form_pw]").type("Correct Horse Battery Staple{enter}");
});

Cypress.Commands.add("platformAdminLogin", () => {
    cy.visit("/");

    cy.get("[data-test=form_loginname]").type("admin");
    cy.get("[data-test=form_pw]").type("welcome0{enter}");
});

Cypress.Commands.add("restrictedMemberLogin", () => {
    cy.visit("/");
    cy.get("[data-test=form_loginname]").type("RestrictedMember");
    cy.get("[data-test=form_pw]").type("Correct Horse Battery Staple{enter}");
});

Cypress.Commands.add("restrictedRegularUserLogin", () => {
    cy.visit("/");
    cy.get("[data-test=form_loginname]").type("RestrictedRegularUser");
    cy.get("[data-test=form_pw]").type("Correct Horse Battery Staple{enter}");
});

Cypress.Commands.add("permissionDelegationLogin", () => {
    cy.visit("/");
    cy.get("[data-test=form_loginname]").type("PermissionDelegation");
    cy.get("[data-test=form_pw]").type("Correct Horse Battery Staple{enter}");
});

Cypress.Commands.add("regularUserLogin", () => {
    cy.visit("/");
    cy.get("[data-test=form_loginname]").type("RegularUser");
    cy.get("[data-test=form_pw]").type("Correct Horse Battery Staple{enter}");
});

Cypress.Commands.add("heisenbergLogin", () => {
    cy.visit("/");
    cy.get("[data-test=form_loginname]").type("heisenberg");
    cy.get("[data-test=form_pw]").type("Correct Horse Battery Staple{enter}");
});

Cypress.Commands.add("secondProjectAdministratorLogin", () => {
    cy.visit("/");
    cy.get("[data-test=form_loginname]").type("SecondProjectAdministrator");
    cy.get("[data-test=form_pw]").type("Correct Horse Battery Staple{enter}");
});

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
    cy.get('[data-test="project-administration-link"]').click();
});

Cypress.Commands.add("visitProjectAdministrationInCurrentProject", () => {
    cy.get('[data-test="project-administration-link"]').click();
});

Cypress.Commands.add("visitServiceInCurrentProject", (service_label: string) => {
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    visitServiceInCurrentProject(service_label, () => {});
});

function visitServiceInCurrentProject(
    service_label: string,
    before_visit_callback: (href: string) => void
): void {
    cy.get("[data-test=project-sidebar]")
        .contains(service_label)
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

Cypress.Commands.add(
    "getProjectId",
    (project_shortname: string): Cypress.Chainable<JQuery<HTMLElement>> => {
        cy.visit(`/projects/${project_shortname}/`);
        return cy.get("[data-test=project-sidebar]").should("have.attr", "data-project-id");
    }
);

export {};
