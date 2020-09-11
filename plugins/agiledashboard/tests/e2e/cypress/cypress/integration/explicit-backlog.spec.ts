/*
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

describe(`Planning view Explicit Backlog`, function () {
    before(function () {
        cy.clearCookie("__Host-TULEAP_session_hash");

        cy.projectMemberLogin();
        cy.visitProjectService("explicit-backlog", "Agile Dashboard");
    });

    it(`Project Member can use Planning view`, function () {
        // Browse the top backlog
        cy.get("[data-test=go-to-top-backlog]").click();
        cy.get("[data-test=backlog]").within(() => {
            cy.contains("[data-test=backlog-item]", "Crossbow Everyday");
            cy.contains("[data-test=backlog-item]", "Raw Wrench");
            cy.get("[data-test=backlog-item]").should("not.contain", "Restless Weeknight");
            cy.get("[data-test=backlog-item]").should("not.contain", "Forgotten Trombone");
        });

        cy.contains("[data-test=milestone]", "Summer Swift");

        // load closed releases
        cy.get("[data-test=load-closed-milestones-button]").click();
        cy.contains("[data-test=milestone]", "New Alpha");

        // Browse the sprint planning
        cy.contains("[data-test=milestone]", "Summer Swift").within(() => {
            cy.get("[data-test=expand-collapse-milestone]").click();
            cy.get("[data-test=go-to-submilestone-planning]").click();
        });

        cy.get("[data-test=backlog]").within(() => {
            cy.contains("[data-test=backlog-item]", "Red Balcony");
            cy.contains("[data-test=backlog-item]", "Forgotten Breeze");
        });

        cy.contains("[data-test=milestone]", "Notorious Endless");
        cy.contains("[data-test=milestone]", "Eager Subdivision");

        // load closed sprints
        cy.get("[data-test=load-closed-milestones-button]").click();
        cy.contains("[data-test=milestone]", "Timely Electron");
    });
});
