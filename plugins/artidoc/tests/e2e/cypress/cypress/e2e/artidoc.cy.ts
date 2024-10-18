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

const requirements = [
    {
        title: "Functional Requirement",
        description:
            "The software must allow users to create, edit, and save documents in various formats such as PDF, DOCX, and TXT.",
    },
    {
        title: "Performance Requirement",
        description:
            "The software must load a document within 3 seconds of the user's request, regardless of the document size, on a standard desktop computer with minimum hardware specifications.",
    },
    {
        title: "Security Requirement",
        description:
            "The software must encrypt all sensitive user data stored locally and during transmission over the internet using AES-256 encryption algorithm.",
    },
];

describe("Artidoc", () => {
    it("Creates an artidoc document", function () {
        cy.projectAdministratorSession();
        const project_name = `artidoc-${now}`;
        cy.createNewPublicProjectFromAnotherOne(project_name, "artidoc-template-project").then(
            () => {
                cy.addProjectMember(project_name, "projectMember");
            },
        );

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
                cy.get("[data-test=artidoc-configuration-tracker]").last().select("Requirements");
                cy.intercept("/api/artidoc/*/configuration").as("saveConfiguration");
                cy.get("[data-test=artidoc-configuration-submit-button]").click();
                cy.wait("@saveConfiguration");

                cy.regularUserSession();
                cy.visit(url);
                cy.log("User with read rights should see an empty state");
                cy.contains("This document is empty");

                cy.projectMemberSession();
                cy.visit(url);
            });

        cy.get("[data-test=artidoc-section]:first-child").within(() => {
            cy.log("User with write rights should see a form to enter a new section");
            fillInSectionTitleAndDescription(requirements[0]);
        });

        cy.log("User should be able to add a section at the beginning");
        cy.get("[data-test=artidoc-add-new-section-trigger]").first().click();
        cy.get("[data-test=add-new-section]").first().click();
        cy.get("[data-test=artidoc-section]:first-child").within(() => {
            fillInSectionTitleAndDescription(requirements[1]);
        });

        cy.log("User should be able to add a section at the end");
        cy.get("[data-test=artidoc-add-new-section-trigger]").last().click();
        cy.get("[data-test=add-new-section]").last().click();
        cy.get("[data-test=artidoc-section]:last-child").within(() => {
            fillInSectionTitleAndDescription(requirements[2]);
        });

        cy.log("Check that the document has now section in given order");
        cy.reload();

        cy.contains("This document is empty").should("not.exist");
        cy.get("[data-test=artidoc-section]:first-child [data-test=title-input]").should(
            "have.value",
            "Performance Requirement",
        );
        cy.get("[data-test=artidoc-section]:nth-child(2) [data-test=title-input]").should(
            "have.value",
            "Functional Requirement",
        );
        cy.get("[data-test=artidoc-section]:last-child [data-test=title-input]").should(
            "have.value",
            "Security Requirement",
        );

        cy.get("[data-test=artidoc-section]:last-child [data-test=title-input]").type(
            "{selectAll}Security Requirement (edited)",
        );

        cy.get("[data-test=artidoc-section]:last-child").within(() => {
            cy.intercept("*/artifacts/*").as("updateArtifact");
            cy.intercept("*/artidoc_sections/*").as("refreshSection");

            pasteImageInProseMirror();
            cy.contains("button", "Save").click();

            cy.wait(["@updateArtifact", "@refreshSection"]);

            cy.get("[data-test=title-input]").should("have.value", "Security Requirement (edited)");
            // ignore rule for image pasted in ProseMirror
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("img")
                .should("have.attr", "src")
                .should("include", "/plugins/tracker/attachments/");
        });
    });
});

function getProseMirrorArea(): Cypress.Chainable<JQuery<HTMLElement>> {
    // ignore rule because this tag is generated by ProseMirror
    // hence, we cannot put a data-test attribute on it
    // eslint-disable-next-line cypress/require-data-selectors
    return cy.get(".ProseMirror[contenteditable=true]");
}

function fillInSectionTitleAndDescription({
    title,
    description,
}: {
    title: string;
    description: string;
}): void {
    cy.intercept("*/artifacts").as("createArtifact");
    cy.intercept("*/artidoc/*/sections").as("addSection");

    cy.get("[data-test=title-input]").type("{selectAll}" + title);

    getProseMirrorArea().then((editor_body) => {
        cy.wrap(editor_body).type("{selectAll}" + description);
    });

    cy.get("[data-test=section-edition]").contains("button", "Save").click();
    cy.wait(["@createArtifact", "@addSection"]);
}

function pasteImageInProseMirror(): void {
    cy.intercept("PATCH", "/uploads/tracker/file/*").as("UploadImage");

    getProseMirrorArea().then((editor_body) => {
        fetch(
            "data:image/gif;base64,R0lGODlhCgAKAIABAP8A/////yH+EUNyZWF0ZWQgd2l0aCBHSU1QACwAAAAACgAKAAACCISPqcvtD2MrADs=",
        )
            .then(function (res) {
                return res.arrayBuffer();
            })
            .then(function (buf) {
                const file = new File([buf], "square.gif", {
                    type: "image/gif",
                });
                const data_transfer = new DataTransfer();
                data_transfer.items.add(file);

                const paste_event = Object.assign(
                    new Event("drop", {
                        bubbles: true,
                        cancelable: true,
                    }),
                    {
                        dataTransfer: data_transfer,
                    },
                );

                editor_body[0].dispatchEvent(paste_event);
            });
    });

    cy.wait("@UploadImage");
}
