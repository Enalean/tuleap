/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

describe("User dashboards", function () {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.projectMemberLogin();
    });

    beforeEach(function () {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
    });

    it("User should be able to manipulate widgets", function () {
        cy.visit("/my/");

        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-delete-tab-button]").click();
        cy.get("[data-test=dashboard-confirm-delete-button]").click();

        cy.get("[data-test=dashboard-add-button]").click();
        cy.get("[data-test=dashboard-add-input-name]").type("My basic dashboard");
        cy.get("[data-test=dashboard-add-button-submit]").click();

        // widget image
        cy.get("[data-test=dashboard-add-widget-empty-state-button]").click();
        cy.get("[data-test=myimageviewer]").click();
        cy.get("[data-test=dashboard-widget-image-input-url]").type(
            "https://tuleap/images/organization_logo.png"
        );
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();
        cy.get("[data-test=dashboard-widget-myimageviewer]")
            .find("img")
            .should("have.attr", "src", "https://tuleap/images/organization_logo.png");

        // widget my artifacts
        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-add-widget-button]").click();

        cy.get("[data-test=plugin_tracker_myartifacts]").click();
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();
        cy.get("[data-test=dashboard-my-artifacts-content]");

        // widget my projects
        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-add-widget-button]").click();
        cy.get("[data-test=myprojects]").click();
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();
        cy.get("[data-test=dashboard-my-projects]").find("td").contains("User dashboard");
    });
});
