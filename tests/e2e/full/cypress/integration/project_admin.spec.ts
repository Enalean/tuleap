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

describe("Project admin", function () {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.ProjectAdministratorLogin();
    });

    beforeEach(() => {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
    });

    context("project basic administration", function () {
        it("should be able to create a new public project", function () {
            cy.visit("/project/new");

            cy.get(
                "[data-test=project-registration-card-label][for=project-registration-tuleap-template-issues]"
            ).click();

            cy.get("[data-test=project-registration-next-button").click();

            cy.get("[data-test=new-project-name]").type("project admin test");
            cy.get("[data-test=approve_tos]").check();

            cy.get("[data-test=project-registration-next-button]").click();
        });

        it("should be able to add users to a public project", function () {
            cy.visitProjectService("project-admin-test", "Admin");
            cy.contains("Members").click();

            cy.get(
                "[data-test=project-admin-members-add-user-select] + .select2-container"
            ).click();
            // ignore rule for select2
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-search__field").type("ProjectMember{enter}");
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-result-user").click();
            cy.get('[data-test="project-admin-submit-add-member"]').click();

            cy.get("[data-test=feedback]").contains("User added", {
                timeout: 40000,
            });
        });

        it("should verify that icon for project visibility is correct", function () {
            cy.visitProjectService("project-admin-test", "Admin");

            cy.get("[data-test=project-icon]").then(($icon) => {
                expect($icon[0].className).to.contains("fa-lock-open");
            });
        });

        it("should verify that a project administrator can enable a new service", () => {
            cy.visitProjectService("project-admin-test", "Admin");
            cy.contains("Services").click({ force: true });

            cy.get("[data-test=edit-service-plugin_svn]").click();

            cy.get("[data-test=service-edit-modal]").within(() => {
                cy.get("[data-test=service-is-used]").click();
                cy.get("[data-test=save-service-modifications]").click();
            });

            cy.get("[data-test=feedback]").contains("Successfully Updated Service", {
                timeout: 40000,
            });
        });
    });
});

context("Project member", function () {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.projectMemberLogin();
    });

    beforeEach(() => {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
    });

    it("should raise an error when user try to access to project admin page", function () {
        //here we don't care about project, member should not be admin of any project
        cy.visit("/project/admin/?group_id=101", { failOnStatusCode: false });

        cy.contains("You don't have permission to access administration of this project.");
    });
});
