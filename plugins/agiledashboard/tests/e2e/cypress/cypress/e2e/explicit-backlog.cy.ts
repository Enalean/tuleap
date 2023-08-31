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
    beforeEach(function () {
        cy.projectMemberSession();
        cy.visitProjectService("explicit-backlog", "Backlog");
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get("body").as("body");
    });
    context("Explicit backlog project", () => {
        it(`Project Member can use Planning view`, function () {
            cy.log("Browse the top backlog");
            cy.get("[data-test=backlog]").within(() => {
                cy.contains("[data-test=backlog-item]", "Crossbow Everyday");
                cy.contains("[data-test=backlog-item]", "Raw Wrench");
                cy.get("[data-test=backlog-item]").should("not.contain", "Restless Weeknight");
                cy.get("[data-test=backlog-item]").should("not.contain", "Forgotten Trombone");
            });

            cy.contains("[data-test=milestone]", "Summer Swift");

            cy.log("load closed releases");
            cy.get("[data-test=load-closed-milestones-button]").click();
            cy.contains("[data-test=milestone]", "New Alpha");

            cy.log("Browse the sprint planning");
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

            cy.log("load closed sprints");
            cy.get("[data-test=load-closed-milestones-button]").click();
            cy.contains("[data-test=milestone]", "Timely Electron");

            cy.log("Viewed milestone must be in recent elements");
            cy.get("@body").type("{s}");
            cy.get("[data-test=switch-to-modal]").should("be.visible");

            cy.get("[data-test=switch-to-filter]").type("Summer");
            cy.get("[data-test=switch-to-recent-items]").should("contain", "Summer Swift");
        });
    });

    context("+New", () => {
        it("redirects to previous A.D pane", function () {
            // Browse the sprint planning
            cy.contains("[data-test=milestone]", "Summer Swift").within(() => {
                cy.get("[data-test=expand-collapse-milestone]").click();
                cy.get("[data-test=go-to-submilestone-planning]").click();
            });

            cy.get("[data-test=tab-details]").click();
            cy.get("[data-test=new-button]").click();
            cy.get("[data-test=create-new-item]").first().click();

            cy.get("[data-test=summary]").type("New Art");
            cy.intercept(`*func=submit-artifact*`).as("createArtifact");

            cy.get("[data-test=artifact-submit-button]").click();
            cy.wait("@createArtifact", { timeout: 60000 });

            cy.location().should((loc) => {
                expect(loc.href).contains("pane=details");
            });

            cy.get("[data-test=release-overview]").contains("New Art");
        });

        it("open the pv2 modal", function () {
            //wait for the page to be loaded
            cy.get("[data-test=backlog-fully-loaded]");

            cy.get("[data-test=new-button]").click();
            cy.get("[data-test=create-new-item]").first().click();

            cy.get("[data-test=artifact-modal-form]").contains("Create a new");
        });
    });

    context("Scrum template usage", () => {
        let project_name: string;
        let now: number;

        before(function () {
            now = Date.now();
        });

        beforeEach(function () {
            project_name = "ad-" + now;
        });
        it(`Scrum template can be used in explicit mode`, function () {
            cy.projectMemberSession();
            cy.log("Create a new project");
            cy.createNewPublicProject(project_name, "scrum");

            cy.log("Items are automatically linked to top backlog");
            cy.visitProjectService(project_name, "Backlog");
            cy.get("[data-test=add-item]").click();
            cy.get("[data-test='add-User Stories']").click();
            cy.get("[data-test=string-field-input]").type("Perfect World");
            cy.get("[data-test=artifact-modal-save-button]").click();

            cy.log("Artifacts are not linked");
            cy.visitProjectService(project_name, "Trackers");
            cy.get("[data-test=tracker-link-story]").click();
            cy.get("[data-test=new-artifact]").click();
            cy.get("[data-test=i_want_to]").type("Unrealistic Hobby");
            cy.get("[data-test=artifact-submit-button]").click();

            cy.visitProjectService(project_name, "Backlog");

            cy.get("[data-test=backlog]").within(() => {
                cy.contains("[data-test=backlog-item]", "Perfect World");
                cy.get("[data-test=backlog-item]").should("not.contain", "Unrealistic Hobby");
            });
        });
    });
});
