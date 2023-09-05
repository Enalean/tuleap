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

import { createAWikiDocument } from "../support/create-document";
import {
    deleteDocumentDisplayedInQuickLook,
    openQuickLook,
    updateWikiPage,
} from "../support/helpers";

function createProjectFromTemplate(project_name: string): void {
    cy.getProjectId("document-phpwiki-template").then((template_id) => {
        const payload = {
            shortname: project_name,
            description: "",
            label: project_name,
            is_public: true,
            categories: [],
            fields: [],
            template_id: template_id,
            allow_restricted: false,
        };
        return cy.postFromTuleapApi("https://tuleap/api/projects/", payload).then((response) => {
            return Number.parseInt(response.body.id, 10);
        });
    });
}

function notifyMe(): void {
    cy.get("[data-test=document-quick-look]").within(() => {
        cy.get("[data-test=notifications-menu-link]").click({ force: true });
    });

    cy.get("[data-test=notify-me-checkbox]").click();
    cy.get("[data-test=submit-notification-button]").click();
}

function addUserToNotifiedPeople(user_name: string): void {
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-container").click();
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-input").type(`${user_name}{enter}`);
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-result-label").last().click();

    cy.get("[data-test=validate-notification-button]").click();
    cy.get("[data-test=feedback]").contains(`Monitoring for user(s) "${user_name}" has been added`);
}

function createFolderInProject(project_suscribers: string): void {
    cy.getProjectId(project_suscribers).then((project_id) => {
        cy.getFromTuleapAPI(`api/projects/${project_id}/docman_service`).then((response) => {
            const root_folder_id = response.body.root_item.id;

            const folder_payload = {
                title: "Folder",
                description: "",
                type: "folder",
            };
            return cy.postFromTuleapApi(
                `api/docman_folders/${root_folder_id}/folders`,
                folder_payload,
            );
        });
    });
}

describe("Document notifications", () => {
    let now = 0;
    let project_wiki_notif = "";
    let project_suscribers = "";
    let public_doc_mail = "";
    let private_doc_mail = "";
    before(() => {
        now = Date.now();
        project_wiki_notif = "wiki-notif" + now;
        project_suscribers = "doc-suscribers" + now;
        public_doc_mail = "public-doc-mail-" + now;
        private_doc_mail = "private-doc-mail-" + now;

        cy.projectAdministratorSession();
        createProjectFromTemplate(project_wiki_notif);
        cy.createNewPublicProject(project_suscribers, "issues");
        cy.createNewPublicProject(public_doc_mail, "issues");
        cy.createNewPrivateProject(private_doc_mail);
    });

    it("User can monitor a wiki document", () => {
        cy.projectAdministratorSession();
        cy.getProjectId(project_wiki_notif).then((project_id) => {
            createAWikiDocument(`A wiki document${now}`, "Wiki page", project_id);
        });

        cy.visitProjectService(project_wiki_notif, "Documents");
        openQuickLook(`A wiki document${now}`);
        notifyMe();

        cy.visitProjectService(project_wiki_notif, "Documents");
        cy.get("[data-test=wiki-document-link]").click();
        cy.get("[data-test=create-wiki]").click();

        cy.visitProjectService(project_wiki_notif, "Wiki");

        cy.visitProjectService(project_wiki_notif, "Documents");
        cy.get("[data-test=wiki-document-link]").click();

        updateWikiPage("My wiki content");
        cy.log("assertion 1");
        cy.assertUserMessagesReceivedByWithSpecificContent(
            "ProjectAdministrator@example.com",
            "New version of Wiki page wiki page was created by ProjectAdministrator",
        );
        cy.log("assertion 2");
        cy.assertUserMessagesReceivedByWithSpecificContent(
            "ProjectAdministrator@example.com",
            "https://tuleap/wiki/index.php?pagename=Wiki%20page&action=diff",
        );
    });

    it("User receives a notification when document is deleted", () => {
        cy.projectAdministratorSession();
        cy.visitProjectService(public_doc_mail, "Documents");
        createFolderInProject(public_doc_mail);

        cy.visitProjectService(public_doc_mail, "Documents");
        openQuickLook(`Folder`);
        notifyMe();

        cy.visitProjectService(public_doc_mail, "Documents");
        openQuickLook(`Folder`);

        cy.intercept("/api/docman_folders/*").as("deleteFolders");
        deleteDocumentDisplayedInQuickLook();
        cy.wait("@deleteFolders", { timeout: 3000 });

        cy.assertUserMessagesReceivedByWithSpecificContent(
            "ProjectAdministrator@example.com",
            `Folder has been removed by ProjectAdministrator.`,
        );
    });

    it("Document manager can manage notifications subscribers", () => {
        cy.projectAdministratorSession();
        cy.visitProjectService(project_suscribers, "Documents");
        createFolderInProject(project_suscribers);
        cy.visitProjectService(project_suscribers, "Documents");
        openQuickLook(`Folder`);
        cy.get("[data-test=document-quick-look]").within(() => {
            cy.get("[data-test=notifications-menu-link]").click({ force: true });
        });

        addUserToNotifiedPeople("ProjectMember");

        // remove myself from suscribers
        cy.visitProjectService(project_suscribers, "Documents");
        openQuickLook(`Folder`);
        notifyMe();

        cy.get("[data-test=user_to_be_notified]").first().click();
        cy.get("[data-test=validate-notification-button]").click();

        cy.get("[data-test=feedback]").contains("Removed monitoring for user(s) ");
    });

    it("Set a project as private will remove non project members watching document", () => {
        cy.projectAdministratorSession();
        cy.visitProjectService(public_doc_mail, "Documents");

        createFolderInProject(public_doc_mail);

        cy.visitProjectService(public_doc_mail, "Documents");
        openQuickLook(`Folder`);
        cy.get("[data-test=document-quick-look]").within(() => {
            cy.get("[data-test=notifications-menu-link]").click({ force: true });
        });

        addUserToNotifiedPeople("ARegularUser");
        cy.get("[data-test=notified-users]").should("contain", "ARegularUser");

        cy.visitProjectAdministration(public_doc_mail);
        cy.switchProjectVisibility("private");

        cy.visitProjectService(public_doc_mail, "Documents");
        openQuickLook(`Folder`);
        cy.get("[data-test=document-quick-look]").within(() => {
            cy.get("[data-test=notifications-menu-link]").click({ force: true });
        });
        cy.get("[data-test=notified-users]").should("not.contain", "ARegularUser");
    });

    it("Removing users from private projects will remove them from notification monitoring", () => {
        cy.projectAdministratorSession();
        cy.visitProjectService(private_doc_mail, "Documents");
        cy.addProjectMember("ProjectMember");

        createFolderInProject(private_doc_mail);

        cy.visitProjectService(private_doc_mail, "Documents");
        openQuickLook(`Folder`);
        cy.get("[data-test=document-quick-look]").within(() => {
            cy.get("[data-test=notifications-menu-link]").click({ force: true });
        });

        addUserToNotifiedPeople("ProjectMember");
        cy.get("[data-test=notified-users]").should("contain", "ProjectMember");
        cy.removeProjectMember("ProjectMember");

        cy.visitProjectService(private_doc_mail, "Documents");
        openQuickLook(`Folder`);
        cy.get("[data-test=document-quick-look]").within(() => {
            cy.get("[data-test=notifications-menu-link]").click({ force: true });
        });
        cy.get("[data-test=notified-users]").should("not.contain", "ProjectMember");
    });
});
