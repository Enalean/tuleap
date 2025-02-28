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
    let now: number;

    before(function () {
        now = Date.now();
    });

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

            cy.visitProjectService("explicit-backlog", "Trackers");
            cy.log("Project Member can look for story planned in a dedicated milestone");
            cy.getContains("[data-test=tracker-link]", "User Stories").click();
            cy.get("[data-test=criteria-in-milestone]").select("Summer Swift");
            cy.log("clear report value for replayability");
            cy.get("[data-test=alphanum-report-criteria]").clear();
            cy.get("[data-test=submit-report-search]").click();
            cy.get("[data-test=artifact-report-table]").contains("Red Balcony");
            cy.get("[data-test=artifact-report-table]").contains("Forsaken Autumn");

            cy.log("Project Member can look for unplanned story");
            cy.get("[data-test=criteria-in-milestone]").select("Any");
            cy.get("[data-test=alphanum-report-criteria]").type("Crossbow Everyday");
            cy.get("[data-test=submit-report-search]").click();
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
            cy.getContains("[data-test=tracker-link]", "User Stories").click();
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

    context("Backlog administration", () => {
        it(`Project administrator can edit planning configuration`, function () {
            cy.createNewPublicProject(`backlog-admin-${now}`, "scrum");

            cy.visitProjectService(`backlog-admin-${now}`, "Backlog");
            cy.get("[data-test=link-to-ad-administration]").click({ force: true });

            cy.get("[data-test=planning-configuration]").find("tr").should("have.length", 3);

            cy.log("Delete Sprint planning level");
            cy.get("[data-test=delete-planning-configuration]").last().click();

            cy.get("[data-test=planning-configuration]").find("tr").should("have.length", 2);

            cy.log("Update release configuration");
            cy.get("[data-test=edit-planning-configuration]").click();
            cy.get("[data-test=planning-name-input]").clear().type("My new release name");
            cy.get("[data-test=update-planning-configuration]").click();
            cy.get("[data-test=feedback]").should("contain", "Planning succesfully updated");

            cy.log("Update global settings");
            cy.get("[data-test=link-to-ad-administration]").click({ force: true });
            cy.get("[data-test=should-sidebar-display-last-milestones]").click();
            cy.get("[data-test=backlog-edit-global-settings]").click();
            cy.get("[data-test=feedback]").should(
                "contain",
                "Scrum configuration successfully updated.",
            );

            cy.log("User can update burnup configuration");
            cy.get("[data-test=backlog-chart-administration]").click();
            cy.get("[data-test=burnup-count-mode]").click();
            cy.get("[data-test=backlog-save-chart-configuration]").click();
            cy.get("[data-test=feedback]").should(
                "contain",
                "Chart configuration updated successfully.",
            );
        });
    });
});
