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

const structures = [
    {
        title: "Introduction",
        description: "With description of how requirements should be described.",
    },
];

describe("Artidoc", () => {
    it("Creates an artidoc document", function () {
        cy.projectAdministratorSession();
        const project_name = `artidoc-${now}`;
        cy.createNewPublicProjectFromAnotherOne(project_name, "artidoc-template-project")
            .then((project_id) => {
                cy.addProjectMember(project_name, "projectMember");

                return cy.getTrackerIdFromREST(project_id, "requirements");
            })
            .then((tracker_id) => {
                cy.projectAdministratorSession();
                cy.createArtifact({
                    tracker_id,
                    artifact_title: "An important requirement",
                    title_field_name: "title",
                }).as("artifact_to_reference_id");
            });

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
        cy.get("[data-test=add-new-section]").first().click({ force: true });
        cy.get("[data-test=artidoc-section]:first-child").within(() => {
            fillInSectionTitleAndDescription(requirements[1]);
        });

        cy.log("User should be able to add a section at the end");
        cy.get("[data-test=artidoc-add-new-section-trigger]").last().click();
        cy.get("[data-test=add-new-section]").last().click({ force: true });
        cy.get("[data-test=artidoc-section]:last-child").within(() => {
            fillInSectionTitleAndDescription(requirements[2]);
        });

        cy.log("Check that the document has now section in given order");
        cy.reload();

        cy.contains("This document is empty").should("not.exist");
        assertDocumentContainsSections([
            "Performance Requirement",
            "Functional Requirement",
            "Security Requirement",
        ]);

        cy.get("[data-test=artidoc-section]:last-child").within(() => {
            getSectionTitle().type("{end} (edited)");
        });

        cy.get("[data-test=artidoc-section]:last-child").within(() => {
            cy.intercept("PUT", "*/artidoc_sections/*").as("updateSection");
            cy.intercept("GET", "*/artidoc_sections/*").as("RefreshSection");

            pasteImageInSectionDescription("/uploads/tracker/file/*");
            cy.contains("button", "Save").click();

            cy.wait(["@updateSection", "@RefreshSection"]);

            getSectionTitle().should("contain.text", "Security Requirement (edited)");
            // ignore rule for image pasted in ProseMirror
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("img")
                .should("have.attr", "src")
                .should("include", "/plugins/tracker/attachments/");
        });

        cy.log("User should be able to reorder sections with arrows");
        cy.intercept("PATCH", "/api/artidoc/*/sections").as("patchSectionsOrder");

        cy.get("[data-test=move-down]").first().click({ force: true });
        cy.wait("@patchSectionsOrder");
        assertDocumentContainsSections([
            "Functional Requirement",
            "Performance Requirement",
            "Security Requirement (edited)",
        ]);

        cy.get("[data-test=move-up]").last().click({ force: true });
        cy.wait("@patchSectionsOrder");
        assertDocumentContainsSections([
            "Functional Requirement",
            "Security Requirement (edited)",
            "Performance Requirement",
        ]);

        cy.log("User should be able to add a freetext at the beginning");
        cy.get("[data-test=artidoc-add-new-section-trigger]").first().click();
        cy.get("[data-test=add-freetext-section]").first().click({ force: true });
        cy.get("[data-test=artidoc-section]:first-child").within(() => {
            fillInFreeTextSectionTitleAndDescription(structures[0]);
        });

        cy.reload();
        assertDocumentContainsSections([
            "Introduction",
            "Functional Requirement",
            "Security Requirement (edited)",
            "Performance Requirement",
        ]);

        cy.log("Paste image in freetext section");
        cy.get("[data-test=artidoc-section]:first-child").within(() => {
            getSectionTitle().type("{end} (edited)");
            pasteImageInSectionDescription("/uploads/artidoc/sections/file/*");
            cy.contains("button", "Save").click();

            cy.wait(["@RefreshSection"]);

            // ignore rule for image pasted in ProseMirror
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("img").should("have.attr", "src").should("include", "/artidoc/attachments/");
        });
        assertDocumentContainsSections([
            "Introduction (edited)",
            "Functional Requirement",
            "Security Requirement (edited)",
            "Performance Requirement",
        ]);

        testCrossReferenceExtraction();

        cy.intercept("DELETE", "*/artidoc_sections/*").as("deleteSection");
        cy.log("Users should be able to delete a freetext section");
        cy.get("[data-test=artidoc-dropdown-trigger]").first().click({ force: true });
        cy.get("[data-test=delete]").filter(":visible").click({ force: true });
        cy.get("[data-test=remove-button]").click();
        cy.wait("@deleteSection");
        assertDocumentContainsSections([
            "Functional Requirement",
            "Security Requirement (edited)",
            "Performance Requirement",
        ]);

        cy.log("Users should be able to delete an artifact section");
        cy.get("[data-test=artidoc-dropdown-trigger]").first().click({ force: true });
        cy.get("[data-test=delete]").filter(":visible").click({ force: true });
        cy.wait("@deleteSection");
        assertDocumentContainsSections([
            "Security Requirement (edited)",
            "Performance Requirement",
        ]);
    });
});

function testCrossReferenceExtraction(): void {
    const insertArtifactReferenceAndAssertItHasBeenProcessed = (reference_id: number): void => {
        getSectionDescription().type(`{enter} See art #${reference_id} for more information.`);

        cy.get("[data-test=section-edition]").contains("button", "Save").click();
        cy.wait(["@updateSection", "@RefreshSection"]);

        getSectionDescription()
            .get("async-cross-reference")
            .should("contain.text", `art #${reference_id}`);
    };

    cy.log("User should be able to reference artifacts in freetext and artifact sections");
    cy.get<number>("@artifact_to_reference_id").then((artifact_id) => {
        cy.get("[data-test-type=freetext-section]")
            .first()
            .within(() => {
                insertArtifactReferenceAndAssertItHasBeenProcessed(artifact_id);
            });

        cy.get("[data-test-type=artifact-section]")
            .first()
            .within(() => {
                insertArtifactReferenceAndAssertItHasBeenProcessed(artifact_id);
            });
    });
}

function getSectionTitle(): Cypress.Chainable<JQuery<HTMLElement>> {
    // ignore rule because this tag is generated by ProseMirror
    // hence, we cannot put a data-test attribute on it
    // eslint-disable-next-line cypress/require-data-selectors
    return cy.get("artidoc-section-title");
}

function getSectionDescription(): Cypress.Chainable<JQuery<HTMLElement>> {
    // ignore rule because this tag is generated by ProseMirror
    // hence, we cannot put a data-test attribute on it
    // eslint-disable-next-line cypress/require-data-selectors
    return cy.get("artidoc-section-description");
}

function fillInSectionTitleAndDescription({
    title,
    description,
}: {
    title: string;
    description: string;
}): void {
    cy.intercept("POST", "/api/v1/artidoc_sections").as("addSection");

    getSectionTitle().type(title);
    getSectionDescription().type(description);

    cy.get("[data-test=section-edition]").contains("button", "Save").click();
    cy.wait("@addSection");
}

function fillInFreeTextSectionTitleAndDescription({
    title,
    description,
}: {
    title: string;
    description: string;
}): void {
    getSectionTitle().type(title);
    getSectionDescription().type(description);

    cy.get("[data-test=section-edition]").contains("button", "Save").click();
    cy.wait("@RefreshSection");
}

function pasteImageInSectionDescription(url_to_intercept: string): void {
    cy.intercept("PATCH", url_to_intercept).as("UploadImage");

    getSectionDescription().click();
    getSectionDescription().then((section_description) => {
        const first_child = section_description[0].firstChild;
        if (!first_child) {
            throw new Error("Unable to find the first child of the section description");
        }

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
                    new ClipboardEvent("paste", {
                        bubbles: true,
                        cancelable: true,
                        clipboardData: data_transfer,
                    }),
                );

                first_child.dispatchEvent(paste_event);
            });
    });

    cy.wait("@UploadImage");
}

function assertDocumentContainsSections(expected_sections: Array<string>): void {
    cy.get("[data-test=artidoc-section]").should("have.length", expected_sections.length);
    expected_sections.forEach((value, key) => {
        cy.get("[data-test=artidoc-section]")
            .eq(key)
            .within(() => {
                getSectionTitle().should("contain.text", value);
            });
    });
}
