/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

const now = Date.now();

describe("Artidoc", () => {
    it("Creates an artidoc document", function () {
        cy.projectAdministratorSession();
        const project_name = `artidoc-${now}`;
        cy.createNewPublicProjectFromAnotherOne(project_name, "artidoc-template-project").then(
            () => {
                cy.addProjectMember(project_name, "projectMember");
            },
        );

        cy.projectMemberSession();
        cy.log("Create some artifacts");
        cy.getProjectId(project_name)
            .then((project_id) =>
                cy.getTrackerIdFromREST(project_id, "requirements").as("tracker_id"),
            )
            .then((tracker_id) =>
                Promise.all([
                    createRequirement(
                        tracker_id,
                        "Functional Requirement",
                        "The software must allow users to create, edit, and save documents in various formats such as PDF, DOCX, and TXT.",
                    ).as("func_req_1"),
                    createRequirement(
                        tracker_id,
                        "Performance Requirement",
                        "The software must load a document within 3 seconds of the user's request, regardless of the document size, on a standard desktop computer with minimum hardware specifications.",
                    ).as("func_req_2"),
                    createRequirement(
                        tracker_id,
                        "Security Requirement",
                        "The software must encrypt all sensitive user data stored locally and during transmission over the internet using AES-256 encryption algorithm.",
                    ).as("func_req_3"),
                ]),
            )
            .then(() => {
                cy.log("Create document");
                cy.projectMemberSession();
                cy.visitProjectService(project_name, "Documents");
                cy.get("[data-test=document-new-item]").click();
                cy.contains("[data-test=other_item_type]", "Artidoc").click();
                cy.intercept("*/docman_folders/*/others").as("createDocument");
                cy.get("[data-test=document-new-item-title]").type("Artidoc requirements{enter}");

                cy.wait("@createDocument")
                    .then((interception) => interception.response?.body.id)
                    .then((document_id): void => {
                        const url = "/artidoc/" + encodeURIComponent(document_id);

                        cy.get("[data-test=document-folder-subitem-link]").click();
                        cy.log(
                            "Wait for section to be loaded, intercepting section load does not do the trick",
                        );
                        cy.get("[data-test=states-section]");
                        cy.get("[data-test=artidoc-configuration-tracker]")
                            .last()
                            .select("Requirements");
                        cy.intercept("/api/artidoc/*/configuration").as("saveConfiguration");
                        cy.get("[data-test=artidoc-configuration-submit-button]").click();
                        cy.wait("@saveConfiguration");

                        cy.regularUserSession();
                        cy.visit(url);
                        cy.log("User with read rights should see an empty state");
                        cy.contains("This document is empty");

                        cy.projectMemberSession();
                        cy.visit(url);
                        cy.log("User with write rights should see a form to enter a new section");
                        cy.get("[data-test=title-input]");

                        cy.putFromTuleapApi(`https://tuleap/api/artidoc/${document_id}/sections`, [
                            { artifact: { id: this.func_req_2 } },
                            { artifact: { id: this.func_req_1 } },
                            { artifact: { id: this.func_req_3 } },
                        ]).then(() => {
                            cy.log("Check that the document has now section in given order");
                            cy.reload();
                            cy.contains("This document is empty").should("not.exist");
                            cy.get("[data-test=document-content]").within(() => {
                                cy.contains("li:first-child", "Performance Requirement");
                                cy.contains("li", "Functional Requirement");
                                cy.contains("li:last-child", "Security Requirement").within(() => {
                                    cy.intercept("*/artifacts/*").as("updateArtifact");
                                    cy.intercept("*/artidoc_sections/*").as("refreshSection");
                                    cy.get("[data-test=artidoc-dropdown-trigger]").click();
                                    cy.get("[data-test=edit]").click();
                                });
                                cy.get("[data-test=title-input]").type(
                                    "{selectAll}Security Requirement (edited)",
                                );
                                cy.contains("button", "Save").click();
                                cy.wait(["@updateArtifact", "@refreshSection"]);
                                cy.contains("h1", "Security Requirement (edited)");
                            });
                        });
                    });
            });
    });
});

function createRequirement(
    tracker_id: number,
    title: string,
    description: string,
): Cypress.Chainable<number> {
    return cy.createArtifactWithFields({
        tracker_id,
        fields: [
            {
                shortname: "title",
                value: title,
            },
            {
                shortname: "description",
                value: description,
            },
        ],
    });
}
