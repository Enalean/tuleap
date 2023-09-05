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
    let now: number;

    context("Project administrators", function () {
        before(() => {
            cy.projectAdministratorSession();
            now = Date.now();
            cy.createNewPublicProject(`frs-${now}`, "agile_alm").as("project_id");
        });

        context("Frs packages", function () {
            it("can CRUD a new package", function () {
                cy.projectAdministratorSession();
                cy.visitProjectService(`frs-${now}`, "Files");
                cy.get("[data-test=create-new-package]").click();
                cy.get("[data-test=frs-create-package]").type(`package${now}`);
                cy.get("[data-test=frs-create-package-button]").click({
                    timeout: 60000,
                });

                cy.visitProjectService(`frs-${now}`, "Files");
                cy.get('[data-test="package-name"]').contains(`package${now}`);

                cy.get("[data-test=update-package]").first().click();
                cy.get("[data-test=frs-create-package]").clear().type(`edited${now}`);
                cy.get("[data-test=frs-create-package-button]").click({
                    timeout: 60000,
                });

                cy.visitProjectService(`frs-${now}`, "Files");
                cy.get('[data-test="package-name"]').contains(`edited${now}`);

                cy.get("[data-test=remove-package]").first().click({
                    timeout: 60000,
                });

                cy.visitProjectService(`frs-${now}`, "Files");
                cy.get("[data-test=main-content]").should("not.contain", `edited${now}`);
            });
        });

        context("Frs CRUD releases", function () {
            it("can create a new release", function () {
                cy.projectAdministratorSession();
                cy.visitProjectService(`frs-${now}`, "Files");
                cy.createFRSPackage(parseInt(this.project_id, 10), "Package to test release");
                cy.visitProjectService(`frs-${now}`, "Files");

                cy.intercept({
                    url: /file\/admin\/frsajax\.php/,
                }).as("createRelease");
                cy.get("[data-test=create-release]").first().click({ force: true });

                cy.get("[data-test=release-name]").type(`My release${now}`);
                cy.get("[data-test=create-release-button]").click({
                    timeout: 60000,
                });
                cy.wait("@createRelease", { timeout: 60000 });

                cy.reloadUntilCondition(
                    () => cy.visitProjectService(`frs-${now}`, "Files"),
                    (number_of_attempts, max_attempts) => {
                        cy.log(
                            `Check that My release${now} has been created (attempt ${number_of_attempts}/${max_attempts})`,
                        );
                        return cy
                            .get('[data-test="release-name"]')
                            .then((releases) => releases.text().includes(`My release${now}`));
                    },
                    `Timed out while checking if My release${now} has been created`,
                );

                cy.intercept({
                    url: /file\/admin\/frsajax\.php/,
                }).as("createRelease");
                cy.get("[data-test=edit-release]").first().click({ force: true });
                cy.get("[data-test=release-name]").clear().type(`Edited${now}`);
                cy.get("[data-test=create-release-button]").click({
                    timeout: 60000,
                });
                cy.wait("@createRelease", { timeout: 60000 });

                cy.visitProjectService(`frs-${now}`, "Files");
                cy.get("[data-test=edit-release]").first().click({ force: true });
                cy.get('[data-test="release-name"]').should("have.value", `Edited${now}`);

                cy.visitProjectService(`frs-${now}`, "Files");
                cy.get("[data-test=release-delete-button]")
                    .first()
                    .click({ force: true, timeout: 60000 });

                cy.visitProjectService(`frs-${now}`, "Files");
                cy.get("[data-test=main-content]").should("not.contain", `edited${now}`);
            });
        });

        context("Hidden packages", function () {
            it("can create a new hidden package", function () {
                cy.projectAdministratorSession();
                cy.createNewPublicProject(`frs-hidden-${now}`, "agile_alm");
                cy.visitProjectService(`frs-hidden-${now}`, "Files");
                cy.get("[data-test=create-new-package]").click();
                cy.get("[data-test=frs-create-package]").type(`My hidden package${now}`);
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
        it("should not see hidden packages", function () {
            cy.projectMemberSession();
            cy.visitProjectService(`frs-hidden-${now}`, "Files");

            cy.get("[data-test=main-content]").then(($body) => {
                expect($body).not.to.contain("My hidden package");
            });
        });
    });
});
