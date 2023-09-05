/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

function visitProjectAdmin(project_id: string): void {
    cy.visit(`/plugins/oauth2_server/project/${encodeURIComponent(project_id)}/admin`);
}

describe("OIDC flow", function () {
    before(function () {
        cy.projectAdministratorSession();
        cy.getProjectId("oidc-flow").as("project_id");
    });

    it("setup a OAuth2 app to sign in on a third party service", function () {
        cy.projectAdministratorSession();
        visitProjectAdmin(this.project_id);
        cy.get("[data-test=oauth2-create-app-button]").click();

        cy.get("[data-test=oauth2-new-app-modal]").within(() => {
            cy.get("[data-test=oauth2-new-app-name]").type("Test OIDC flow");
            cy.get("[data-test=oauth2-new-app-redirect-uri]").type(
                "https://oauth2-server-rp-oidc:8443/callback",
            );

            cy.get("[data-test=oauth2-new-app-modal-submit-button]").click();
        });

        cy.get("[data-test=oauth2-new-secret-success]").then(($success_message) => {
            const client_id = $success_message.attr("data-oauth2-new-app-client-id");
            cy.wrap(client_id).should("not.be.empty");
            const client_secret = $success_message.attr("data-oauth2-new-app-client-secret");
            cy.wrap(client_secret).should("not.be.empty");

            cy.request({
                url: `https://oauth2-server-rp-oidc:8443/init-flow?client_id=${encodeURIComponent(
                    client_id ?? "",
                )}&client_secret=${encodeURIComponent(client_secret ?? "")}`,
                followRedirect: false,
            }).then(function (resp) {
                cy.visit(String(resp.headers.location));
                cy.get("[data-test=oauth2-authorize-request-submit-button]").click();
                cy.contains("OK as ProjectAdministrator");
            });
        });
    });

    it(`Project Administrator can manage OAuth2 Apps`, function () {
        cy.projectAdministratorSession();
        visitProjectAdmin(this.project_id);
        // Create an app
        cy.get("[data-test=oauth2-create-app-button]").click();

        cy.get("[data-test=oauth2-new-app-modal]")
            .filter(".tlp-modal-shown")
            .within(() => {
                cy.get("[data-test=oauth2-new-app-name]").type("Test OAuth2 App Management");
                cy.get("[data-test=oauth2-new-app-redirect-uri]").type("https://example.com");

                cy.get("[data-test=oauth2-new-app-modal-submit-button]").click();
            });

        // Generate a new Client Secret
        cy.get("[data-test=oauth2-app-row]")
            .contains("Test OAuth2 App Management")
            .parent("[data-test=oauth2-app-row]")
            .within(() => {
                return cy.get("[data-test=oauth2-new-client-secret-button]").click();
            });
        cy.get("[data-test=oauth2-new-secret-modal]")
            .filter(".tlp-modal-shown")
            .within(() => {
                return cy.get("[data-test=oauth2-new-secret-modal-submit-button]").click();
            });
        cy.get("[data-test=oauth2-new-secret-success]").should("be.visible");

        // Edit the app
        cy.get("[data-test=oauth2-app-row]")
            .contains("Test OAuth2 App Management")
            .parent("[data-test=oauth2-app-row]")
            .within(() => {
                return cy.get("[data-test=oauth2-edit-app-button]").click();
            });
        cy.get("[data-test=oauth2-edit-app-modal]")
            .filter(".tlp-modal-shown")
            .within(() => {
                cy.get("[data-test=oauth2-edit-app-name]")
                    .should("have.value", "Test OAuth2 App Management")
                    .clear()
                    .type("My OIDC App");
                cy.get("[data-test=oauth2-edit-app-redirect-uri]")
                    .should("have.value", "https://example.com")
                    .clear()
                    .type("https://example.com/redirect");
                cy.get("[data-test=oauth2-edit-app-use-pkce]").should("be.checked").uncheck();
                cy.get("[data-test=oauth2-edit-app-modal-submit-button]").click();
            });

        cy.get("[data-test=oauth2-app-row]").should("contain", "My OIDC App");

        // Delete the app
        cy.get("[data-test=oauth2-app-row]")
            .contains("My OIDC App")
            .parent("[data-test=oauth2-app-row]")
            .within(() => {
                return cy.get("[data-test=oauth2-delete-app-button]").click();
            });
        cy.get("[data-test=oauth2-delete-app-modal]")
            .filter(".tlp-modal-shown")
            .within(() => {
                cy.get("[data-test=oauth2-delete-app-modal-submit-button]").click();
            });

        cy.get("[data-test=oauth2-app-row]").and(($row) => {
            expect($row).not.to.contain("My OIDC App");
        });
    });
});
