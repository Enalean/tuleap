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

describe("Hide widget", function () {
    before(() => {
        cy.platformAdminLogin();
    });

    it("given widget is not available on platform, then it must never be displayed", function () {
        cy.visit("/admin/project-creation/widgets");

        cy.get("[data-test=project-widgets-checkbox-projectheartbeat]").click({ force: true });

        cy.visit("/projects/project-dashboard");

        cy.get("[data-test=dashboard-widget-projectnote]");
        cy.get("[data-test=dashboard-widget-projectheartbeat]").should("not.exist");

        //enable heartbeat again
        cy.visit("/admin/project-creation/widgets");
        cy.get("[data-test=project-widgets-checkbox-projectheartbeat]").click({ force: true });
    });
});
