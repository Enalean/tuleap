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

import type { ProjectServiceResponse } from "@tuleap/plugin-document-rest-api-types";

export function createAWikiDocument(
    document_title: string,
    page_name: string,
    project_id: number,
): void {
    cy.getFromTuleapAPI<ProjectServiceResponse>(`api/projects/${project_id}/docman_service`).then(
        (response) => {
            const root_folder_id = response.body.root_item.id;

            const payload = {
                title: document_title,
                description: "",
                type: "empty",
                wiki_properties: {
                    page_name: page_name,
                },
            };

            return cy.postFromTuleapApi(`api/docman_folders/${root_folder_id}/wikis`, payload);
        },
    );
}

export function createFolderWithContent(folder_name: string, file_path: string): void {
    cy.get("[data-test=document-header-actions]").within(() => {
        cy.get("[data-test=document-item-action-new-button]").click();

        cy.get("[data-test=document-new-folder-creation-button]").click();
    });

    cy.get("[data-test=document-new-folder-modal]").within(() => {
        cy.get("[data-test=document-new-item-title]").type(folder_name);
        cy.get("[data-test=document-modal-submit-button-create-folder]").click();
    });

    cy.get("[data-test=document-tree-content]")
        .contains("tr", folder_name)
        .within(() => {
            cy.get("[data-test=toggle]").click();
            // button is displayed on tr::hover, so we need to force click
            cy.get("[data-test=document-drop-down-button]").click({ force: true });
            cy.get("[data-test=document-folder-content-creation]").click();
            // force is needed because button is displayed at hover
            cy.get("[data-test=document-new-file-creation-button]").click({ force: true });
        });

    cy.get("[data-test=document-new-file-upload]").selectFile(file_path);
    cy.get("[data-test=document-modal-submit-button-create-item]").click();
}

export function createSubfolderIntoFolderFromTreeViewRow(
    folder_name: string,
    subfolder_name: string,
): void {
    cy.get("[data-test=document-tree-content]")
        .contains("tr", folder_name)
        .within(() => {
            cy.get("[data-test=toggle]").click();
            // button is displayed on tr::hover, so we need to force click
            cy.get("[data-test=document-drop-down-button]").click({ force: true });
            cy.get("[data-test=document-folder-content-creation]").click();
            // force is needed because button is displayed at hover
            cy.get("[data-test=document-new-folder-creation-button]").click({ force: true });
        });

    cy.get("[data-test=document-new-folder-modal]").within(() => {
        cy.get("[data-test=document-new-item-title]").type(subfolder_name);
        cy.get("[data-test=document-modal-submit-button-create-folder]").click();
    });
}
