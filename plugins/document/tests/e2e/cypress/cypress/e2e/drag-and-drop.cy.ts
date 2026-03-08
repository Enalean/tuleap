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
    let multi_drag_and_drop_project_name: string;
    let multi_drag_and_dropsub_folder_project_name: string;
    const slowing_delay_for_progress_bar_in_ms = 1000;
    const max_wait_for_progress_bar_in_ms = 1000;
    before(() => {
        project_name = "document-dnd-" + getAntiCollisionNamePart();
        cy.createNewPublicProject(project_name, "issues");
        cy.addProjectMember(project_name, "projectMember");

        multi_drag_and_drop_project_name = "multi-dnd-" + getAntiCollisionNamePart();
        cy.createNewPublicProject(multi_drag_and_drop_project_name, "issues");
        cy.addProjectMember(multi_drag_and_drop_project_name, "projectMember");

        multi_drag_and_dropsub_folder_project_name =
            "multi-dnd-sub-folder-" + getAntiCollisionNamePart();
        cy.createNewPublicProject(multi_drag_and_dropsub_folder_project_name, "issues");
        cy.addProjectMember(multi_drag_and_dropsub_folder_project_name, "projectMember");
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

        cy.intercept(
            {
                method: "PATCH",
                pathname: /docman\/file\//,
            },
            (req) => {
                req.continue((res) => {
                    res.delay = slowing_delay_for_progress_bar_in_ms;
                    res.send();
                });
            },
        ).as("uploadFile");

        // eslint-disable-next-line cypress/require-data-selectors
        cy.get(".document-main").selectFile("./_fixtures/aa.txt", { action: "drag-drop" });

        cy.log("Progress bar should be displayed");
        cy.get("[data-test=progress-bar]").should("be.visible");
        cy.wait("@uploadFile");

        cy.log("progress bar should no longer be visible");
        cy.get("[data-test=progress-bar]").should("not.exist");
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

        // The progress bar appears only for a very short moment and the UI hides it
        // independently of the HEAD/PATCH upload requests. Even when we delay the
        // backend responses with cy.intercept(), the frontend removes the progress bar
        // almost immediately after the modal submit action. This means Cypress may miss
        // the exact moment when it is visible. Using `.should("exist")` ensures the test
        // reliably detects that the progress bar appeared at least once during upload.
        cy.get("[data-test=progress-bar]", { timeout: max_wait_for_progress_bar_in_ms }).should(
            "exist",
        );
        cy.wait("@uploadVersion");
        cy.get("[data-test=progress-bar]").should("not.exist");

        cy.get("[data-test=document-drop-down-button]").eq(1).click({ force: true });
        cy.get("[data-test=document-versions]").click();
        cy.get("[data-test=version-number]").should("have.length", 2);
        cy.get("[data-test=version-name]").eq(0).should("contain.text", "My new version");
        cy.get("[data-test=version-changelog]")
            .eq(0)
            .should("contain.text", "This is my new version");
    });

    it("Multi drag and drop files into root folder", () => {
        cy.projectMemberSession();
        cy.visitProjectService(multi_drag_and_drop_project_name, "Documents");
        cy.get("[data-test=document-empty-state]");

        cy.log("Upload first version of file");

        cy.intercept(
            {
                method: "PATCH",
                pathname: /docman\/file\//,
            },
            (req) => {
                req.continue((res) => {
                    res.delay = slowing_delay_for_progress_bar_in_ms;
                    res.send();
                });
            },
        ).as("uploadFile");

        // eslint-disable-next-line cypress/require-data-selectors
        cy.get(".document-main").selectFile(["./_fixtures/aa.txt", "./_fixtures/bb.txt"], {
            action: "drag-drop",
        });

        cy.get("[data-test=progress-bar]", { timeout: max_wait_for_progress_bar_in_ms }).should(
            "exist",
        );
        cy.wait("@uploadFile");
        cy.get("[data-test=progress-bar]").should("not.exist");

        cy.get("[data-test=document-folder-content-row]").should("have.length", 2);
    });

    it("Multi drag and drop files into a sub folder", () => {
        cy.projectMemberSession();
        cy.visitProjectService(multi_drag_and_dropsub_folder_project_name, "Documents");
        cy.get("[data-test=document-empty-state]");

        cy.get("[data-test=document-header-actions]").within(() => {
            cy.get("[data-test=document-item-action-new-button]").click();
            cy.get("[data-test=document-new-folder-creation-button]").click();
        });

        cy.get("[data-test=document-new-folder-modal]").within(() => {
            cy.get("[data-test=document-new-item-title]").type("My new folder");
            cy.get("[data-test=document-modal-submit-button-create-folder]").click();
        });

        cy.log("Upload first version of file");

        cy.intercept(
            {
                method: "PATCH",
                pathname: /docman\/file\//,
            },
            (req) => {
                req.continue((res) => {
                    res.delay = slowing_delay_for_progress_bar_in_ms;
                    res.send();
                });
            },
        ).as("uploadFile");

        // eslint-disable-next-line cypress/require-data-selectors
        cy.get(".document-tree-item-folder").selectFile(
            ["./_fixtures/aa.txt", "./_fixtures/bb.txt"],
            { action: "drag-drop" },
        );
        cy.get("[data-test=progress-bar-quick-look-pane-closed]").should("be.visible");
        cy.wait("@uploadFile");
        cy.get("[data-test=progress-bar-quick-look-pane-closed]").should("not.exist");

        cy.get("[data-test=document-tree-content]")
            .contains("tr", "My new folder")
            .within(() => {
                cy.get("[data-test=toggle]").click();
            });
        cy.get("[data-test=document-folder-content-row]").should("have.length", 3);
    });
});
