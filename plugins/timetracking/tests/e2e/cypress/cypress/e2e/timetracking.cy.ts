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

describe("Time tracking", function () {
    let now: number;

    beforeEach(function () {
        now = Date.now();
    });

    it("Project administrator must be able to configure timetracking", function () {
        cy.projectAdministratorSession();

        cy.visitProjectService("timetracking", "Trackers");
        cy.get("[data-test=tracker-link-issue]").click();
        cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });
        cy.get("[data-test=tracker-admin-more-options]").click();
        cy.get("[data-test=timetracking]").click();

        // enable time tracking
        cy.get("[data-test=timetracking-admin-form]").then(($timetracking) => {
            if ($timetracking.find("[data-test=timetracking-readers]").length === 0) {
                cy.get("[data-test=enable-timetracking]").click();
                cy.get("[data-test=timetracking-save-configuration]").click();
            }
        });

        // give permissions
        cy.get("[data-test=timetracking-writers]").select("Project members");
        cy.get("[data-test=timetracking-save-configuration]").click();
    });

    it("regular user should be able to track his time in artifact", function () {
        cy.projectMemberSession();

        // create an artifact
        cy.visitProjectService("timetracking", "Trackers");
        cy.get("[data-test=tracker-link-issue]").click();

        cy.get("[data-test=new-artifact]").click();
        cy.get("[data-test=details]").type("My artifact");
        cy.get("[data-test=artifact-submit-options]").click();
        cy.get("[data-test=artifact-submit-and-stay]").click();

        // directly on artifact
        cy.get("[data-test=timetracking]").click();
        cy.get("[data-test=timetracking-add-button]").click();

        cy.get("[data-test=timetracking-new-row-step]").type("My time");
        cy.get("[data-test=timetracking-new-row-date]").clear().type("2020-02-06");
        cy.get("[data-test=timetracking-new-row-time]").type("03:00");

        cy.get("[data-test=timetracking-add-time]").click();

        cy.get("[data-test=timetracking-times]").find("tr").should("have.length", 2);

        cy.get("[data-test=timetracking-add-button]").click();

        cy.get("[data-test=timetracking-new-row-step]").type("My time");
        cy.get("[data-test=timetracking-new-row-date]").clear().type("2020-02-07");
        cy.get("[data-test=timetracking-new-row-time]").type("04:00");

        cy.get("[data-test=timetracking-add-time]").click();

        cy.get("[data-test=timetracking-times]").find("tr").should("have.length", 3);

        cy.get("[data-test=total-timetracking-row]").contains("7");

        cy.get("[data-test=timetracking-delete-time]").first().click();
        cy.get("[data-test=timetracking-delete-confirm]").first().click();

        cy.get("[data-test=timetracking-update-time]").click();
        cy.get("[data-test=timetracking-edit-row-time]").clear().type("01:00");
        cy.get("[data-test=timetracking-edit-row-date]").clear().type("2020-03-02");

        cy.get("[data-test=timetracking-edit-time]").click();

        cy.get("[data-test=timetracking-times]").find("tr").should("have.length", 2);

        cy.get("[data-test=total-timetracking-row]").contains("1");
    });

    it("regular user should be able to track his time in his personal widget", function () {
        cy.projectMemberSession();

        cy.visit("/my");
        cy.get("[data-test=dashboard-add-button]").click();
        cy.get("[data-test=dashboard-add-input-name]").type(`tab-${now}`);
        cy.get("[data-test=dashboard-add-button-submit]").click();

        cy.get("[data-test=dashboard-configuration-button]").click();

        cy.get("[data-test=dashboard-add-widget-button]").click({ force: true });
        cy.get("[data-test=timetracking]").click();
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();

        cy.get("[data-test=timetracking-switch-reading-mode]").click();

        //vue flat picker needs to force clear/type
        cy.get("[data-test=timetracking-start-date]")
            .clear({ force: true })
            .type("2020-03-01", { force: true });
        cy.get("[data-test=timetracking-end-date]")
            .clear({ force: true })
            .type("2020-03-10", { force: true });

        //can be invisible due to flat picker who isn't closed by type command
        cy.get("[data-test=timetracking-search-for-dates]").click({ force: true });

        cy.get("[data-test=timetracking-details]").first().click();

        cy.get("[data-test=timetracking-edit-time]").first().click();
        cy.get("[data-test=timetracking-time]").clear().type("04:00");
        cy.get("[data-test=timetracking-submit-time]").click();

        cy.get("[data-test=button-set-add-mode]").first().click();
        cy.get("[data-test=timetracking-time]").first().clear().type("04:00");
        cy.get("[data-test=timetracking-submit-time]").first().click();

        cy.get("[data-test=timetracking-delete-time]").first().click();
        // even if the modal is open, the button might be invisible
        cy.get("[data-test=timetracking-confirm-time-deletion]").first().click({ force: true });
    });

    it("manager should be able to track time of his subordinates", function () {
        cy.projectAdministratorSession();
        cy.visit("/my");

        cy.get("[data-test=dashboard-add-button]").click();
        cy.get("[data-test=dashboard-add-input-name]").type(`tab-${now}`);
        cy.get("[data-test=dashboard-add-button-submit]").click();

        cy.get("[data-test=dashboard-configuration-button]").click();

        cy.get("[data-test=dashboard-add-widget-button]").click({ force: true });
        cy.get("[data-test=timetracking-overview]").click();
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();

        // select some trackers
        cy.intercept(`*trackers?representation=minimal&limit=*&offset=*&query=*`).as(
            "loadTrackers",
        );

        cy.get("[data-test=overview-toggle-reading-mode]").click();
        // select project
        cy.get("[data-test=overview-project-list]").select("timetracking");
        cy.wait("@loadTrackers", { timeout: 3000 });

        //select tracker
        cy.get("[data-test=overview-tracker-selector]").select("Issues");
        cy.get("[data-test=add-tracker-button]").click();

        cy.get("[data-test=overview-search-times]").click();
        //check that at least one time correspond to query
        cy.get("[data-test=overview-table]").find("tr").should("have.length", 3);

        //check that user can save report
        cy.get("[data-test=save-overview-report]").click();
        cy.get("[data-test=report-success]").contains("successfully saved");
    });
});
