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

import { getAntiCollisionNamePart } from "@tuleap/cypress-utilities-support";

function visitFilesService(project_id: number): void {
    cy.visit(`/file/showfiles.php?group_id=${project_id}`);
}

describe("Frs", function () {
    context("Project administrators", function () {
        let project_name: string;
        before(() => {
            project_name = "frs-" + getAntiCollisionNamePart();
            cy.projectAdministratorSession();
            cy.createNewPublicProject(project_name, "agile_alm").as("project_id");
        });

        context("Frs packages", function () {
            it("can CRUD a new package", function () {
                cy.projectAdministratorSession();
                cy.visitProjectService(project_name, "Files");
                cy.get("[data-test=create-new-package]").click();
                const package_name = "package" + getAntiCollisionNamePart();
                cy.get("[data-test=frs-create-package]").type(package_name);
                cy.get("[data-test=frs-create-package-button]").click({
                    timeout: 60000,
                });

                visitFilesService(this.project_id);
                cy.get("[data-test=package-name]").contains(package_name);

                cy.get("[data-test=update-package]").first().click();
                const edited_package = "edited" + getAntiCollisionNamePart();
                cy.get("[data-test=frs-create-package]").clear().type(edited_package);
                cy.get("[data-test=frs-create-package-button]").click({
                    timeout: 60000,
                });

                visitFilesService(this.project_id);
                cy.get("[data-test=package-name]").contains(edited_package);

                cy.get("[data-test=remove-package]").first().click({
                    timeout: 60000,
                });
                cy.get("[data-test=confirm-deletion]").click();

                visitFilesService(this.project_id);
                cy.get("[data-test=packages-list]").should("not.contain", edited_package);
            });
        });

        context("Frs CRUD releases", function () {
            it("can create a new release", function () {
                cy.projectAdministratorSession();
                cy.createFRSPackage(this.project_id, "Package to test release");
                cy.visitProjectService(project_name, "Files");
                cy.get("[data-test=package-name]").click();

                cy.intercept({
                    url: /file\/admin\/frsajax\.php/,
                }).as("createRelease");
                cy.get("[data-test=create-release]").first().click();

                const release_name = "My release" + getAntiCollisionNamePart();
                cy.get("[data-test=release-name]").type(release_name);
                cy.get("[data-test=create-release-button]").click({
                    timeout: 60000,
                });
                cy.wait("@createRelease", { timeout: 60000 });
                cy.get("[data-test=release-name]").should("contain.text", release_name);

                cy.get("[data-test=edit-release]").first().click();
                const edited_release = "Edited" + getAntiCollisionNamePart();
                cy.get("[data-test=release-name]").type("{selectAll}" + edited_release);
                cy.get("[data-test=create-release-button]").click({
                    timeout: 60000,
                });
                cy.wait("@createRelease", { timeout: 60000 });

                visitFilesService(this.project_id);
                cy.get("[data-test=package-name]").click();

                cy.getContains("tr", edited_release).find("[data-test=edit-release]").click();
                cy.get("[data-test=release-name]").should("have.value", edited_release);

                cy.log("MD5 is computed at file upload when not provided");
                cy.get("[data-test=file-input]").selectFile("cypress/fixtures/release-file.txt");
                cy.get("[data-test=create-release-button]").click({
                    timeout: 60000,
                });
                cy.wait("@createRelease", { timeout: 60000 });
                cy.getContains("tr", edited_release).find("[data-test=edit-release]").click();
                cy.get("[data-test=release_reference_md5]").should(
                    "have.value",
                    "d41d8cd98f00b204e9800998ecf8427e",
                );

                cy.log("File is not created when bad MD5 is written by user");
                cy.get("[data-test=file-input]").selectFile(
                    "cypress/fixtures/other-release-file.txt",
                );
                cy.get("[data-test=add-md5-file-input]").type("blabla");
                cy.get("[data-test=create-release-button]").click({
                    timeout: 60000,
                });
                cy.wait("@createRelease", { timeout: 60000 });
                cy.get("[data-test=feedback]").contains("MD5 checksum comparison failed");

                visitFilesService(this.project_id);
                cy.get("[data-test=package-name]").click();
                cy.getContains("tr", edited_release).find("[data-test=edit-release]").click();

                cy.get("[data-test=release-file-name]").should("have.length", 1);

                cy.get("[data-test=release-file-name]")
                    .should("contain", "release-file.txt")
                    .and("not.contain", "other-release-file.txt");

                cy.log("When user provide a correct MD5 file is created");
                cy.get("[data-test=file-input]").selectFile(
                    "cypress/fixtures/other-release-file.txt",
                );
                cy.get("[data-test=add-md5-file-input]").type("d41d8cd98f00b204e9800998ecf8427e");
                cy.get("[data-test=create-release-button]").click({
                    timeout: 60000,
                });
                cy.wait("@createRelease", { timeout: 60000 });
                cy.getContains("tr", edited_release).find("[data-test=edit-release]").click();
                cy.get("[data-test=release-file-name]").should("have.length", 2);

                cy.get("[data-test=release-file-name]")
                    .should("contain", "release-file.txt")
                    .and("contain", "other-release-file.txt");

                cy.log("Delete the release");
                visitFilesService(this.project_id);
                cy.get("[data-test=package-name]").click();
                cy.getContains("tr", edited_release)
                    .find("[data-test=release-delete-button]")
                    .click();
                cy.get("[data-test=delete-release-modal]")
                    .find("[data-test=confirm-deletion]")
                    .click();

                visitFilesService(this.project_id);
                cy.get("[data-test=package-name]").click();
                cy.get("[data-test=releases-list]").should("not.contain", edited_release);
            });
        });

        context("Hidden packages", function () {
            it("can create a new hidden package", function () {
                cy.projectAdministratorSession();
                const project_name = "frs-hidden-" + getAntiCollisionNamePart();
                cy.createNewPublicProject(project_name, "agile_alm");
                cy.visitProjectService(project_name, "Files");
                cy.get("[data-test=create-new-package]").click();
                cy.get("[data-test=frs-create-package]").type(
                    "My hidden package" + getAntiCollisionNamePart(),
                );
                cy.get("[data-test=status]").select("Hidden");
                cy.get("[data-test=frs-create-package-button]").click({
                    timeout: 60000,
                });

                cy.log("Project members should not see hidden packages");
                cy.projectMemberSession();
                cy.visitProjectService(project_name, "Files");

                cy.get("[data-test=packages-list]").should("not.contain", "My hidden package");
            });
        });
    });
});
