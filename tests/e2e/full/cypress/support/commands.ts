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
            ProjectAdministratorLogin(): void;
            projectMemberLogin(): void;
            platformAdminLogin(): void;
            RestrictedMemberLogin(): void;
            RestrictedRegularUserLogin(): void;
            heisenbergLogin(): void;
            userLogout(): void;
            updatePlatformVisibilityAndAllowRestricted(): void;
            getProjectId(project_shortname: string): Chainable<JQuery<HTMLElement>>;
            visitProjectService(project_unixname: string, service_label: string): void;
            uploadFixtureFile(
                file_name: string,
                file_type: string
            ): Chainable<JQuery<HTMLInputElement>>;
            visitServiceInCurrentProject(service_label: string): void;
            getFromTuleapAPI(url: string): Chainable<Response>;
            postFromTuleapApi(url: string, payload: unknown): void;
            putFromTuleapApi(url: string, payload: unknown): void;
        }
    }
}

Cypress.Commands.add("ProjectAdministratorLogin", () => {
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

Cypress.Commands.add("RestrictedMemberLogin", () => {
    cy.visit("/");
    cy.get("[data-test=form_loginname]").type("RestrictedMember");
    cy.get("[data-test=form_pw]").type("Correct Horse Battery Staple{enter}");
});

Cypress.Commands.add("RestrictedRegularUserLogin", () => {
    cy.visit("/");
    cy.get("[data-test=form_loginname]").type("RestrictedRegularUser");
    cy.get("[data-test=form_pw]").type("Correct Horse Battery Staple{enter}");
});

Cypress.Commands.add("heisenbergLogin", () => {
    cy.visit("/");
    cy.get("[data-test=form_loginname]").type("heisenberg");
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

Cypress.Commands.add(
    "getProjectId",
    (project_shortname: string): Cypress.Chainable<JQuery<HTMLElement>> => {
        cy.visit(`/projects/${project_shortname}/`);
        return cy.get("[data-test=project-sidebar]").should("have.attr", "data-project-id");
    }
);

// Use this command to attach a file to a file input
// Don't forget to pass the filename in the arguments
// Also, the file has to be put under the fixtures folder
// see https://github.com/cypress-io/cypress/issues/170#issuecomment-609395903
Cypress.Commands.add(
    "uploadFixtureFile",
    {
        prevSubject: "element",
    },
    (input: JQuery<HTMLInputElement>, file_name: string, file_type: string): void => {
        cy.fixture(file_name).then((content) => {
            const blob = Cypress.Blob.base64StringToBlob(content, file_type);
            const test_file = new File([blob], file_name);
            const data_transfer = new DataTransfer();

            data_transfer.items.add(test_file);
            input[0].files = data_transfer.files;
            return input;
        });
    }
);

export {};
