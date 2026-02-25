/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

describe("Document properties", () => {
    let project_name: string;
    before(() => {
        cy.projectAdministratorSession();
        project_name = "document-dnd-" + getAntiCollisionNamePart();
        cy.createNewPublicProject(project_name, "issues");
        cy.addProjectMember(project_name, "projectMember");
    });
    beforeEach(() => {
        cy.siteAdministratorSession();
        cy.visit("/admin/document/history-enforcement");
        cy.get("[data-test=toggle-changelog-modal]").then((el) => {
            if (el.is(":not(:checked)")) {
                // eslint-disable-next-line cypress/no-force
                cy.get("[data-test=toggle-changelog-modal]").click({ force: true });
                cy.get("[data-test=feedback]").should(
                    "contain.text",
                    "Settings have been saved successfully.",
                );
            }
        });
    });

    it("can create a new version by dropping file on existing document", () => {
        cy.projectMemberSession();
        cy.visitProjectService(project_name, "Documents");
        cy.get("[data-test=document-empty-state]");

        cy.log("Upload first version of file");
        cy.intercept("PATCH", "*/docman/file/*").as("uploadFile");
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get(".document-main").selectFile("./_fixtures/aa.txt", { action: "drag-drop" });
        cy.wait("@uploadFile");
        cy.get("[data-test=document-folder-content-row]").should("have.length", 1);

        cy.visitProjectService(project_name, "Documents");
        cy.log("Upload a new version");
        cy.intercept("PATCH", "*/docman/version/*").as("uploadVersion");
        cy.get("[data-test=document-folder-content-row]").selectFile("./_fixtures/bb.txt", {
            action: "drag-drop",
        });
        cy.get("[data-test=modal-title]").should("contain.text", "New version for");
        cy.get("[data-test=document-update-version-title]").type("My new version");
        cy.get("[data-test=document-update-changelog]").type("This is my new version");
        cy.get("[data-test=document-modal-submit-button-create-version-changelog]").click();
        cy.wait("@uploadVersion");

        cy.log("Check new version exists");
        cy.get("[data-test=document-folder-content-row]").should("be.visible");
        // eslint-disable-next-line cypress/no-force
        cy.get("[data-test=document-drop-down-button]").eq(1).click({ force: true });
        cy.get("[data-test=document-versions]").click();
        cy.get("[data-test=version-number]").should("have.length", 2);
        cy.get("[data-test=version-name]").eq(0).should("contain.text", "My new version");
        cy.get("[data-test=version-changelog]")
            .eq(0)
            .should("contain.text", "This is my new version");
    });
});
