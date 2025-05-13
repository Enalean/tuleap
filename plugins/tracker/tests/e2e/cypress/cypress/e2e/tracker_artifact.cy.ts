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

function submitAndStay(): void {
    cy.get("[data-test=artifact-submit-and-stay]").click();
}

function getCurrentTimestampInSeconds(): string {
    return String(Date.now()).slice(0, -4);
}

function editSimpleField(label: string): Cypress.Chainable<JQuery<HTMLElement>> {
    cy.getContains("[data-test-artifact-form-element]", label)
        .find("[data-test-edit-field]")
        .click();
    return cy
        .getContains("[data-test-artifact-form-element]", label)
        .find("[data-test-field-input]");
}

function selectFormElementWithName(form_element_name: string): void {
    cy.get("[data-test=create-form-element-block]")
        .contains(form_element_name)
        .parent()
        .find("[data-test=create-form-element]")
        .click();
}

function updateSpecificPropertyField(field_name: string, field_value: string): void {
    cy.get("[data-test=string-specific-properties]")
        .contains(field_name)
        .parent()
        .find("[data-test=string-specific-properties-input]")
        .clear()
        .type(field_value);
}

function assertFieldDefaultValue(field_name: string, default_value: string): void {
    cy.get("[data-test=administration-field-label]")
        .contains(field_name)
        .parent()
        .find("[data-test=field-default-value]")
        .should("have.value", default_value);
}

function assertListHaveBeenCreatedWithSubElements(
    field_name: string,
    number_of_elements: number,
): void {
    cy.get("[data-test=administration-field-label]")
        .contains(field_name)
        .parent()
        .find("[data-test=field-list-value]")
        .should("have.length", number_of_elements);
}

function checkListFieldType(type: string): void {
    cy.log(`Add static ${type} and check default value is stored`);
    selectFormElementWithName(type);
    cy.get("[data-test=formElement_label]").type(type);
    cy.get("[data-test=list-static-bind-values]").type("Alpha{Enter}Beta{Enter}Gamma{Enter}");
    cy.get("[data-test=formElement-submit]").click();
    assertListHaveBeenCreatedWithSubElements(type, 4);
    cy.get("[data-test=form-element-field-list] option")
        .should("contain", "None")
        .and("contain", "Alpha")
        .and("contain", "Beta")
        .and("contain", "Gamma");

    cy.log(`Add user ${type} and check default value is stored`);
    selectFormElementWithName(type);
    cy.get("[data-test=formElement_label]").type(`${type}_users`);
    cy.get("[data-test=formElement-bind]").find("input[value=users]").click();
    cy.get("[data-test=list-user-bind-values]").select("group_members");
    cy.get("[data-test=formElement-submit]").click();
    assertListHaveBeenCreatedWithSubElements(`${type}_users`, 2);
    cy.get("[data-test=form-element-field-list] option")
        .should("contain", "None")
        .and("contain", "ProjectAdministrator");

    cy.log(`Add user ${type} field and check default value is stored`);
    selectFormElementWithName(type);
    cy.get("[data-test=formElement_label]").type(`${type}_ugroup`);
    cy.get("[data-test=formElement-bind]").find("input[value=ugroups]").click();
    cy.get("[data-test=list-ugroup-bind-values]").select(0);
    cy.get("[data-test=formElement-submit]").click();
    assertListHaveBeenCreatedWithSubElements(`${type}_ugroup`, 2);
    cy.get("[data-test=form-element-field-list] option")
        .should("contain", "None")
        .and("contain", "Project members");
}

function assertCheckboxHaveBeenCreatedWithSubElements(
    field_name: string,
    number_of_elements: number,
): void {
    cy.get("[data-test=administration-field-label]")
        .contains(field_name)
        .parent()
        .find("[data-test=checkbox-field-value]")
        .should("have.length", number_of_elements);
}

function checkCheckboxListFieldType(): void {
    cy.log(`Add static Checkbox and check default value is stored`);
    selectFormElementWithName("Checkbox");
    cy.get("[data-test=formElement_label]").type("Checkbox");
    cy.get("[data-test=list-static-bind-values]").type("Alpha{Enter}Beta{Enter}Gamma{Enter}");
    cy.get("[data-test=formElement-submit]").click();
    assertCheckboxHaveBeenCreatedWithSubElements("Checkbox", 3);
    cy.get("[data-test=administration-field-label]")
        .contains("Checkbox")
        .parent()
        .find("[data-test=checkbox-field-value]")
        .should("contain", "Alpha")
        .and("contain", "Beta")
        .and("contain", "Gamma");

    cy.log(`Add user Checkbox and check default value is stored`);
    selectFormElementWithName("Checkbox");
    cy.get("[data-test=formElement_label]").type("Checkbox_users");
    cy.get("[data-test=formElement-bind]").find("input[value=users]").click();
    cy.get("[data-test=list-user-bind-values]").select("group_members");
    cy.get("[data-test=formElement-submit]").click();
    assertCheckboxHaveBeenCreatedWithSubElements("Checkbox_users", 1);
    cy.get("[data-test=administration-field-label]")
        .contains("Checkbox_users")
        .parent()
        .find("[data-test=checkbox-field-value]")
        .should("contain", "ProjectAdministrator");

    cy.log(`Add user Checkbox field and check default value is stored`);
    selectFormElementWithName("Checkbox");
    cy.get("[data-test=formElement_label]").type("Checkbox_ugroup");
    cy.get("[data-test=formElement-bind]").find("input[value=ugroups]").click();
    cy.get("[data-test=list-ugroup-bind-values]").select(0);
    cy.get("[data-test=formElement-submit]").click();
    assertCheckboxHaveBeenCreatedWithSubElements("Checkbox_ugroup", 1);
    cy.get("[data-test=administration-field-label]")
        .contains("Checkbox_ugroup")
        .parent()
        .find("[data-test=checkbox-field-value]")
        .should("contain", "Project members");
}

function assertRadioListHaveBeenCreatedWithSubElements(
    field_name: string,
    number_of_elements: number,
): void {
    cy.get("[data-test=administration-field-label]")
        .contains(field_name)
        .parent()
        .find("[data-test=radiobutton-field-value]")
        .should("have.length", number_of_elements);
}

function checkRadioListFieldType(): void {
    cy.log(`Add static Radio and check default value is stored`);
    selectFormElementWithName("Radio button");
    cy.get("[data-test=formElement_label]").type("Radio");
    cy.get("[data-test=list-static-bind-values]").type("Alpha{Enter}Beta{Enter}Gamma{Enter}");
    cy.get("[data-test=formElement-submit]").click();
    assertRadioListHaveBeenCreatedWithSubElements("Radio", 4);
    cy.get("[data-test=administration-field-label]")
        .contains("Radio")
        .parent()
        .find("[data-test=radiobutton-field-value]")
        .should("contain", "None")
        .and("contain", "Alpha")
        .and("contain", "Beta")
        .and("contain", "Gamma");

    cy.log(`Add user Radio and check default value is stored`);
    selectFormElementWithName("Radio button");
    cy.get("[data-test=formElement_label]").type("Radio_users");
    cy.get("[data-test=formElement-bind]").find("input[value=users]").click();
    cy.get("[data-test=list-user-bind-values]").select("group_members");
    cy.get("[data-test=formElement-submit]").click();
    assertRadioListHaveBeenCreatedWithSubElements("Radio_users", 2);
    cy.get("[data-test=administration-field-label]")
        .contains("Radio_users")
        .parent()
        .find("[data-test=radiobutton-field-value]")
        .should("contain", "None")
        .and("contain", "ProjectAdministrator");

    cy.log(`Add user Radio field and check default value is stored`);
    selectFormElementWithName("Radio button");
    cy.get("[data-test=formElement_label]").type("Radio_ugroup");
    cy.get("[data-test=formElement-bind]").find("input[value=ugroups]").click();
    cy.get("[data-test=list-ugroup-bind-values]").select(0);
    cy.get("[data-test=formElement-submit]").click();
    assertRadioListHaveBeenCreatedWithSubElements("Radio_ugroup", 2);
    cy.get("[data-test=administration-field-label]")
        .contains("Radio_ugroup")
        .parent()
        .find("[data-test=radiobutton-field-value]")
        .should("contain", "None")
        .and("contain", "Project members");
}

function assertOpenListHaveBeenCreatedWithSubElements(
    field_name: string,
    elements: ReadonlyMap<string, string>,
): void {
    elements.forEach((value: string, key: string): void => {
        cy.get("[data-test=administration-field-label]")
            .contains(field_name)
            .parent()
            .find("[data-test=open-list-field] input")
            .first()
            .type("{selectall}{del}")
            .type(key);
        cy.get("[data-test=administration-field-label]")
            .contains(field_name)
            .parent()
            .find("[data-test=open-list-field-dropdown]")
            .contains(value);
    });
}

function checkOpenListFieldType(): void {
    cy.log("Add static open list field and check default value is stored");
    selectFormElementWithName("Open List");
    cy.get("[data-test=formElement_label]").type("Open");
    cy.get("[data-test=list-static-bind-values]").type("Theta{Enter}Iota{Enter}");
    cy.get("[data-test=formElement-submit]").click();
    assertOpenListHaveBeenCreatedWithSubElements(
        "Open",
        new Map([
            ["Th", "Theta"],
            ["Io", "Iota"],
        ]),
    );

    cy.log("Add user open list field and check default value is stored");
    selectFormElementWithName("Open List");
    cy.get("[data-test=formElement_label]").type("Open_user");
    cy.get("[data-test=formElement-bind]").find("input[value=users]").click();
    cy.get("[data-test=list-user-bind-values]").select("group_members");
    cy.get("[data-test=formElement-submit]").click();
    assertOpenListHaveBeenCreatedWithSubElements(
        "Open_user",
        new Map([["Pr", "ProjectAdministrator"]]),
    );

    cy.log(`Add user Radio field and check default value is stored`);
    selectFormElementWithName("Open List");
    cy.get("[data-test=formElement_label]").type("Open_ugroup");
    cy.get("[data-test=formElement-bind]").find("input[value=ugroups]").click();
    cy.get("[data-test=list-ugroup-bind-values]").select(0);
    cy.get("[data-test=formElement-submit]").click();
    assertOpenListHaveBeenCreatedWithSubElements(
        "Open_ugroup",
        new Map([["Pr", "Project members"]]),
    );
}

describe("Tracker artifacts", function () {
    const TITLE_FIELD_NAME = "title";

    describe("Site admin specific settings for move/deletion", function () {
        it("must be able to set the artifact deletion setting", function () {
            cy.siteAdministratorSession();
            cy.visit("/");

            cy.get("[data-test=platform-administration-link]").click();
            cy.get("[data-test=admin-tracker]").click();
            cy.get("[data-test=artifact-deletion]").click();
            cy.get("[data-test=input-artifacts-limit]").clear().type("50");
            cy.get("[data-test=artifact-deletion-button]").click();
            cy.get("[data-test=feedback]").contains("Limit successfully updated.");
        });
    });

    context("", function () {
        // Create once the project for tests in this context
        let project_name: string;
        before(function () {
            project_name = "tracker-" + getCurrentTimestampInSeconds();

            cy.projectAdministratorSession();
            cy.log("Create a new project");
            cy.createNewPublicProject(project_name, "issues").as("project_id");
        });

        describe("Tracker administration", function () {
            it("can access to admin section", function () {
                cy.projectAdministratorSession();
                cy.visit("/plugins/tracker/global-admin/" + this.project_id);
                cy.get("[data-test=tracker-global-admin-title]").should(
                    "contain.text",
                    "Tracker global administration",
                );
            });

            it("must be able to create tracker from Tuleap template Bug", function () {
                cy.projectAdministratorSession();
                cy.visitProjectService(project_name, "Trackers");
                cy.get("[data-test=new-tracker-creation]").click();
                cy.get("[data-test=selected-option-default-bug]").click({ force: true });

                cy.get("[data-test=button-next]").click();
                cy.get("[data-test=tracker-name-input]").type(
                    getCurrentTimestampInSeconds() + " from cypress",
                );
                cy.get("[data-test=button-create-my-tracker]").click();
                cy.get("[data-test=tracker-creation-modal-success]").contains("Congratulations");
            });

            it("must be able to create tracker from empty and configure it", function () {
                const current_time = getCurrentTimestampInSeconds();
                const tracker_item_name = current_time + "_from_empty";

                cy.projectAdministratorSession();
                cy.visit(`/plugins/tracker/${encodeURIComponent(project_name)}/new`);
                cy.get("[data-test=selected-option-tracker_empty]").click({ force: true });

                cy.get("[data-test=button-next]").click();
                cy.get("[data-test=tracker-name-input]").type(current_time + " From empty");
                cy.get("[data-test=button-create-my-tracker]").click();
                cy.get("[data-test=tracker-creation-modal-success]").contains("Congratulations");
                cy.getTrackerIdFromREST(this.project_id, tracker_item_name).as("tracker_id");

                cy.log("Configure tracker fields");
                cy.get("[data-test=continue-tracker-configuration]").click();

                cy.log("Add computed field and check default value is stored");
                selectFormElementWithName("Computed value");
                cy.get("[data-test=formElement_label]").type("Computed");
                updateSpecificPropertyField("Default value", "22");
                cy.get("[data-test=formElement-submit]").click();
                assertFieldDefaultValue("Computed", "22");

                cy.log("Add float field and check default value is stored");
                selectFormElementWithName("Float");
                cy.get("[data-test=formElement_label]").type("Float");
                updateSpecificPropertyField("Max", "123");
                updateSpecificPropertyField("Size", "456");
                updateSpecificPropertyField("Default value", "789");
                cy.get("[data-test=formElement-submit]").click();
                assertFieldDefaultValue("Float", "789");

                cy.log("Add int field and check default value is stored");
                selectFormElementWithName("Integer");
                cy.get("[data-test=formElement_label]").type("Int");
                updateSpecificPropertyField("Max", "12");
                updateSpecificPropertyField("Size", "34");
                updateSpecificPropertyField("Default value", "56");
                cy.get("[data-test=formElement-submit]").click();
                assertFieldDefaultValue("Int", "56");

                checkListFieldType("Selectbox");
                checkListFieldType("Multi Select Box");
                checkCheckboxListFieldType();
                checkRadioListFieldType();
                checkOpenListFieldType();

                cy.log("Add rich text field and check default value is stored");
                selectFormElementWithName("Static Text");
                cy.get("[data-test=formElement_label]").type("Static");
                cy.window().then((win): void => {
                    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                    // @ts-ignore
                    win.CKEDITOR.instances.formElement_properties_static_value.setData(
                        "My custom text",
                    );
                });
                cy.get("[data-test=formElement-submit]").click();
                cy.get("[data-test=rich-text-value]").should("contain.text", "My custom text");

                cy.log("Add string field and check default value is stored");
                selectFormElementWithName("String");
                cy.get("[data-test=formElement_label]").type("String");
                updateSpecificPropertyField("Default value", "Default string");
                cy.get("[data-test=formElement-submit]").click();
                assertFieldDefaultValue("String", "Default string");

                cy.log("Add text field and check default value is stored");
                selectFormElementWithName("Text");
                cy.get("[data-test=formElement_label]").type("Text");
                updateSpecificPropertyField("Row", "20");
                updateSpecificPropertyField("Columns", "30");
                cy.get("[data-test=text-field-specific-properties]").type("Text default value");
                cy.get("[data-test=formElement-submit]").click();
                cy.get("[data-test=text-field-admin-value]").should(
                    "contain.text",
                    "Text default value",
                );

                cy.log("Add date field and check default value is stored");
                selectFormElementWithName("Date");
                cy.get("[data-test=formElement_label]").type("Date");
                cy.get("[data-test=input-type-radio]").last().check();
                cy.get("[data-test=date-picker]").type("2021-01-01");
                cy.get("[data-test=formElement-submit]").click();
                cy.get("[data-test=date-time-date]").invoke("val").should("equal", "2021-01-01");

                cy.log("Add artifact link field and check the specific properties");
                selectFormElementWithName("Artifact Link");
                cy.get("[data-test=formElement_label]").type("Artifact Link");
                cy.get("[data-test=input-type-checkbox]").should("be.checked");
                cy.get("[data-test=formElement-submit]").click();

                cy.log("Check that the new artifact link field is displayed");
                cy.get("@tracker_id").then((tracker_id) => {
                    cy.visit(`/plugins/tracker/?tracker=${tracker_id}&func=new-artifact`);
                    cy.get("[data-test=artifact-link-submit]").should("not.exist");
                    cy.get("[data-test=link-field-add-link-input]").should("exist");

                    cy.log("Change the specific properties of the artifact link field");
                    cy.visit(`/plugins/tracker/?tracker=${tracker_id}&func=admin`);
                    cy.get("[data-test=edit-field]").first().click({ force: true });
                    cy.get("[data-test=input-type-checkbox]").uncheck();
                    cy.get("[data-test=formElement-submit]").click();

                    cy.log("Check that the old artifact link field is displayed");
                    cy.visit(`/plugins/tracker/?tracker=${tracker_id}&func=new-artifact`);
                    cy.get("[data-test=artifact-link-submit]").should("exist");
                    cy.get("[data-test=link-field-add-link-input]").should("not.exist");
                });
            });

            it("must be able to create tracker from an other project", function () {
                cy.projectAdministratorSession();
                cy.visit(`/plugins/tracker/${encodeURIComponent(project_name)}/new`);
                cy.get("[data-test=selected-option-tracker_another_project]").click({
                    force: true,
                });

                cy.get("[data-test=project-select]").select("Empty Followup");
                cy.get("[data-test=project-tracker-select]").select("Bugs");

                cy.get("[data-test=button-next]").click();
                cy.get("[data-test=tracker-name-input]").type(
                    getCurrentTimestampInSeconds() + " From an other project",
                );
                cy.get("[data-test=button-create-my-tracker]").click();
                cy.get("[data-test=tracker-creation-modal-success]").contains("Congratulations");
            });

            it("can add a report on the project home page", function () {
                cy.projectAdministratorSession();
                cy.visitProjectService("tracker-artifact", "Trackers");
                cy.getContains("[data-test=tracker-link]", "artifact-link").click();
                cy.get("[data-test=add-to-project-dashboard]").click();

                cy.get("[data-test=artifact-report-table]").contains("test A");
                cy.get("[data-test=artifact-report-table]").contains("test B");
            });
        });

        describe("Tracker regular users", function () {
            beforeEach(function () {
                // eslint-disable-next-line cypress/require-data-selectors
                cy.get("body").as("body");
            });

            describe("Artifact manipulation", function () {
                it("can create and edit an artifact", function () {
                    cy.projectMemberSession();
                    cy.visitProjectService(project_name, "Trackers");
                    cy.getContains("[data-test=tracker-link]", "Issues").click();
                    cy.get("[data-test=create-new]").click();
                    cy.get("[data-test=create-new-item]").first().click();
                    cy.getContains("[data-test=artifact-form-element]", "Title")
                        .find("[data-test-field-input]")
                        .type("My new bug");
                    submitAndStay();

                    cy.get("[data-test=feedback]").contains("Artifact Successfully Created");
                    cy.getContains("[data-test-artifact-form-element]", "Title").contains(
                        "My new bug",
                    );

                    cy.log("Created artifact must be in recent elements");
                    cy.get("@body").type("s");
                    cy.get("[data-test=switch-to-modal]").should("be.visible");

                    cy.get("[data-test=switch-to-filter]").type("My new bug");
                    cy.get("[data-test=switch-to-recent-items]").should("contain", "My new bug");
                    cy.get("@body").type("{esc}");

                    cy.log("Edit the artifact and add a comment");
                    editSimpleField("Title").clear().type("My edited bug");
                    cy.get("[data-test=artifact_followup_comment]").type("Changed the title");
                    submitAndStay();

                    cy.log("Edit a comment");
                    cy.intercept("POST", "/plugins/tracker/?aid=*").as("editComment");
                    cy.getContains("[data-test=artifact-follow-up]", "Changed the title").then(
                        (comment_panel) => {
                            cy.wrap(comment_panel).find("[data-test=edit-comment]").click();
                            cy.wrap(comment_panel)
                                .find("[data-test=edit-comment-textarea]")
                                .clear()
                                .type("Edited the comment");
                            cy.wrap(comment_panel).find("[data-test=edit-comment-submit]").click();
                        },
                    );
                    cy.wait("@editComment");
                    cy.get("[data-test=follow-up-comment]")
                        .should("contain.text", "Edited the comment")
                        .and("contain.text", "last edited by");
                });

                it("must be able to copy new artifact", function () {
                    cy.projectMemberSession();
                    cy.getTrackerIdFromREST(this.project_id, "issue").then((tracker_id) => {
                        cy.createArtifact({
                            tracker_id,
                            artifact_title: "copy artifact",
                            artifact_status: "New",
                            title_field_name: TITLE_FIELD_NAME,
                        }).then((artifact_id) => {
                            cy.visit("https://tuleap/plugins/tracker/?&aid=" + artifact_id);
                        });
                    });

                    cy.get("[data-test=tracker-artifact-actions]").click();
                    cy.get("[data-test=artifact-copy-button]").click();
                    editSimpleField("Title").clear().type("My updated summary");

                    cy.get("[data-test=artifact-copy]").click();

                    cy.get("[data-test=artifact-followups]").contains("Copy of issue");
                    cy.getContains("[data-test-artifact-form-element]", "Title").contains(
                        "My updated summary",
                    );
                });

                it("can be displayed in printer version", function () {
                    cy.projectMemberSession();
                    cy.getTrackerIdFromREST(this.project_id, "issue").then((tracker_id) => {
                        cy.createArtifact({
                            tracker_id,
                            artifact_title: "printer version",
                            artifact_status: "New",
                            title_field_name: TITLE_FIELD_NAME,
                        }).then((artifact_id) => {
                            cy.visit(`https://tuleap/plugins/tracker/?&aid=${artifact_id}&pv=1`);
                        });
                    });
                    // check that followup block is displayed
                    cy.get("[data-test=artifact-followups]").should("exist");
                });

                it("can switch from autocomputed mode to calculated mode and so on", function () {
                    cy.projectMemberSession();
                    cy.getProjectId("tracker-artifact")
                        .then((project_id) => cy.getTrackerIdFromREST(project_id, "bug"))
                        .then((tracker_id) => {
                            return cy.createArtifact({
                                tracker_id,
                                artifact_title: "autocompute",
                                artifact_status: "New",
                                title_field_name: "summary",
                            });
                        })
                        .then((artifact_id) => {
                            cy.visit("https://tuleap/plugins/tracker/?&aid=" + artifact_id);
                        });

                    editSimpleField("remaining_effort").clear().type("20");

                    // submit and check
                    submitAndStay();
                    cy.get("[data-test=computed-value]").contains(20);

                    //edit field and go back in autocomputed mode
                    cy.getContains("[data-test-artifact-form-element]", "remaining_effort").then(
                        (form_element) => {
                            cy.wrap(form_element).find("[data-test-edit-field]").click();
                            cy.wrap(form_element).find("[data-test=switch-to-autocompute]").click();
                        },
                    );

                    //submit and check
                    submitAndStay();
                    cy.get("[data-test=computed-value]").contains("Empty");
                });
            });

            it("can add a report on his dashboard", function () {
                cy.projectMemberSession();
                cy.visitProjectService("tracker-artifact", "Trackers");
                cy.getContains("[data-test=tracker-link]", "artifact-link").click();
                cy.get("[data-test=add-to-my-dashboard]").first().click({ force: true });

                cy.get("[data-test=artifact-report-table]").contains("test A");
                cy.get("[data-test=artifact-report-table]").contains("test B");
            });
        });

        describe("Tracker dedicated permissions", function () {
            it("should raise an error when user try to access to plugin Tracker admin page", function () {
                cy.projectMemberSession();
                cy.request({
                    url: "/plugins/tracker/global-admin/" + this.project_id,
                    failOnStatusCode: false,
                }).then((response) => {
                    expect(response.status).to.eq(403);
                });
            });

            it("tracker admin must be able to delegate tracker administration privilege", function () {
                cy.projectAdministratorSession();
                cy.visitProjectService(project_name, "Trackers");
                cy.getContains("[data-test=tracker-link]", "Issues").click();

                // eslint-disable-next-line cypress/no-force -- Link is in a dropdown
                cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });

                cy.get("[data-test=admin-permissions]").click();
                cy.get("[data-test=tracker-permissions]").click();

                cy.get("[data-test=permissions_3]").select("are admin of the tracker");

                cy.get("[data-test=tracker-permission-submit]").click();

                cy.get("[data-test=feedback]").contains("Permissions Updated");
            });

            it("regular user must be able to move artifact", function () {
                cy.projectAdministratorSession();
                cy.getProjectId("tracker-artifact")
                    .then((project_id) => cy.getTrackerIdFromREST(project_id, "bug"))
                    .then((tracker_id) => {
                        return cy.createArtifact({
                            tracker_id,
                            artifact_title: "move artifact",
                            artifact_status: "New",
                            title_field_name: "summary",
                        });
                    })
                    .then((artifact_id) => {
                        cy.visit("https://tuleap/plugins/tracker/?&aid=" + artifact_id);
                    });

                cy.get("[data-test=tracker-artifact-actions]").click();
                cy.get("[data-test=tracker-action-button-move]").click();

                cy.get("[data-test=move-artifact-project-selector]").select("tracker artifact");
                cy.get("[data-test=move-artifact-tracker-selector]").select("Bugs for Move");

                cy.get("[data-test=move-artifact]").click();

                cy.get("[data-test=feedback]").contains("has been successfully");
                cy.getContains("[data-test-artifact-form-element]", "Summary").contains(
                    "move artifact",
                );
                cy.getContains("[data-test-artifact-form-element]", "Status").contains("New");
            });

            it("user with tracker admin permissions are tracker admin", function () {
                cy.projectMemberSession();
                cy.getProjectId("tracker-artifact")
                    .then((project_id) => cy.getTrackerIdFromREST(project_id, "bug"))
                    .then((tracker_id) =>
                        cy.request({
                            url: `/plugins/tracker?tracker=${tracker_id}&func=admin`,
                        }),
                    )
                    .then((response) => {
                        expect(response.status).to.eq(200);
                    });
            });
        });

        describe("Tracker artifact permissions", function () {
            it("field permission on artifact should restrict access", function () {
                cy.log("Add artifact permissions field");
                cy.projectAdministratorSession();
                cy.visitProjectService(project_name, "Trackers");
                cy.getContains("[data-test=tracker-link]", "Issues").click();
                // eslint-disable-next-line cypress/no-force -- Link is in a dropdown
                cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });
                selectFormElementWithName("Permissions");
                cy.get("[data-test=formElement_label]").type("Artifact permissions");
                cy.get("[data-test='formElement-submit']").click();

                cy.log("Create artifact with permissions");
                cy.projectMemberSession();
                cy.visitProjectService(project_name, "Trackers");
                cy.getContains("[data-test=tracker-link]", "Issues").click();
                cy.get("[data-test=create-new]").click();
                cy.get("[data-test=create-new-item]").first().click();
                cy.get("[data-test=artifact-permission-enable-checkbox]").click();
                cy.get("[data-test=artifact-permissions-selectbox]").select([
                    "Project administrators",
                ]);
                submitAndStay();
                cy.get("[data-test=feedback]").contains(
                    "You don't have the permissions to view this artifact.",
                );
            });
        });

        describe("Concurrent artifact edition", function () {
            it("A popup is shown to warn the user that someone has edited the artifact while he was editing it.", function () {
                cy.projectMemberSession();
                cy.getTrackerIdFromREST(this.project_id, "issue").then((tracker_id) => {
                    cy.visit(`/plugins/tracker/?tracker=${tracker_id}&func=new-artifact`);
                });
                cy.getContains("[data-test=artifact-form-element]", "Title")
                    .find("[data-test-field-input]")
                    .type("Concurrent edition test");
                submitAndStay();

                cy.get("[data-test=current-artifact-id]")
                    .should("have.attr", "data-artifact-id")
                    .then((artifact_id) => {
                        // Add a follow-up comment to the artifact via the REST API
                        cy.putFromTuleapApi(`https://tuleap/api/artifacts/${artifact_id}`, {
                            values: [],
                            comment: {
                                body: "I have commented this artifact while you were editing it. You mad bro?",
                                post_processed_body: "string",
                                format: "string",
                            },
                        });
                    });

                cy.get("[data-test=artifact_followup_comment]").type(
                    "This my freshly created artifact. Hope nobody has edited it in the meantime!",
                );

                submitAndStay();

                // Check popup is shown and submit buttons disabled
                cy.get("[data-test=concurrent-edition-popup-shown]");
                cy.get("[data-test=artifact-submit]").should("be.disabled");
                cy.get("[data-test=artifact-submit-and-stay]").should("be.disabled");

                // Acknowledge changes
                cy.get("[data-test=acknowledge-concurrent-edition-button]").click();

                // Check popup is hidden and submit buttons enabled
                cy.get("[data-test=concurrent-edition-popup-shown]").should("not.exist");
                cy.get("[data-test=artifact-submit]").should("not.be.disabled");
                cy.get("[data-test=artifact-submit-and-stay]").should("not.be.disabled");

                submitAndStay();

                cy.get("[data-test=artifact-follow-up]").should("have.length", 2);
            });
        });
    });
});
