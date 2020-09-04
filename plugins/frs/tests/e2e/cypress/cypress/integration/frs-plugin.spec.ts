/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

describe("FRS plugin", () => {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.ProjectAdministratorLogin();
        cy.getProjectId("frs-plugin").as("frs_project_id");
    });

    beforeEach(() => {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
    });

    it("user can link a frs release to an agiledashboard release", function (): void {
        cy.server();

        cy.visit(`https://tuleap/plugins/agiledashboard/?group_id=${this.frs_project_id}`)
            .get("[data-test=release-id]")
            .should("have.attr", "data-artifact-id")
            .as("release_id")
            .then(function () {
                cy.visit(`/file/showfiles.php?group_id=${this.frs_project_id}`);

                cy.get("[data-test=create-new-package]").click();
                cy.get("[data-test=frs-create-package]").type("My first package");
                cy.get("[data-test=frs-create-package-button]").click({
                    timeout: 60000,
                });

                cy.route("/file/admin/frsajax.php*").as("createRelease");
                cy.get("[data-test=create-release]").click({ force: true });
                cy.get("[data-test=release-name]").type("My release name");
                cy.get("[data-test=release-artifact-id]").type(this.release_id);
                cy.get("[data-test=release-note]").type("My awesome RN");
                cy.get("[data-test=create-release-button]").click({
                    timeout: 60000,
                });
                cy.wait("@createRelease", { timeout: 60000 });

                cy.visitProjectService("frs-plugin", "Files");

                cy.visit(`/file/showfiles.php?group_id=${this.frs_project_id}`);
                cy.get(`[data-test=toggle-package]`).click();
                cy.get(`[data-test=release-note-access]`).click();
                cy.get("[data-test=release-note]").contains("My awesome RN");

                cy.get(`[data-test=linked-artifacts]`).click();

                cy.get("[data-test=frs-artifact-links]").within(() => {
                    cy.get(`[data-test=artifact]`).contains("bug 1");
                    cy.get(`[data-test=artifact]`).contains("bug 2");
                    cy.get(`[data-test=artifact]`).contains("reverse 1");
                    cy.get(`[data-test=artifact]`).contains("reverse 2");
                });
            });
    });
});
