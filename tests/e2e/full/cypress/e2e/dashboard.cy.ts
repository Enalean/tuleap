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

describe("Hide widget", function () {
    it("given widget is not available on platform, then it must never be displayed", function () {
        cy.siteAdministratorSession();
        cy.visit("/admin/project-creation/widgets");

        cy.get("[data-test=project-widgets-checkbox-projectheartbeat]").click({ force: true });

        cy.createNewPublicProject("dashboard", "agile_alm");
        cy.visit("/projects/dashboard");

        cy.get("[data-test=dashboard-widget-projectnote]");
        cy.get("[data-test=dashboard-widget-projectheartbeat]").should("not.exist");

        //enable heartbeat again
        cy.visit("/admin/project-creation/widgets");
        cy.get("[data-test=project-widgets-checkbox-projectheartbeat]").click({ force: true });
    });

    it("User should be able to manipulate widgets", function () {
        cy.siteAdministratorSession();
        cy.visit("/my/");

        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-delete-tab-button]").click();
        cy.get("[data-test=dashboard-confirm-delete-button]").click();

        cy.get("[data-test=dashboard-add-button]").click();
        cy.get("[data-test=dashboard-add-input-name]").type("My Dashboard");
        cy.get("[data-test=dashboard-add-button-submit]").click();

        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-add-widget-button]").click();
        cy.get("[data-test=mysystemevent]").click();
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();
        cy.get("[data-test=dashboard-widgets-list]").contains("System events");
    });

    it("Project member should be able to manipulate widgets", function () {
        cy.projectMemberSession();
        cy.visit("/my/");

        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-delete-tab-button]").click();
        cy.get("[data-test=dashboard-confirm-delete-button]").click();

        cy.get("[data-test=dashboard-add-button]").click();
        cy.get("[data-test=dashboard-add-input-name]").type("My Dashboard");
        cy.get("[data-test=dashboard-add-button-submit]").click();

        // widget image
        cy.get("[data-test=dashboard-add-widget-empty-state-button]").click();
        cy.get("[data-test=myimageviewer]").click();
        cy.get("[data-test=dashboard-widget-image-input-url]").type(
            "https://tuleap/images/organization_logo.png",
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
        cy.get("[data-test=dashboard-my-projects]").find("td").contains("MW");

        // widget document
        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-add-widget-button]").click();
        cy.get("[data-test=plugin_docman_mydocman_search]").click();
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();
        cy.get("[data-test=document-search-id]").type("6");

        cy.get("[data-test=document-button-search]").click();
        cy.get("[data-test=document-search-error]").contains("Unable to find the document");
    });
});
