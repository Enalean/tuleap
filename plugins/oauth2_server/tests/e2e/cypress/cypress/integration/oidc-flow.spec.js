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

describe("OIDC flow", function () {
    before(function () {
        cy.ProjectAdministratorLogin();
        cy.getProjectId("oidc-flow").as("project_id");
    });

    it("setup a OAuth2 app to sign in on a third party service", function () {
        cy.visit(`/plugins/oauth2_server/project/${encodeURIComponent(this.project_id)}/admin`);
        cy.get("[data-test=oauth2-create-app-button]").click();

        cy.get("[data-test=oauth2-new-app-modal]").within(() => {
            cy.get("[data-test=oauth2-new-app-name]").type("Test OIDC flow");
            cy.get("[data-test=oauth2-new-app-redirect-uri]").type(
                "https://oauth2-server-rp-oidc:8443/callback"
            );

            cy.get("[data-test=oauth2-new-app-modal-submit-button]").click();
        });

        cy.get("[data-test=oauth2-app-creation-success]").then(($success_message) => {
            const client_id = $success_message.attr("data-oauth2-new-app-client-id");
            cy.wrap(client_id).should("not.be.empty");
            const client_secret = $success_message.attr("data-oauth2-new-app-client-secret");
            cy.wrap(client_secret).should("not.be.empty");

            cy.request({
                url: `https://oauth2-server-rp-oidc:8443/init-flow?client_id=${encodeURIComponent(
                    client_id
                )}&client_secret=${encodeURIComponent(client_secret)}`,
                followRedirect: false,
            }).then(function (resp) {
                cy.visit(resp.headers.location);
                cy.get("[data-test=oauth2-authorize-request-submit-button]").click();
                cy.contains("OK");
            });
        });
    });
});
