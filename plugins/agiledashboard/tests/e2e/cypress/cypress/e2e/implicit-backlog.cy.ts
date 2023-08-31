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

describe(`Planning view Implicit Backlog`, function () {
    it(`Project Member can browse the top backlog`, function () {
        cy.projectMemberSession();
        cy.visitProjectService("implicit-backlog", "Agile Dashboard");

        cy.get("[data-test=backlog]").within(() => {
            cy.contains("[data-test=backlog-item]", "Crossbow Everyday");
            cy.contains("[data-test=backlog-item]", "Restless Weeknight");
            cy.contains("[data-test=backlog-item]", "Raw Wrench");
            cy.contains("[data-test=backlog-item]", "Forgotten Trombone");
        });

        cy.contains("[data-test=milestone]", "Summer Swift");

        // load closed milestones
        cy.get("[data-test=load-closed-milestones-button]").click();
        cy.contains("[data-test=milestone]", "New Alpha");
    });
});
