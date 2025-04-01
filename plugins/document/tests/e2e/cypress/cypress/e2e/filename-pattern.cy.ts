/*
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
 *
 */

describe("Document filename pattern", () => {
    let project_unixname: string, no_pattern_project_unixname: string, now: number;

    before(() => {
        now = Date.now();
        project_unixname = "doc-pattern-" + now;
        no_pattern_project_unixname = "doc-no-pattern-" + now;
    });

    function uploadNewVersion(file_name: string): void {
        cy.intercept("POST", "*/docman_files/*/versions").as("createVersion");
        cy.visitProjectService(project_unixname, "Documents");
        cy.get("[data-test=dropdown-button]").click({ force: true });
        cy.get("[data-test=document-dropdown-create-new-version-button]").click({ force: true });
        cy.get("[data-test=document-new-file-upload]").selectFile(file_name);
        cy.get("[data-test=document-modal-submit-button-create-file-version]").click();
        cy.wait("@createVersion");
    }

    it("administrator can define a specific pattern", () => {
        cy.projectAdministratorSession();
        cy.createNewPublicProject(project_unixname, "issues");
        cy.createNewPublicProject(no_pattern_project_unixname, "issues");

        cy.log("Pattern can be set");
        cy.visitProjectService(project_unixname, "Documents");
        cy.get("[data-test=breadcrumb-project-documentation]").click();
        cy.get("[data-test=breadcrumb-administrator-link]").click();
        cy.get("[data-test=filename-pattern]").click({ force: true });
        cy.get("[data-test=docman-enforce-pattern]").check();

        // eslint-disable-next-line no-template-curly-in-string
        cy.get("[data-test=docman-pattern]").type("tuleap-${ID}-${TITLE}", {
            parseSpecialCharSequences: false,
        });
        cy.get("[data-test=docman-save-pattern-button]").click();

        cy.log("At file creation pattern is displayed");
        cy.visitProjectService(project_unixname, "Documents");

        cy.get("[data-test=document-header-actions]").within(() => {
            cy.get("[data-test=document-item-action-new-button]").click();
            cy.get("[data-test=document-new-file-creation-button]").click();
        });
        cy.get("[data-test=document-new-item-title]").type("test");
        // eslint-disable-next-line no-template-curly-in-string
        cy.get("[data-test=preview]").contains("tuleap-${ID}-test");

        cy.log("User can upload a file");
        cy.intercept("POST", "*/docman_folders/*/files").as("createFile");
        cy.get("[data-test=document-new-file-upload]").selectFile("./_fixtures/aa.txt");
        cy.get("[data-test=document-modal-submit-button-create-item]").click();
        cy.wait("@createFile");
        cy.log("Check that progress bar is displayed");
        cy.get("[data-test=document-progress-bar]");
        uploadNewVersion("./_fixtures/bb.txt");
        uploadNewVersion("./_fixtures/cc.txt");
        uploadNewVersion("./_fixtures/dd.txt");
        uploadNewVersion("./_fixtures/ee.txt");
        uploadNewVersion("./_fixtures/ee.txt");
        uploadNewVersion("./_fixtures/ee.txt");

        cy.log("When file has several version history, then user have a show all versions link");
        cy.intercept("GET", "*/docman_files/*/versions*").as("loadVersions");
        cy.get("[data-test=dropdown-button]").click({ force: true });
        cy.get("[data-test=document-dropdown-create-new-version-button]").click({ force: true });
        cy.wait("@loadVersions");

        cy.get("[data-test=document-history-file]").should("have.length", 5);
        cy.get("[data-test=document-history-file]")
            .first()
            .within(() => {
                cy.get("[data-test=download-version]").click();
                cy.get("[data-test=version-file-name]").then((version_one) => {
                    const download_folder = Cypress.config("downloadsFolder");
                    cy.readFile(download_folder + "/" + version_one.html()).should("exist");
                    cy.readFile(download_folder + "/" + version_one.html()).should("eq", "ee\n");
                });
            });

        cy.log("Go to all versions page");
        cy.get("[data-test=document-view-all-versions]").contains("View all versions");
        cy.get("[data-test=document-view-all-versions]").click();

        cy.get("[data-test=history-versions]").find("tr").its("length").should("be.greaterThan", 5);

        cy.log("Filename is not displayed when not configured");
        cy.visitProjectService(no_pattern_project_unixname, "Documents");

        cy.get("[data-test=document-header-actions]").within(() => {
            cy.get("[data-test=document-item-action-new-button]").click();
            cy.get("[data-test=document-new-file-creation-button]").click();
        });
        cy.get("[data-test=preview]").should("not.exist");
    });
});
