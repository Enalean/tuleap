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
    let project_unixname: string, public_name: string, now: number;

    before(() => {
        cy.clearSessionCookie();
        now = Date.now();

        project_unixname = "document-kbd-" + now;
        public_name = "Document kbd " + now;

        cy.projectAdministratorLogin();
    });

    beforeEach(() => {
        cy.preserveSessionCookies();
    });

    it("Creates a project with document service", () => {
        cy.visit("/project/new");
        cy.get(
            "[data-test=project-registration-card-label][for=project-registration-tuleap-template-issues]"
        ).click();
        cy.get("[data-test=project-registration-next-button]").click();

        cy.get("[data-test=new-project-name]").type(public_name);
        cy.get("[data-test=project-shortname-slugified-section]").click();
        cy.get("[data-test=new-project-shortname]").type("{selectall}" + project_unixname);
        cy.get("[data-test=approve_tos]").click();
        cy.get("[data-test=project-registration-next-button]").click();
        cy.get("[data-test=start-working]").click({
            timeout: 20000,
        });
    });

    it("user can navigate and manipulate items using keyboard shortcuts", () => {
        cy.visitProjectService(project_unixname, "Documents");
        cy.get("[data-test=document-header-actions]").should("be.visible");

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
    cy.get("[data-test=document-new-item-modal]")
        .should("be.visible")
        .within(() => {
            cy.focused()
                .should("have.attr", "data-test", "document-new-item-title")
                .type(item_title);
            cy.get("[data-test=empty]").click();
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
