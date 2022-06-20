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

describe("User dashboards", function () {
    before(() => {
        cy.clearSessionCookie();
        cy.platformAdminLogin();
    });

    beforeEach(function () {
        cy.preserveSessionCookies();
    });

    it("User should be able to manipulate widgets", function () {
        cy.visit("/my/");

        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-delete-tab-button]").click();
        cy.get("[data-test=dashboard-confirm-delete-button]").click();

        cy.get("[data-test=dashboard-add-button]").click();
        cy.get("[data-test=dashboard-add-input-name]").type("My Dashboard");
        cy.get("[data-test=dashboard-add-button-submit]").click();

        // widget document
        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-add-widget-button]").click();
        cy.get("[data-test=plugin_docman_mydocman_search]").click();
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();
        cy.get("[data-test=document-search-id]").type("5");

        cy.get("[data-test=document-button-search]").click();
        cy.get("[data-test=document-search-link]").contains("empty");
    });
});
