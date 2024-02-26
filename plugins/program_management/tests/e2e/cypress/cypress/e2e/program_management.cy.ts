/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { ConditionPredicate } from "@tuleap/cypress-utilities-support";

describe("Program management", () => {
    let program_project_name: string, team_project_name: string, other_team_project_name: string;

    beforeEach(function () {
        const now = Date.now();
        program_project_name = "program-" + now;
        team_project_name = "team-" + now;
        other_team_project_name = "z-other-team-" + now;

        cy.intercept("/api/v1/projects/*/program_teams").as("linkTeamToProgram");
    });

    it("SAFe usage", () => {
        cy.projectAdministratorSession();
        createProjects(program_project_name, team_project_name, other_team_project_name);
        configureProgram(program_project_name, team_project_name);
        createAndPlanFeature(program_project_name, team_project_name);
        createIteration();
        checkThatProgramAndTeamsAreCorrect(program_project_name, team_project_name);
        updateProgramIncrementAndIteration(program_project_name);
        checkThatMirrorsAreSynchronized(team_project_name);

        planUserStory(team_project_name, program_project_name);
        linkANewTeamToProgram(other_team_project_name, program_project_name);
    });
});

function createProject(team_project_name: string, is_team: boolean): void {
    cy.visit("/project/new");
    cy.get("[data-test=project-registration-SAFe-templates-tab]").click();

    const team_or_program_selector = is_team
        ? "project-registration-tuleap-template-program_management_team"
        : "project-registration-tuleap-template-program_management_program";

    cy.get(`[data-test=project-registration-card-label][for=${team_or_program_selector}]`).click();
    cy.get("[data-test=project-registration-next-button]").click();

    cy.get("[data-test=new-project-name]").type(team_project_name);
    cy.get("[data-test=project-shortname-slugified-section]").click();
    cy.get("[data-test=new-project-shortname]").type("{selectall}" + team_project_name);
    cy.get("[data-test=approve_tos]").click();
    cy.get("[data-test=project-registration-next-button]").click();
    cy.get("[data-test=start-working]").click({
        timeout: 20000,
    });
}

function createProjects(
    program_project_name: string,
    team_project_name: string,
    other_team_project_name: string,
): void {
    cy.log("Create team project");
    createProject(team_project_name, true);
    cy.log("Create program project");
    createProject(program_project_name, false);
    cy.log("Create other team project");
    createProject(other_team_project_name, true);
}

function configureProgram(program_project_name: string, team_project_name: string): void {
    cy.log("Add team inside project");
    cy.visitProjectService(program_project_name, "Program");
    cy.get("[data-test=program-go-to-administration]").click({ force: true });
    selectLabelInListPickerDropdown(team_project_name);
    cy.get("[data-test=program-management-add-team-button]").click({ force: true });

    cy.wait("@linkTeamToProgram");

    cy.log("Edit configuration");
    cy.get("[data-test=admin-program-increment-label]").type("Foo");
    cy.get("[data-test=admin-program-increment-sub-label]").type("Bar{enter}");

    cy.log("Check configuration is applied");
    cy.visitProjectService(program_project_name, "Program");
    cy.get("[data-test=create-program-increment-button]").contains("Create the first Bar").click();
}

function createAndLinkUserStory(
    program_project_name: string,
    team_project_name: string,
    feature_id: string,
): void {
    cy.log("Create a user story");
    cy.visitProjectService(team_project_name, "Trackers");
    cy.get("[data-test=tracker-link-story]").click();
    cy.get("[data-test=create-new]").click();
    cy.get("[data-test=create-new-item]").first().click();
    cy.get("[data-test=i_want_to]").type("My US");

    cy.get("[data-test=artifact-submit-and-stay]").click();

    cy.get("[data-test=current-artifact-id]").then(($input) => {
        const user_story_id = String($input.val());
        planFeatureIntoProgramIncrement(
            program_project_name,
            team_project_name,
            feature_id,
            user_story_id,
        );
    });
}

function planFeatureIntoProgramIncrement(
    program_project_name: string,
    team_project_name: string,
    feature_id: string,
    user_story_id: string,
): void {
    cy.log("Link User story to feature");
    cy.visit("https://tuleap/plugins/tracker/?&aid=" + feature_id);

    cy.get("[data-test=edit-field-link]").click();
    cy.get("[data-test=artifact-link-submit]").type(user_story_id);
    cy.get("[data-test=artifact-link-type-selector]").select("_is_child");
    cy.get("[data-test=artifact-submit-and-stay]").click();

    cy.log("Add feature to top backlog");
    cy.get("[data-test=tracker-artifact-actions]").click();
    cy.get("[data-test=add-to-top-backlog]").click();

    cy.log("Plan feature inside PI");
    cy.visitProjectService(program_project_name, "Program");
    cy.get("[data-test=program-increment-toggle]").click();
    cy.get("[data-test=program-increment-info-edit-link]").click();
    cy.get("[data-test=edit-field-links]").click();
    cy.get("[data-test=artifact-link-submit]").type(feature_id);
    cy.get("[data-test=artifact-submit]").click();
}

function createAndPlanFeature(program_project_name: string, team_project_name: string): void {
    cy.log("Create a program increment");
    cy.get("[data-test=program_increment_name]").type("My first PI");
    cy.get("[data-test=date-time-start_date]").type("2021-08-03");
    cy.get("[data-test=date-time-end_date]").type("2021-10-03");
    cy.get("[data-test=artifact-submit-button]").click();

    cy.log("Create a feature");
    cy.visitProjectService(program_project_name, "Trackers");
    cy.get("[data-test=tracker-link-feature]").click();
    cy.get("[data-test=create-new]").click();
    cy.get("[data-test=create-new-item]").first().click();
    cy.get("[data-test=title]").type("My awesome feature");

    cy.get("[data-test=artifact-submit-and-stay]").click();

    cy.get("[data-test=current-artifact-id]").then(($input) => {
        const feature_id = String($input.val());
        createAndLinkUserStory(program_project_name, team_project_name, feature_id);
    });
}

function createIteration(): void {
    cy.log("Create an iteration");
    cy.get("[data-test=program-increment-toggle]").click();
    cy.get("[data-test=program-increment-plan-iterations-link]").click();
    cy.log("Check that iteration have an unplanned user story");
    cy.get("[data-test=user-story-card]").contains("My US");
    cy.get("[data-test=planned-iterations-add-iteration-button]").click();
    cy.get("[data-test=iteration_name]").type("Iteration One");
    cy.get("[data-test=date-time-start_date]").type("2021-08-03");
    cy.get("[data-test=date-time-end_date]").type("2021-08-13");
    cy.get("[data-test=artifact-submit-button]").click();
}

function planUserStory(team_project_name: string, program_project_name: string): void {
    cy.log("plan the user story in team");
    cy.visitProjectService(team_project_name, "Backlog");
    cy.get("[data-test=milestone]").click().get("[data-test=go-to-submilestone-planning]").click();
    cy.get("[data-test=backlog-item-details-link]")
        .invoke("data", "artifact-id")
        .then((user_story_id) => {
            cy.get("[data-test=expand-collapse-milestone]")
                .invoke("data", "artifact-id")
                .then((sprint_id) => {
                    cy.visit("https://tuleap/plugins/tracker/?&aid=" + sprint_id);
                    cy.get("[data-test=edit-field-links]").click();
                    cy.get("[data-test=artifact-link-submit]").type(String(user_story_id));
                    cy.get("[data-test=artifact-link-type-selector]").first().select("_is_child");
                    cy.get("[data-test=artifact-submit]").click();
                });
        });

    cy.log("Check user story is now planned in iteration app");

    cy.visitProjectService(program_project_name, "Program");
    cy.get("[data-test=program-increment-toggle]").click();
    cy.get("[data-test=program-increment-plan-iterations-link").click();
    cy.get("[data-test=iteration-card-header]").click();
    cy.get("[data-test=user-story-card]").contains("My US");
}

function checkThatProgramAndTeamsAreCorrect(
    program_project_name: string,
    team_project_name: string,
): void {
    cy.visitProjectService(program_project_name, "Program");
    cy.log("Check sidebar for program");
    cy.get("[data-test=nav-bar-linked-projects]", { includeShadowDom: true }).contains(
        team_project_name,
    );

    cy.log("Check that feature is linked to program increment");
    cy.get("[data-test=program-increment-toggle]").click();
    cy.get("[data-test=program-increment-content]").contains("My awesome feature");

    cy.log("Check sidebar for team");
    cy.visitProjectService(team_project_name, "Backlog");
    cy.get("[data-test=nav-bar-linked-projects]", { includeShadowDom: true }).contains(
        program_project_name,
    );

    checkPIExistsInReleases("My first PI", team_project_name);
    checkMirrorIterationExistsInSprint("My first PI", "Iteration One", team_project_name);

    cy.log("Check that user story linked to feature has been planned in mirror program increment");
    cy.visitProjectService(team_project_name, "Backlog");
    cy.get("[data-test=expand-collapse-milestone]").click();
    cy.get("[data-test=milestone-backlog-items]").contains("My US");
}

function checkPIExistsInReleases(expected_text: string, team_project_name: string): void {
    const reloadCallback = (): void => cy.visitProjectService(team_project_name, "Backlog");
    const conditionCallback: ConditionPredicate = (number_of_attempts, max_attempts) => {
        cy.log(
            `Check that mirror program increment ${expected_text} has been created (attempt ${number_of_attempts}/${max_attempts})`,
        );
        return cy
            .get("[data-test=milestone]")
            .then((milestone) => milestone.text().includes(expected_text));
    };
    cy.reloadUntilCondition(
        reloadCallback,
        conditionCallback,
        "Timed out while checking if mirror program increment has been created",
    );
}

function checkMirrorIterationExistsInSprint(
    parent_pi: string,
    expected_text: string,
    team_project_name: string,
): void {
    cy.get("[data-test=milestone]")
        .contains(parent_pi)
        .click()
        .get("[data-test=go-to-submilestone-planning]")
        .click();

    const reloadCallback = (): void => cy.visitProjectService(team_project_name, "Backlog");
    const conditionCallback: ConditionPredicate = (number_of_attempts, max_attempts) => {
        cy.log(
            `Check that mirror iteration ${expected_text} has been created (attempt ${number_of_attempts}/${max_attempts})`,
        );
        return cy
            .get("[data-test=milestone]")
            .then((home_sprints) => home_sprints.text().includes(expected_text));
    };
    cy.reloadUntilCondition(
        reloadCallback,
        conditionCallback,
        "Timed out while checking if mirror iteration has been created",
    );
}

function updateProgramIncrementAndIteration(program_project_name: string): void {
    cy.log("Edit program increment and iteration");
    cy.visitProjectService(program_project_name, "Program");
    cy.get("[data-test=program-increment-toggle]").click();
    cy.get("[data-test=program-increment-info-edit-link]").click();
    cy.get("[data-test=edit-field-program_increment_name]").click();
    cy.get("[data-test=program_increment_name]").type("{end} updated");
    cy.get("[data-test=artifact-submit]").click();

    cy.get("[data-test=program-increment-toggle]").click();
    cy.get("[data-test=program-increment-plan-iterations-link]").click();
    cy.get("[data-test=iteration-card-header]").click();
    cy.get("[data-test=planned-iteration-info-edit-link]").click();
    cy.get("[data-test=edit-field-iteration_name]").type("{end} updated");
    cy.get("[data-test=artifact-submit]").click();
}

function checkThatMirrorsAreSynchronized(team_project_name: string): void {
    cy.log("Check that mirror program increment is synchronized");
    cy.visitProjectService(team_project_name, "Backlog");

    checkPIExistsInReleases("My first PI updated", team_project_name);
    checkMirrorIterationExistsInSprint(
        "My first PI updated",
        "Iteration One updated",
        team_project_name,
    );
}

function selectLabelInListPickerDropdown(
    label: string,
): Cypress.Chainable<JQuery<HTMLHtmlElement>> {
    cy.get("[data-test=list-picker-selection]").first().click();
    return cy.root().within(() => {
        cy.get("[data-test-list-picker-dropdown-open]").within(() => {
            cy.get("[data-test=list-picker-item]").contains(label).click();
        });
    });
}

function linkANewTeamToProgram(
    other_team_project_name: string,
    program_project_name: string,
): void {
    cy.log("Add new team in program");
    cy.visit(`program_management/admin/${program_project_name}`);

    selectLabelInListPickerDropdown(other_team_project_name);
    cy.get("[data-test=program-management-add-team-button]").click({ force: true });

    cy.get("[data-test=synchronize-team-button]").click();

    cy.log("Check sidebar for team");
    cy.visitProjectService(other_team_project_name, "Backlog");
    cy.get("[data-test=nav-bar-linked-projects]", { includeShadowDom: true }).contains(
        program_project_name,
    );

    checkPIExistsInReleases("My first PI updated", other_team_project_name);
}
