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

describe("Keyboard navigation in Document", () => {
    let project_unixname: string, now: number;

    before(() => {
        now = Date.now();
        project_unixname = "document-kbd-" + now;
    });

    it("user can navigate and manipulate items using keyboard shortcuts", () => {
        cy.projectAdministratorSession();
        cy.log("Creates a project with document service");
        cy.createNewPublicProject(project_unixname, "issues");
        cy.visitProjectService(project_unixname, "Documents");
        cy.get("[data-test=document-header-actions]").should("be.visible");
        cy.get("[data-test=loading-row]").should("not.exist");

        const folder_title = "Folder title " + now;
        const item_title = "Item title " + now;
        testNewFolderShortcut(folder_title);
        testNewItemShortcut(item_title);
        testNavigationShortcuts(folder_title, item_title);
        deleteItems();
    });
});

function testNewFolderShortcut(folder_title: string): void {
    typeShortcut("b");
    cy.get("[data-test=document-new-folder-modal]")
        .should("be.visible")
        .within(() => {
            cy.focused()
                .should("have.attr", "data-test", "document-new-item-title")
                .type(folder_title);
            cy.get("[data-test=document-modal-submit-button-create-folder]").click();
        });
    cy.get("[data-test=document-new-folder-modal]").should("not.be.visible");
    cy.get("[data-test=folder-title]").contains(folder_title);
}

function testNewItemShortcut(item_title: string): void {
    typeShortcut("n");
    cy.get("[data-test=document-header-actions]").within(() => {
        cy.get("[data-test=document-new-empty-creation-button]").click();
    });
    cy.get("[data-test=document-new-item-modal]")
        .should("be.visible")
        .within(() => {
            cy.focused()
                .should("have.attr", "data-test", "document-new-item-title")
                .type(item_title);
            cy.get("[data-test=document-modal-submit-button-create-item]").click();
        });
    cy.get("[data-test=document-new-item-modal]").should("not.be.visible");
    cy.get("[data-test=empty-file-title]").contains(item_title);
}

function testNavigationShortcuts(folder_title: string, item_title: string): void {
    typeShortcut("{ctrl}{uparrow}");
    cy.focused().should("contain", folder_title);

    typeShortcut("{downarrow}");
    cy.focused().should("contain", item_title);
}

function deleteItems(): void {
    typeShortcut("{del}");
    cy.get("[data-test=document-confirm-deletion-button]").click();
    cy.get("[data-test=document-delete-item-modal]").should("not.exist");

    typeShortcut("{ctrl}{uparrow}", "{del}");
    cy.get("[data-test=document-confirm-deletion-button]").click();
    cy.get("[data-test=document-delete-item-modal]").should("not.exist");
}

function typeShortcut(...inputs: string[]): void {
    for (const input of inputs) {
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get("body").type(input);
    }
}
