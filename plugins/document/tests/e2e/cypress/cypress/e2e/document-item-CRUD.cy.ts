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

import { deleteDocumentDisplayedInQuickLook, openQuickLook } from "../support/helpers";
import type {
    ProjectServiceResponse,
    CreatedItemResponse,
} from "@tuleap/plugin-document-rest-api-types";
import { getAntiCollisionNamePart } from "@tuleap/cypress-utilities-support";

describe("Document", () => {
    let project_unixname: string;
    let project_name: string;
    let project_size: string;
    let project_copy_paste: string;
    before(() => {
        project_unixname = "docman-" + getAntiCollisionNamePart();
        cy.projectAdministratorSession();
        cy.createNewPublicProject(project_unixname, "issues").as("project_id");
        cy.visit(`${"/plugins/document/" + project_unixname + "/admin-search"}`);

        cy.projectAdministratorSession();
        project_name = "document-project-" + getAntiCollisionNamePart();
        cy.createNewPublicProject(project_name, "issues");
        cy.addProjectMember(project_name, "projectMember");

        cy.projectAdministratorSession();
        project_size = "document-size-" + getAntiCollisionNamePart();
        cy.createNewPublicProject(project_size, "issues");

        cy.projectAdministratorSession();
        project_copy_paste = "document-copy-" + getAntiCollisionNamePart();
        cy.createNewPublicProject(project_copy_paste, "issues");
    });

    it("document versioning", function () {
        cy.projectAdministratorSession();
        createProjectWithAVersionnedEmbededFile();

        cy.log("delete a given version of a document");
        cy.get(`[data-test=delete-button]`).eq(0).click();
        cy.get("[data-test=confirm-button]").eq(0).click();

        cy.get("[data-test=display-version-feedback]").contains("successfully deleted");

        cy.log("The delete button should be disabled because there is only one version left");
        cy.get(`[data-test=delete-button]`).should("be.disabled");
    });

    it("Folders CRUD", () => {
        cy.projectAdministratorSession();
        cy.visitProjectService(project_name, "Documents");
        cy.get("[data-test=document-header-actions]").within(() => {
            cy.get("[data-test=document-item-action-new-button]").click();

            cy.get("[data-test=document-new-folder-creation-button]").click();
        });

        cy.get("[data-test=document-new-folder-modal]").within(() => {
            cy.get("[data-test=document-new-item-title]").type("My new folder");
            cy.get("[data-test=document-property-description]").type(
                "With a description because I like to describe what I'm doing",
            );

            cy.get("[data-test=document-modal-submit-button-create-folder]").click();
        });
        openQuickLook("My new folder");
        deleteDocumentDisplayedInQuickLook();

        cy.get("[data-test=document-tree-content]").should("not.exist");
    });

    it("Empty CRUD", () => {
        cy.projectAdministratorSession();
        cy.visitProjectService(project_name, "Documents");
        cy.get("[data-test=document-header-actions]").within(() => {
            cy.get("[data-test=document-item-action-new-button]").click();
            cy.get("[data-test=document-new-empty-creation-button]").click();
        });
        cy.get("[data-test=document-new-item-modal]").within(() => {
            cy.get("[data-test=document-new-item-title]").type("My new empty document");
            cy.get("[data-test=document-modal-submit-button-create-item]").click();
        });
        openQuickLook("My new empty document");
        deleteDocumentDisplayedInQuickLook();

        cy.get("[data-test=document-tree-content]").should("not.exist");
    });

    it("Empty Item can be converted", () => {
        cy.projectAdministratorSession();
        cy.visitProjectService(project_name, "Documents");
        createEmptyAndOpenConvertModal("embedded");

        cy.get("[data-test=document-new-version-modal]").within(() => {
            cy.window().then((win) => {
                // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                // @ts-ignore
                win.CKEDITOR.instances["document-new-item-embedded"].setData(`En embbeded content`);
            });
            cy.get("[data-test=document-modal-submit-button-create-empty]").click();
        });

        checkThatEmptyHasTheCorrectType("embedded", "fa-file-lines", "tlp-swatch-inca-silver");
        deleteDocumentDisplayedInQuickLook();

        createEmptyAndOpenConvertModal("file");

        cy.get("[data-test=document-new-file-upload]").selectFile("./_fixtures/aa.txt");
        cy.get("[data-test=document-modal-submit-button-create-empty]").click();

        checkThatEmptyHasTheCorrectType("file", "fa-file-lines", "tlp-swatch-firemist-silver");
        deleteDocumentDisplayedInQuickLook();

        createEmptyAndOpenConvertModal("link");
        cy.get("[data-test=document-new-item-link-url]").type("https://example.com");
        cy.get("[data-test=document-modal-submit-button-create-empty]").click();

        checkThatEmptyHasTheCorrectType("link", "fa-link", "tlp-swatch-flamingo-pink");
        deleteDocumentDisplayedInQuickLook();
    });

    it("Link CRUD", () => {
        cy.projectAdministratorSession();
        cy.visitProjectService(project_name, "Documents");
        cy.get("[data-test=document-header-actions]").within(() => {
            cy.get("[data-test=document-item-action-new-button]").click();
            cy.get("[data-test=document-new-link-creation-button]").click();
        });

        cy.get("[data-test=document-new-item-modal]").within(() => {
            cy.get("[data-test=document-new-item-title]").type("My new link document");
            cy.get("[data-test=document-new-item-link-url]").type("https://example.com");
            cy.get("[data-test=document-modal-submit-button-create-item]").click();
        });
        openQuickLook("My new link document");

        cy.get("[data-test=document-quicklook-action-button-new-version").click({
            force: true,
        });

        cy.get("[data-test=document-new-version-modal]").within(() => {
            cy.get("[data-test=document-new-item-link-url]").clear();
            cy.get("[data-test=document-new-item-link-url]").type("https://example-bis.com");

            cy.get("[data-test=document-modal-submit-button-create-link-version]").click();
        });
        deleteDocumentDisplayedInQuickLook();

        cy.get("[data-test=document-tree-content]").should("not.exist");
    });

    it("Embedded file CRUD", () => {
        cy.projectAdministratorSession();
        cy.visitProjectService(project_name, "Documents");
        cy.get("[data-test=document-header-actions]").within(() => {
            cy.get("[data-test=document-item-action-new-button]").click();
            cy.get("[data-test=document-new-embedded-creation-button]").click();
        });

        cy.get("[data-test=document-new-item-modal]").within(() => {
            cy.get("[data-test=document-new-item-title]").type("My new html content");

            cy.window().then((win) => {
                // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                // @ts-ignore
                win.CKEDITOR.instances["document-new-item-embedded"].setData(
                    `<strong>This is the story of my life </strong>`,
                );
            });
            cy.get("[data-test=document-modal-submit-button-create-item]").click();
        });

        cy.get("[data-test=document-tree-content]")
            .contains("tr", "My new html content")
            .within(() => {
                // button is displayed on tr::hover, so we need to force click
                cy.get("[data-test=quick-look-button]").click({ force: true });
            });

        cy.log("User can access to specific embedded page view");
        cy.get("[data-test=embedded-cell-title]").click();
        cy.get("[data-test=embedded_content]").should("exist");

        cy.visitProjectService(project_name, "Documents");
        openQuickLook("My new html content");

        deleteDocumentDisplayedInQuickLook();

        cy.get("[data-test=document-tree-content]").should("not.exist");
    });

    it(`user can download a folder as a zip archive`, () => {
        cy.projectAdministratorSession();
        const project_name = "download-" + getAntiCollisionNamePart();
        createProjectWithDownloadableDocuments(project_name);

        cy.visitProjectService(project_name, "Documents");

        cy.get("[data-test=document-tree-content]")
            .contains("tr", "Folder download")
            .within(($row) => {
                // We cannot click the download button, otherwise the browser will ask "Where to save this file ?"
                // and will stop the test.
                cy.get("[data-test=document-dropdown-download-folder-as-zip]").should("exist");
                const folder_id = $row.data("itemId");
                if (folder_id === undefined) {
                    throw new Error("Could not retrieve the folder id from its <tr>");
                }
                const download_uri = `/plugins/document/${project_name}/folders/${encodeURIComponent(
                    folder_id,
                )}/download-folder-as-zip`;

                // Verify the download URI returns code 200 and has the correct headers
                cy.request({
                    url: download_uri,
                }).then((response) => {
                    expect(response.status).to.equal(200);
                    expect(response.headers["content-type"]).to.equal("application/zip");
                    expect(response.headers["content-disposition"]).to.equal(
                        'attachment; filename="Folder download.zip"',
                    );
                });
            });
    });

    it(`filesize threshold`, () => {
        cy.log("as site administrator define download limits");
        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=sidebar-plugins-section]").within(() => {
            cy.contains("Document").click();
        });
        cy.get("[data-test=document-download-limit-tabs]").within(() => {
            cy.contains("download").click();
        });
        cy.get("[data-test=error-threshold]").clear().type("2");
        cy.get("[data-test=warning-threshold]").clear().type("1");
        cy.get("[data-test=save-settings]").click();

        cy.projectAdministratorSession();
        cy.visitProjectService(project_size, "Documents");

        cy.log("create a folder with oversized content");
        cy.projectAdministratorSession();
        cy.visitProjectService(project_size, "Documents");

        const three_mega_filesize = 1024 * 1024 * 3;
        const three_mega_content = Cypress.Buffer.alloc(three_mega_filesize, "b");
        cy.writeFile("./_fixtures/error.txt", three_mega_content);

        cy.intercept("PATCH", "/uploads/docman/file/*").as("uploadFile");

        createFolderWithContent("Oversized folder", "./_fixtures/error.txt");
        cy.wait("@uploadFile");

        cy.log("User can't download it an error modal is displayed");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", "Oversized folder")
            .within(() => {
                cy.get("[data-test=document-dropdown-download-folder-as-zip]").click({
                    force: true,
                });
            });
        cy.get("[data-test=document-folder-size-threshold-exceeded]").contains("Maximum");
        cy.get("[data-test=close-max-archive-size-threshold-exceeded-modal]").click();

        cy.log("create a folder with a medium warning sized content");

        const one_mega_filesize = 1024 * 1024;
        const one_mega_content = Cypress.Buffer.alloc(one_mega_filesize, "b");
        cy.writeFile("./_fixtures/warning.txt", one_mega_content);

        createFolderWithContent("Warning folder", "./_fixtures/warning.txt");
        cy.wait("@uploadFile");
        cy.log("User has a warning modal displayed");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", "Warning folder")
            .within(() => {
                cy.get("[data-test=document-dropdown-download-folder-as-zip]").click({
                    force: true,
                });
            });
        cy.get("[data-test=document-folder-size-warning-modal]").contains("warning");
    });

    it(`User can add elements in folder from any drop down`, () => {
        cy.log("Use main dropdown to create a new folder");
        cy.projectAdministratorSession();
        cy.visitProjectService(project_name, "Documents");

        cy.log("Use main dropdown to create a file inside folder");
        cy.get("[data-test=document-new-item]").click();
        cy.get("[data-test=document-new-file-creation-button]").click();
        cy.get("[data-test=document-new-file-upload]").selectFile("./_fixtures/aa.txt");
        cy.get("[data-test=document-modal-submit-button-create-item]").click();

        cy.log("use main drop down to create a new folder");
        cy.get("[data-test=document-new-item]").click();
        cy.get("[data-test=document-new-folder-creation-button]").click();
        cy.get("[data-test=document-new-item-title]").type("Z folder");
        cy.get("[data-test=document-modal-submit-button-create-folder]").click();

        cy.log("Tree view display folder, then files");
        assertRows(0, "Z folder", false);
        assertRows(1, "aa.txt", false);

        cy.log("Use quicklook dropdown to create a folder inside folder");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", "Z folder")
            .within(() => {
                cy.get("[data-test=toggle]").click();
                // button is displayed on tr::hover, so we need to force click
                cy.get("[data-test=document-drop-down-button]").click({ force: true });
                cy.get("[data-test=document-folder-content-creation]").click();
                // force is needed because button is displayed at hover
                cy.get("[data-test=document-new-folder-creation-button]").click({ force: true });
            });

        cy.get("[data-test=document-new-item-title]").type("sub folder");
        cy.get("[data-test=document-modal-submit-button-create-folder]").click();

        assertRows(0, "Z folder", false);
        assertRows(1, "sub folder", false);
        assertRows(2, "aa.txt", false);

        cy.log("Fold main folder");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", "Z folder")
            .within(() => {
                cy.get("[data-test=toggle]").click();
            });

        cy.log("Sub elements are no longer displayed when folder is fold");
        assertRows(0, "Z folder", false);
        assertRows(1, "sub folder", true);
        assertRows(2, "aa.txt", false);

        cy.log("Create a new folder inside folded folder");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", "Z folder")
            .within(() => {
                cy.get("[data-test=document-drop-down-button]").click({ force: true });
                cy.get("[data-test=document-folder-content-creation]").click();
                cy.get("[data-test=document-new-folder-creation-button]").click({ force: true });
            });
        cy.get("[data-test=document-new-item-title]").type("An other sub folder");
        cy.get("[data-test=document-modal-submit-button-create-folder]").click();

        assertRows(0, "Z folder", false);
        assertRows(1, "An other sub folder", true);
        assertRows(2, "sub folder", true);
        assertRows(3, "aa.txt", false);
    });

    it(`Copy/paste`, () => {
        cy.projectAdministratorSession();
        cy.visitProjectService(project_copy_paste, "Documents");

        createFolderWithContent("AA", "./_fixtures/aa.txt");
        createFolderWithContent("BB", "./_fixtures/bb.txt");

        assertRows(0, "AA", false);
        assertRows(1, "aa.txt", false);
        assertRows(2, "BB", false);
        assertRows(3, "bb.txt", false);

        cy.log("user can copy/paste an item from folder AA to folder BB");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", "aa.txt")
            .within(() => {
                // button is displayed on tr::hover, so we need to force click
                cy.get("[data-test=copy-item]").click({ force: true });
            });
        cy.get("[data-test=document-tree-content]")
            .contains("tr", "BB")
            .within(() => {
                // button is displayed on tr::hover, so we need to force click
                cy.get("[data-test=paste-item]").click({ force: true });
            });

        assertRows(0, "AA", false);
        assertRows(1, "aa.txt", false);
        assertRows(2, "BB", false);
        assertRows(3, "aa.txt", false);
        assertRows(4, "bb.txt", false);

        cy.log("user can cut/paste folder BB into folder AA");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", "AA")
            .within(() => {
                // button is displayed on tr::hover, so we need to force click
                cy.get("[data-test=cut-item]").click({ force: true });
            });
        cy.get("[data-test=document-tree-content]")
            .contains("tr", "BB")
            .within(() => {
                // button is displayed on tr::hover, so we need to force click
                cy.get("[data-test=paste-item]").click({ force: true });
            });
        assertRows(0, "BB", false);
        assertRows(1, "AA", false);
        assertRows(2, "aa.txt", false);
        assertRows(3, "aa.txt", false);
        assertRows(4, "bb.txt", false);
    });
});

function createProjectWithDownloadableDocuments(project_name: string): void {
    cy.createNewPublicProject(project_name, "issues")
        .then((document_project_id) =>
            cy.getFromTuleapAPI<ProjectServiceResponse>(
                `api/projects/${document_project_id}/docman_service`,
            ),
        )
        .then((response) => {
            const root_folder_id = response.body.root_item.id;

            const folder_payload = {
                title: "Folder download",
                description: "",
                type: "folder",
            };
            return cy.postFromTuleapApi<CreatedItemResponse>(
                `api/docman_folders/${root_folder_id}/folders`,
                folder_payload,
            );
        })
        .then((response) => {
            const folder = response.body.id;
            const item = {
                title: "test",
                description: "",
                type: "embedded",
                embedded_properties: {
                    content:
                        "<strong>Our deeds determine us, as much as we determine our deeds.</strong>",
                },
            };
            return cy.postFromTuleapApi(`api/docman_folders/${folder}/embedded_files`, item);
        });
}

function createProjectWithAVersionnedEmbededFile(): void {
    const project_shortname = "doc-version-" + getAntiCollisionNamePart();
    cy.createNewPublicProject(project_shortname, "issues").then((project_id) =>
        cy
            .getFromTuleapAPI<ProjectServiceResponse>(`api/projects/${project_id}/docman_service`)
            .then((response) => {
                const root_folder_id = response.body.root_item.id;
                const embedded_payload = {
                    title: "test",
                    description: "",
                    type: "embedded",
                    embedded_properties: {
                        content: "<p>embedded</p>\n",
                    },
                    should_lock_file: false,
                };
                return cy.postFromTuleapApi<CreatedItemResponse>(
                    `api/docman_folders/${root_folder_id}/embedded_files`,
                    embedded_payload,
                );
            })
            .then((response) => response.body.id)
            .then((item) => {
                const updated_embedded_payload = {
                    embedded_properties: {
                        content: "<p>updated content</p>\n",
                    },
                    should_lock_file: false,
                };
                cy.postFromTuleapApi(
                    `api/docman_embedded_files/${item}/versions`,
                    updated_embedded_payload,
                );
                cy.visit(`/plugins/document/${project_shortname}/versions/${item}`);
            }),
    );
}

function createEmptyAndOpenConvertModal(type: string): void {
    cy.get("[data-test=document-header-actions]").within(() => {
        cy.get("[data-test=document-item-action-new-button]").click();
        cy.get("[data-test=document-new-empty-creation-button]").click();
    });
    cy.get("[data-test=document-new-item-modal]").within(() => {
        cy.get("[data-test=document-new-item-title]").type(`Empty for ${type}`);
        cy.get("[data-test=document-modal-submit-button-create-item]").click();
    });

    openQuickLook(`Empty for ${type}`);
    cy.log(`Empty can be converted into an ${type}`);

    cy.get("[data-test=document-quick-look]").within(() => {
        cy.get("[data-test=document-new-item]").click();
        cy.get(`[data-test=document-new-${type}-creation-button]`).click();
    });
}

function checkThatEmptyHasTheCorrectType(
    type: string,
    icon_type: string,
    icon_color: string,
): void {
    cy.log("check that document have correct type in tree view");
    cy.get("[data-test=document-tree-content]")
        .contains("tr", `Empty for ${type}`)
        .within(() => {
            cy.get(`[data-test=${type}-icon]`).should("have.class", icon_type);
            cy.get(`[data-test=${type}-icon]`).should("have.class", icon_color);
        });
}

function createFolderWithContent(folder_name: string, file_path: string): void {
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

function assertRows(index: number, docuent_name: string, is_hidden: boolean): void {
    cy.get("[data-test=document-tree-content] tr")
        .eq(index)
        .find("td")
        .eq(0)
        .contains(docuent_name);
    cy.get("[data-test=document-tree-content] tr")
        .eq(index)
        .should(is_hidden ? "have.class" : "not.have.class", "document-tree-item-hidden");
}
