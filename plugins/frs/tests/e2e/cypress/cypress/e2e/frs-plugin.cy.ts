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
    let now: number;
    beforeEach(() => {
        now = Date.now();
    });

    it("user can link a frs release to an agiledashboard release", (): void => {
        cy.projectMemberSession();
        cy.getProjectId("frs-plugin").then((frs_project_id) => {
            cy.visitProjectService("frs-plugin", "Agile Dashboard");
            cy.get("[data-test=expand-collapse-milestone]")
                .should("have.attr", "data-artifact-id")
                .as("release_id")
                .then(function () {
                    cy.visit(`/file/showfiles.php?group_id=${frs_project_id}`);

                    cy.createFRSPackage(frs_project_id, "My first package " + now);
                    cy.visit(`/file/showfiles.php?group_id=${frs_project_id}`);

                    cy.intercept({
                        url: /file\/admin\/frsajax\.php/,
                    }).as("createRelease");
                    cy.get(`[data-test=toggle-package]`).first().click();
                    cy.get("[data-test=create-release]").first().click();
                    cy.get("[data-test=release-name]").type("My release name" + now);
                    cy.get("[data-test=release-artifact-id]").type(this.release_id);
                    cy.get("[data-test=release-note]").type("My awesome RN" + now);
                    cy.get("[data-test=create-release-button]").click({
                        timeout: 60000,
                    });
                    cy.wait("@createRelease", { timeout: 60000 });

                    cy.visitProjectService("frs-plugin", "Files");

                    cy.visit(`/file/showfiles.php?group_id=${frs_project_id}`);
                    cy.get(`[data-test=toggle-package]`).first().click();
                    cy.get(`[data-test=release-note-access]`).first().click();
                    cy.get("[data-test=release-note]").contains("My awesome RN" + now);

                    cy.get(`[data-test=linked-artifacts]`).click();

                    cy.get("[data-test=frs-artifact-links-fixed_in-forward]").within(() => {
                        cy.get(`[data-test=artifact]`).contains("bug 1");
                        cy.get(`[data-test=artifact]`).contains("bug 2");
                    });
                    cy.get("[data-test=frs-artifact-links-fixed_in-reverse]").within(() => {
                        cy.get(`[data-test=artifact]`).contains("reverse 1");
                        cy.get(`[data-test=artifact]`).contains("reverse 2");
                    });
                });
        });
    });
});
