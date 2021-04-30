/*
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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
    let project_id: string;

    context("Project administrators", function () {
        before(() => {
            cy.clearSessionCookie();
            cy.projectAdministratorLogin();
            cy.getProjectId("permissions-project-01").as("project_id");
        });

        beforeEach(() => {
            cy.preserveSessionCookies();
            cy.visitProjectService("frs-project", "Files");
        });

        it("can access to admin section", function () {
            project_id = this.project_id;
            cy.visit("/file/admin/?group_id=" + project_id + "&action=edit-permissions");
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
                cy.get("[data-test=create-new-package]").click();
                cy.get("[data-test=frs-create-package]").type("Package to test release");
                cy.get("[data-test=frs-create-package-button]").click({
                    timeout: 60000,
                });

                cy.visitProjectService("frs-project", "Files");

                cy.intercept({
                    url: /file\/admin\/frsajax\.php/,
                }).as("createRelease");
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
                cy.visitProjectService("frs-project", "Files");

                cy.intercept({
                    url: /file\/admin\/frsajax\.php/,
                }).as("createRelease");
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

        context("Hidden packages", function () {
            it("can create a new hidden package", function () {
                cy.get("[data-test=create-new-package]").click();
                cy.get("[data-test=frs-create-package]").type("My hidden package");
                cy.get("[data-test=status]").within(() => {
                    // the select is built with legacy `html_build_select_box_from_arrays` function
                    // the best way to retrieve it, is having a selector on div, and then get the select element inside
                    // eslint-disable-next-line cypress/require-data-selectors
                    cy.get("select").select("Hidden");
                });
                cy.get("[data-test=frs-create-package-button]").click({
                    timeout: 60000,
                });
            });
        });
    });

    context("Project members", function () {
        before(() => {
            cy.clearSessionCookie();
            cy.projectMemberLogin();
        });

        beforeEach(function () {
            cy.preserveSessionCookies();
        });

        it("should raise an error when user try to access to plugin files admin page", function () {
            cy.visit("/file/admin/?group_id=" + project_id + "&action=edit-permissions");
            cy.get("[data-test=feedback]").contains(
                "You are not granted sufficient permission to perform this operation."
            );
        });

        it("should not see hidden packages", function () {
            cy.visitProjectService("frs-project", "Files");

            cy.get("[data-test=main-content]").then(($body) => {
                expect($body).not.to.contain("My hidden package");
            });
        });
    });
});
