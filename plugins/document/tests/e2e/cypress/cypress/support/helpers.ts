/*
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

export function openQuickLook(document_title: string): void {
    cy.get("[data-test=document-tree-content]")
        .contains("tr", document_title)
        .within(() => {
            // button is displayed on tr::hover, so we need to force click
            cy.get("[data-test=quick-look-button]").click({ force: true });
        });

    cy.get("[data-test=document-quick-look]").contains(document_title);
}

export function updateWikiPage(page_content: string): void {
    cy.get("[data-test=php-wiki-edit-page]").contains("Edit").click();
    cy.get("[data-test=textarea-wiki-content]").clear().type(page_content);
    cy.get("[data-test=edit-page-action-buttons]").contains("Save").click();
}

export function deleteDocumentDisplayedInQuickLook(): void {
    // force: true is mandatory because on small screen button might be displayed with only an icon + ellipsis and cause following error:
    // This element '...' is not visible because it has an effective width and height of: '0 x 0' pixels.
    cy.get("[data-test=document-quick-look-delete-button]").click({ force: true });
    cy.get("[data-test=document-confirm-deletion-button]").click();
}
