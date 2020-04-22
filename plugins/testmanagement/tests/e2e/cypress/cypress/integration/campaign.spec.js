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

describe("TTM campaign", () => {
    let ttm_project_name, ttm_project_public_name, now;

    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        now = Date.now();
    });

    beforeEach(function () {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
        cy.server();

        ttm_project_name = "test-ttm-" + now;
        ttm_project_public_name = "Test TTM " + now;
    });

    context("As project administrator", () => {
        before(() => {
            cy.ProjectAdministratorLogin();
        });

        after(() => {
            cy.userLogout();
        });

        it("Creates a project with TTM", () => {
            cy.visit("/project/new");
            cy.get(
                "[data-test=project-registration-card-label][for=project-registration-tuleap-template-agile_alm]"
            ).click();
            cy.get("[data-test=project-registration-next-button]").click();

            cy.get("[data-test=new-project-name]").type(ttm_project_public_name);
            cy.get("[data-test=project-shortname-slugified-section]").click();
            cy.get("[data-test=new-project-shortname]").type("{selectall}" + ttm_project_name);
            cy.get("[data-test=approve_tos]").click();
            cy.get("[data-test=project-registration-next-button]").click();
            cy.get("[data-test=start-working]").click({
                timeout: 20000,
            });
        });

        it("Adds user to project members", () => {
            cy.visitServiceInCurrentProject("Admin");
            cy.get("[data-test=admin-nav-members]").click();

            cy.get(
                "[data-test=project-admin-members-add-user-select] + .select2-container"
            ).click();
            cy.get(".select2-search__field").type("ProjectMember{enter}");
            cy.get(".select2-result-user").click();
            cy.get('[data-test="project-admin-submit-add-member"]').click();

            cy.get("[data-test=project-admin-submit-add-member]").click();
        });
    });

    context("As project member", () => {
        before(() => {
            cy.projectMemberLogin();
        });

        it("Creates a campaign", () => {
            cy.visitProjectService(ttm_project_name, "Test Management");
            cy.get("[data-test=new-campaign-button]").click();

            cy.get("[data-test=campaign-label]").type("My first campaign");
            cy.get("[data-test=choose-tests]").select("none");
            cy.get("[data-test=create-new-campaign-button]").click();

            cy.contains("My first campaign").click();
            cy.contains("There are no tests you can see.");
        });

        context("Within the campaign", () => {
            it("Adds a test", () => {
                cy.get("[data-test=edit-campaign-button]").click();

                cy.get("[data-test=campaign-label]").type(
                    "{selectall}My first campaign with tests"
                );
                cy.get("[data-test=add-test-button]").click();

                cy.get("[data-test=artifact-modal-field-summary]").type("My first test");
                cy.get("[data-test=artifact-modal-save-button]").click();
                cy.contains("1 test will be added");

                cy.get("[data-test=edit-campaign-save-button]").click();
            });
        });
    });
});
