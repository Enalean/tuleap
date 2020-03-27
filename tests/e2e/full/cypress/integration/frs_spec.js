/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

describe("Frs", function () {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.ProjectAdministratorLogin();
    });

    beforeEach(() => {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
        cy.visitProjectService("frs-project", "Files");
    });

    context("Frs packages", function () {
        it("can create a new package", function () {
            cy.get("[data-test=create-new-package]").click();
            cy.get("[data-test=frs-create-package]").type("My first package");
            cy.get("[data-test=frs-create-package-button]").click({
                timeout: 60000,
            });

            cy.visitProjectService("frs-project", "Files");
            cy.get('[data-test="package-name"]').contains("My first package");
        });

        it("can update a package", function () {
            cy.get("[data-test=update-package]").click();
            cy.get("[data-test=frs-create-package]").type(" edited");
            cy.get("[data-test=frs-create-package-button]").click({
                timeout: 60000,
            });

            cy.visitProjectService("frs-project", "Files");
            cy.get('[data-test="package-name"]').contains("My first package edited");
        });

        it("can delete a package", function () {
            cy.get("[data-test=remove-package]").click({
                timeout: 60000,
            });

            cy.visitProjectService("frs-project", "Files");
            cy.get('[data-test="package-name"]').should("not.exist");
        });
    });

    context("Frs releases", function () {
        it("can create a new release", function () {
            cy.server();
            cy.get("[data-test=create-new-package]").click();
            cy.get("[data-test=frs-create-package]").type("Package to test release");
            cy.get("[data-test=frs-create-package-button]").click({
                timeout: 60000,
            });

            cy.visitProjectService("frs-project", "Files");

            cy.route("/file/admin/frsajax.php*").as("createRelease");
            cy.get("[data-test=create-release]").click({ force: true });
            cy.get("[data-test=release-name]").type("My release name");
            cy.get("[data-test=create-release-button]").click({
                timeout: 60000,
            });
            cy.wait("@createRelease", { timeout: 60000 });

            cy.visitProjectService("frs-project", "Files");
            cy.get('[data-test="release-name"]').contains("My release name");
        });

        it("can update a release", function () {
            cy.server();
            cy.visitProjectService("frs-project", "Files");

            cy.route("/file/admin/frsajax.php*").as("createRelease");
            cy.get("[data-test=edit-release]").click({ force: true });
            cy.get("[data-test=release-name]").type(" edited");
            cy.get("[data-test=create-release-button]").click({
                timeout: 60000,
            });

            cy.wait("@createRelease", { timeout: 60000 });

            cy.visitProjectService("frs-project", "Files");
            cy.get("[data-test=edit-release]").click({ force: true });
            cy.get('[data-test="release-name"]').should("have.value", "My release name edited");
        });

        it("can delete a release", function () {
            cy.get("[data-test=release-delete-button]").click({ force: true, timeout: 60000 });

            cy.visitProjectService("frs-project", "Files");
            cy.get('[data-test="release-name"]').should("not.exist");
        });
    });
});
