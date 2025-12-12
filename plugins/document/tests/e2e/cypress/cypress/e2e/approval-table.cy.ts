/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

describe("Approval table workflow", () => {
    let now: number;
    let project_name: string;

    before(() => {
        now = Date.now();
        project_name = `approval-table-${now}`;
        cy.projectAdministratorSession();
        cy.createNewPublicProject(project_name, "issues").as("project_id");
        cy.addProjectMember(project_name, "ProjectMember");
    });

    it("can use approval tables", () => {
        cy.projectAdministratorSession();
        cy.visitProjectService(project_name, "Documents");

        cy.log("Create an embedded file");
        cy.get("[data-test=document-header-actions]").within(() => {
            cy.get("[data-test=document-item-action-new-button]").click();
            cy.get("[data-test=document-new-embedded-creation-button]").click();
        });

        const document_name = "My document";
        cy.get("[data-test=document-new-item-title]").type(document_name);
        cy.get("[data-test=document-modal-submit-button-create-item]").click();

        cy.log("Create approval table");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", document_name)
            .within(() => {
                // eslint-disable-next-line cypress/no-force
                cy.get("[data-test=document-dropdown-approval-tables]").click({ force: true });
            });
        cy.get("[data-test=creation-button]").click();
        cy.searchItemInListPickerDropdown("Project members").click();
        cy.get("[data-test=create-table-button]").click();
        cy.log("And enable it");
        cy.get("[data-test=table-admin-button]").click();
        cy.get("[data-test=table-status-select]").select("enabled");
        cy.get("[data-test=update-table-button]").click();
        cy.get("[data-test=reviewer-row]").contains("ProjectMember");

        cy.log("ProjectMember do its review");
        cy.projectMemberSession();
        cy.visitProjectService(project_name, "Documents");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", document_name)
            .within(() => {
                // eslint-disable-next-line cypress/no-force
                cy.get("[data-test=document-dropdown-approval-tables]").click({ force: true });
            });
        cy.get("[data-test=review-modal-trigger-button]").click();
        cy.get("[data-test=review-select-state]").select("rejected");
        cy.get("[data-test=review-comment]").type("I do not like it, please do some changes!");
        cy.get("[data-test=send-review-button]").click();
        cy.get("[data-test=reviewer-state]").contains("Rejected");

        cy.log("Create new version");
        cy.projectAdministratorSession();
        cy.visitProjectService(project_name, "Documents");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", document_name)
            .within(() => {
                // eslint-disable-next-line cypress/no-force
                cy.get("[data-test=document-dropdown-create-new-version-button]").click({
                    force: true,
                });
            });
        cy.get("[data-test=approval-table-action-checkbox]").check("reset");
        cy.get("[data-test=document-modal-submit-button-create-embedded-version]").click();

        cy.log("Remove last table and create it again");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", document_name)
            .within(() => {
                // eslint-disable-next-line cypress/no-force
                cy.get("[data-test=document-dropdown-approval-tables]").click({ force: true });
            });
        cy.get("[data-test=table-admin-button]").click();
        cy.get("[data-test=delete-table-button]").click();
        cy.get("[data-test=delete-confirmation-table-button]").click();
        cy.get("[data-test=table-admin-button]").click();
        cy.get("[data-test=missing-table-warning]").should("be.visible");
        cy.get("[data-test=missing-table-action-checkbox]").check("copy");
        cy.get("[data-test=update-table-button]").click();
        cy.get("[data-test=table-admin-button]").click();
        cy.get("[data-test=table-status-select]").select("enabled");
        cy.get("[data-test=update-table-button]").click();

        cy.log("Both reviewers do their review");
        cy.get("[data-test=review-modal-trigger-button]").click();
        cy.get("[data-test=review-select-state]").select("approved");
        cy.get("[data-test=send-review-button]").click();
        cy.projectMemberSession();
        cy.visitProjectService(project_name, "Documents");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", document_name)
            .within(() => {
                // eslint-disable-next-line cypress/no-force
                cy.get("[data-test=document-dropdown-approval-tables]").click({ force: true });
            });
        cy.get("[data-test=review-modal-trigger-button]").click();
        cy.get("[data-test=review-select-state]").select("approved");
        cy.get("[data-test=review-comment]").clear();
        cy.get("[data-test=send-review-button]").click();
        cy.get("[data-test=history-row]").first().contains("Approved");

        cy.log("Remove all tables");
        cy.get("[data-test=table-admin-button]").click();
        cy.get("[data-test=delete-table-button]").click();
        cy.get("[data-test=delete-confirmation-table-button]").click();
        cy.get("[data-test=table-admin-button]").click();
        cy.get("[data-test=delete-table-button]").click();
        cy.get("[data-test=delete-confirmation-table-button]").click();
        cy.get("[data-test=creation-button]").should("be.visible");
    });
});
