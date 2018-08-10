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

describe("Frs", function() {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.login();
    });

    beforeEach(() => {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");

        cy.loadProjectConfig();
    });

    context("Frs packages", function() {
        it("can create a new package", function() {
            cy.visit("/file/admin/package.php?func=add&group_id=" + this.projects.frs_project_id);

            cy.get("[data-test=frs-create-package]").type("My first package");
            cy.get("[data-test=frs-create-package-button]").click();
            cy.get("[data-test=feedback]").contains("Added Package");
        });

        it("can update a package", function() {
            cy.visit("/file/showfiles.php?group_id=" + this.projects.frs_project_id);

            cy.get("[data-test=update-package]").click();
            cy.get("[data-test=frs-create-package]").type(" edited");
            cy.get("[data-test=frs-create-package-button]").click();
            cy.get("[data-test=feedback]").contains("Updated Package");
        });
    });

    context("Frs releases", function() {
        it("can create a new release", function() {
            cy.visit("/file/showfiles.php?group_id=" + this.projects.frs_project_id);

            cy.get("[data-test=create-release]").click({ force: true });
            cy.get("[data-test=release-name]").type("My release name");
            cy.get("[data-test=create-release-button]").click();

            cy.get("[data-test=feedback]").contains("Added Release");
        });

        it("can update a release", function() {
            cy.visit("/file/showfiles.php?group_id=" + this.projects.frs_project_id);

            cy.get("[data-test=edit-release]").click({ force: true });
            cy.get("[data-test=release-name]").type(" edited");
            cy.get("[data-test=create-release-button]").click();

            cy.get("[data-test=feedback]").contains("Updated Release");
        });

        it("can delete a release", function() {
            cy.visit("/file/showfiles.php?group_id=" + this.projects.frs_project_id);
            cy.get("[data-test=release-delete-button]").click({ force: true });

            cy.get("[data-test=feedback]").contains("Release Deleted");
        });
    });

    it("can delete a package", function() {
        cy.visit("/file/showfiles.php?group_id=" + this.projects.frs_project_id);

        cy.get("[data-test=remove-package]").click();
        cy.get("[data-test=feedback]").contains("Package Deleted");
    });
});
