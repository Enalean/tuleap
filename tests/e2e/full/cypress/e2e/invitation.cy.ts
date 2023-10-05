/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

describe("Invitations", () => {
    describe("In a project", () => {
        let now: number, project_name: string, invitee_email: string;

        before(() => {
            now = Date.now();
            project_name = "test-invitations-" + now;
            invitee_email = `email-${now}@example.com`;

            cy.projectAdministratorSession();
            cy.createNewPublicProject(project_name, "issues");
            cy.addProjectMember(project_name, "projectMember");
            cy.projectAdministratorSession();
            cy.visitProjectAdministration(project_name);

            cy.get("[data-test=admin-nav-groups]").click();

            cy.log("Add 'delegated' group to project");
            cy.get("[data-test=project-admin-ugroups-modal]").click();
            cy.get("[data-test=ugroup_name]").type("delegated");
            cy.get("[data-test=create-user-group]").click();

            cy.get("[data-test=select-member-to-add-in-ugroup] + .select2-container").click();
            // ignore rule for select2
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-search__field").type("projectMember{enter}");
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-result-user").click();
            cy.get('[data-test="project-admin-submit-add-member"]').click();

            // all membership pane is outside of viewport, need to force true every action
            cy.get("[data-test=membership-management]").check({ force: true });
            cy.get("[data-test=save-delegated-permissions]").click({ force: true });
        });

        it("should let delegated users manage invitations", () => {
            cy.projectMemberSession();
            cy.visit(`/projects/${project_name}`);
            invite(invitee_email);
            assertNumberOfEmailMessagesReceivedBy(invitee_email, 1);

            cy.visitProjectAdministrationInCurrentProject();

            cy.log("Resend an invitation");
            cy.contains("tr", invitee_email).within(() => {
                cy.get("[data-test=resend-invitation]").click();
            });
            cy.contains("Invitation has been resent");
            assertNumberOfEmailMessagesReceivedBy(invitee_email, 2);

            cy.log("Withdraw an invitation");
            cy.contains("tr", invitee_email).within(() => {
                cy.get("[data-test=withdraw-invitation]").click();
            });
            cy.get("[data-test=withdraw-invitation-confirm]").click();
            cy.contains("Invitation has been withdrawn");
            cy.contains("tr", invitee_email).should("not.exist");
        });
    });
});

function invite(invitee_email: string): void {
    cy.get("[data-test=invite-buddies-button]").click();
    cy.get("[data-test=invite-buddies-email]").type(invitee_email);
    cy.get("[data-test=invite-buddies-submit]").click();
    cy.get("[data-test=invite-buddies-close]").click();
}

function assertNumberOfEmailMessagesReceivedBy(email: string, expected: number): void {
    cy.request({
        method: "GET",
        url: "http://mailhog:8025/api/v2/search?kind=to&query=" + encodeURIComponent(email),
        headers: {
            accept: "application/json",
        },
    }).then((response) => {
        expect(response.body).to.have.property("total", expected);
    });
}
