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

import * as quotedPrintable from "quoted-printable";
import { getAntiCollisionNamePart, WEB_UI_SESSION } from "@tuleap/cypress-utilities-support";

function newSessionForInvitedUser(user_name: string): void {
    cy.session([WEB_UI_SESSION, `/${user_name}`], () => {
        cy.visit("/");
        // Do not log in
    });
}

describe("Invitations", () => {
    describe("In a project", () => {
        let anti_collision: string, project_name: string, invitee_email: string;

        before(() => {
            anti_collision = getAntiCollisionNamePart();
            project_name = `test-invitations-${anti_collision}`;
            invitee_email = `email-${anti_collision}@example.com`;

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

        it("users should be able to invite teamworkers", () => {
            cy.deleteAllMessagesInMailbox();

            cy.log("Ensure that users can invite other people");
            cy.siteAdministratorSession();
            cy.visit("/admin/");
            cy.get("[data-test=site-admin-invitations]").click();
            cy.get("[data-test=max-invitations-by-day]").type("{selectAll}50");
            cy.get("[data-test=save-invitations-settings]").click();

            cy.log("Invite a Tuleap user and an external email into a specific project");
            cy.projectAdministratorSession();
            cy.visit(`/projects/${project_name}`);
            cy.get("[data-test=invite-buddies-button]").click();
            const user_invited_in_project = `InvitedInProject${anti_collision}`;
            const user_invited_in_project_mail = `user-invited-in-project${anti_collision}@example.com`;
            cy.get("[data-test=invite-buddies-email]").type(
                `SecondProjectAdministrator@example.com,${user_invited_in_project_mail}`,
            );
            cy.get("[data-test=invite-buddies-submit]").click();

            cy.log("Tuleap user receive a mail to connect to Tuleap");
            cy.assertEmailWithContentReceived(
                "SecondProjectAdministrator@example.com",
                "You are now a member",
            );

            cy.log("external user receive a mail to register to Tuleap");
            cy.assertEmailWithContentReceived(
                user_invited_in_project_mail,
                "ProjectAdministrator invited you to register to Tuleap and join the project",
            );

            cy.log("Project administrators can re send invitation");
            cy.visit(`/projects/${project_name}`);
            cy.get('[data-test="project-administration-link"]', { includeShadowDom: true }).click();
            cy.get("[data-test=resend-user-invitations-pane]").contains(
                user_invited_in_project_mail,
            );

            cy.log("Check that users are created without approbation");
            extractInviteUrlFromEmail(user_invited_in_project_mail).then((url) => {
                newSessionForInvitedUser(user_invited_in_project);
                cy.visit(url);
            });

            cy.log("As non existing user register to Tuleap...");
            cy.get("[data-test=user-login]").type(user_invited_in_project);
            cy.get("[data-test=user-pw]").type("welcome0");
            cy.get("[data-test=user-pw2]").type("welcome0");
            cy.get("[data-test=user-name]").type(user_invited_in_project);

            cy.get("[data-test=register-user-button]").click();

            cy.log("...and be welcomed on Tuleap project");
            cy.get("[data-test=first-timer-success-modal-user-greetings]").contains(
                user_invited_in_project,
            );
            cy.get("[data-test=first-timer-success-modal-project-greetings]").contains(
                project_name,
            );

            cy.deleteAllMessagesInMailbox();

            cy.log("Invite a Tuleap user and an external email In Tuleap");
            cy.projectAdministratorSession();
            cy.visit(`/`);
            cy.get("[data-test=invite-buddies-button]").click();
            const user_invited_in_tuleap = `InvitedInTuleap${anti_collision}`;
            const user_invited_in_tuleap_mail = `user-invited-in-Tuleap${anti_collision}@example.com`;
            cy.get("[data-test=invite-buddies-email]").type(
                `RestrictedMember@example.com,${user_invited_in_tuleap_mail}`,
            );
            cy.get("[data-test=invite-buddies-submit]").click();

            cy.log("Tuleap user receive a mail to connect to Tuleap");
            cy.assertEmailWithContentReceived("RestrictedMember@example.com", "To sign in");

            cy.log("external user receive a mail to register to Tuleap");
            cy.assertEmailWithContentReceived(
                user_invited_in_tuleap_mail,
                "ProjectAdministrator invited you to register to Tuleap.",
            );

            cy.log("Check that users are created without approbation");
            extractInviteUrlFromEmail(user_invited_in_tuleap_mail).then((url) => {
                newSessionForInvitedUser(user_invited_in_tuleap);
                cy.visit(url);
            });

            cy.log("As non existing user register to Tuleap...");
            cy.get("[data-test=user-login]").type(user_invited_in_tuleap);
            cy.get("[data-test=user-pw]").type("welcome0");
            cy.get("[data-test=user-pw2]").type("welcome0");
            cy.get("[data-test=user-name]").type(user_invited_in_tuleap);

            cy.get("[data-test=register-user-button]").click();

            cy.log("...and be welcomed on Tuleap");
            cy.get("[data-test=first-timer-success-modal-user-greetings]").contains(
                user_invited_in_tuleap,
            );
            cy.get("[data-test=first-timer-success-modal-project-greetings]").contains(
                "Welcome to Tuleap",
            );
        });
    });
});

function extractInviteUrlFromEmail(user_email: string): Cypress.Chainable<string> {
    return cy
        .request({
            method: "GET",
            url:
                "http://mailhog:8025/api/v2/search?kind=to&query=" + encodeURIComponent(user_email),
            headers: {
                accept: "application/json",
            },
        })
        .then((response) => {
            const last_email = response.body.items[0];
            const body = last_email.Content.Body;

            const url = extractInvitationUrlFromEmail(body);

            if (!url) {
                throw new Error("Invitation Url not found in mail of user " + user_email);
            }

            return url;
        });
}

function extractInvitationUrlFromEmail(email_content: string): string | null {
    const decoded = quotedPrintable.decode(email_content);

    const match = decoded.match(/https:\/\/\S+/);
    return match ? match[0] : null;
}

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
