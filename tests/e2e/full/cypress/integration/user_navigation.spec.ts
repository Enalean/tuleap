/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

describe("Navigation", function () {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.projectMemberLogin();
    });

    beforeEach(function () {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");

        // eslint-disable-next-line cypress/require-data-selectors
        cy.get("body").as("body");
    });

    it("User can access to its dashboard with mouse", function () {
        cy.visit("/");

        cy.get("[data-test=my-dashboard]").click();
        cy.get("[data-test=my-dashboard-option]").contains("My Dashboard");
        cy.get("[data-test=my-dashboard-option]").click();

        cy.get("[data-test=my-dashboard-title]").contains("My Dashboard");
    });

    it("User can access to its dashboard with keyboard", function () {
        cy.visit("/");

        cy.get("@body").type("d");

        //user is directly redirected to its personal dashboard
        cy.get("[data-test=my-dashboard-title]").contains("My Dashboard");
    });

    it("User can create a project with keyboard navigation", function () {
        cy.visit("/");

        cy.get("@body").type("c");
        cy.get("[data-test=create-new-item]").contains("Start a new project");
    });

    it("User can switch project with keyboard navigation", function () {
        cy.visit("/");

        cy.get("@body").type("s");

        //we click randomly on a project to ensure that navigation can happen
        cy.get("[data-test=project-link").first().click();
    });
});
