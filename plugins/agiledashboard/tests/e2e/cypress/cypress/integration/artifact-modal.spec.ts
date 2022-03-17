/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

describe(`Artifact Modal`, function () {
    const now: number = Date.now();
    before(function () {
        cy.clearSessionCookie();
        cy.projectMemberLogin();
        getArtifactLinkIdFromREST().as("artifact_link_id");

        cy.visitProjectService("kanban-artifact-modal", "Agile Dashboard");
        cy.get("[data-test=go-to-kanban]").first().click();
    });

    beforeEach(function () {
        cy.preserveSessionCookies();
    });

    it(`can create an artifact with all fields`, function () {
        cy.get("[data-test=kanban-add-artifact]").click();

        cy.get("[data-test=artifact-modal-form]").within(() => {
            getFieldWithLabel("Title", "[data-test=string-field]").within(() => {
                cy.get("[data-test=string-field-input]").type(`Artifact creation ${now}`);
            });

            getFieldsetWithLabel("Other fields").within(() => {
                getFieldWithLabel("String", "[data-test=string-field]").within(() => {
                    cy.get("[data-test=string-field-input]").type("String value");
                });

                getFieldWithLabel("Text", "[data-test=text-field]").within(() => {
                    cy.get("[data-test=textarea]").type("Text value");
                    cy.get("[data-test=format]").select("HTML");
                });

                getFieldWithLabel("Integer", "[data-test=int-field]").within(() => {
                    cy.get("[data-test=int-field-input]").type("12");
                });

                getFieldWithLabel("Float", "[data-test=float-field]").within(() => {
                    cy.get("[data-test=float-field-input]").type("12.3");
                });

                getFieldWithLabel("Date", "[data-test=date-field]").within(() => {
                    // flatpickr lib sets "readonly" attribute on the input. Testing date picker specifically should be a dedicated test, therefore we use "force".
                    cy.get("[data-test=date-field-input]")
                        .clear({ force: true })
                        .type("2021-02-05", { force: true });
                });

                getFieldWithLabel("Datetime", "[data-test=date-field]").within(() => {
                    // flatpickr lib sets "readonly" attribute on the input. Testing date picker specifically should be a dedicated test, therefore we use "force".
                    cy.get("[data-test=date-field-input]")
                        .clear({ force: true })
                        .type("2021-02-04 16:54", { force: true });
                });

                getFieldWithLabel("Computed", "[data-test=computed-field]").within(() => {
                    cy.get("[data-test=switch-to-manual]").click();
                    cy.get("[data-test=computed-field-input]").type("8");
                });

                getFieldWithLabel("Attachments", "[data-test=file-field]").within(() => {
                    cy.get("[data-test=file-field-file-input]").selectFile(
                        "cypress/fixtures/attachment.json"
                    );
                    cy.get("[data-test=file-field-description-input]").type("My JSON attachment");
                });

                getFieldWithLabel("Permissions", "[data-test=permission-field]").within(() => {
                    cy.get("[data-test=permission-field-checkbox]").check();
                    cy.get("[data-test=permission-field-select]").select([
                        "Project members",
                        "Integrators",
                    ]);
                });
            });

            getFieldsetWithLabel("List fields").within(() => {
                getFieldWithLabel("Selectbox static", "[data-test=selectbox-field]").within(() => {
                    selectLabelInListPickerDropdown("Dos");
                });

                getFieldWithLabel(
                    "Selectbox users (members)",
                    "[data-test=selectbox-field]"
                ).within(() => {
                    selectLabelInListPickerDropdown("ProjectMember (ProjectMember)");
                });

                getFieldWithLabel("Selectbox ugroups", "[data-test=selectbox-field]").within(() => {
                    selectLabelInListPickerDropdown("Integrators");
                });

                getFieldWithLabel("Radio static", "[data-test=radiobutton-field]").within(() => {
                    checkRadioButtonWithLabel("四");
                });

                getFieldWithLabel("Radio users (members)", "[data-test=radiobutton-field]").within(
                    () => {
                        checkRadioButtonWithLabel("ProjectMember (ProjectMember)");
                    }
                );

                getFieldWithLabel("Radio ugroups", "[data-test=radiobutton-field]").within(() => {
                    checkRadioButtonWithLabel("Integrators");
                });

                getFieldWithLabel("MSB static", "[data-test=multi-selectbox-field]").within(() => {
                    selectLabelInListPickerDropdown("Deux");
                    selectLabelInListPickerDropdown("Trois");
                });

                getFieldWithLabel(
                    "MSB users (members)",
                    "[data-test=multi-selectbox-field]"
                ).within(() => {
                    selectLabelInListPickerDropdown("ProjectMember (ProjectMember)");
                    selectLabelInListPickerDropdown("ProjectAdministrator (ProjectAdministrator)");
                });

                getFieldWithLabel("MSB ugroups", "[data-test=multi-selectbox-field]").within(() => {
                    selectLabelInListPickerDropdown("Integrators");
                });

                getFieldWithLabel("Checkbox static", "[data-test=checkbox-field]").within(() => {
                    checkBoxWithLabel("One");
                    checkBoxWithLabel("Three");
                });

                getFieldWithLabel("Checkbox users (members)", "[data-test=checkbox-field]").within(
                    () => {
                        checkBoxWithLabel("ProjectAdministrator (ProjectAdministrator)");
                        checkBoxWithLabel("ProjectMember (ProjectMember)");
                    }
                );

                getFieldWithLabel("Checkbox ugroups", "[data-test=checkbox-field]").within(() => {
                    checkBoxWithLabel("Project administrators");
                    checkBoxWithLabel("Contributors");
                });

                getFieldWithLabel("Openlist static", "[data-test=openlist-field]").within(() => {
                    selectLabelInSelect2Dropdown("Bravo");
                    selectLabelInSelect2Dropdown("Delta");
                });

                getFieldWithLabel("Openlist users (members)", "[data-test=openlist-field]").within(
                    () => {
                        // The list is never populated, it is fetched dynamically by autocomplete
                        // eslint-disable-next-line cypress/require-data-selectors
                        cy.get("input[type=search]").type("proj");
                        selectLabelInSelect2Dropdown("ProjectAdministrator (ProjectAdministrator)");
                        // eslint-disable-next-line cypress/require-data-selectors
                        cy.get("input[type=search]").type("proj");
                        selectLabelInSelect2Dropdown("ProjectMember (ProjectMember)");
                    }
                );

                getFieldWithLabel("Openlist ugroups", "[data-test=openlist-field]").within(() => {
                    selectLabelInSelect2Dropdown("Project administrators");
                    selectLabelInSelect2Dropdown("Contributors");
                });
            });

            getFieldWithLabel("Artifact link", "[data-test=artifactlink-field]").within(() => {
                cy.get("[data-test=artifactlink-field-input]").type(this.artifact_link_id);
            });

            cy.get("[data-test=artifact-modal-save-button]").click();
        });
        waitForKanbanCard(`Artifact creation ${now}`);
    });

    it(`can edit an artifact with all fields`, function () {
        getKanbanCard("Editable Artifact").within(() => {
            cy.get("[data-test=edit-link]").click();
        });

        cy.get("[data-test=artifact-modal-form]").within(() => {
            getFieldWithLabel("Title", "[data-test=string-field]").within(() => {
                cy.get("[data-test=string-field-input]")
                    .clear()
                    .type("Editable Artifact " + now);
            });

            getFieldsetWithLabel("Other fields").within(() => {
                getFieldWithLabel("String", "[data-test=string-field]").within(() => {
                    cy.get("[data-test=string-field-input]").clear().type("Edit String value");
                });

                getFieldWithLabel("Text", "[data-test=text-field]").within(() => {
                    cy.get("[data-test=format]").select("Markdown");
                    cy.get("[data-test=textarea]").clear().type("Edit Text value");
                });

                getFieldWithLabel("Integer", "[data-test=int-field]").within(() => {
                    cy.get("[data-test=int-field-input]").clear().type("87");
                });

                getFieldWithLabel("Float", "[data-test=float-field]").within(() => {
                    cy.get("[data-test=float-field-input]").clear().type("87.9");
                });

                getFieldWithLabel("Date", "[data-test=date-field]").within(() => {
                    // flatpickr lib sets "readonly" attribute on the input. Testing date picker specifically should be a dedicated test, therefore we use "force".
                    cy.get("[data-test=date-field-input]")
                        .clear({ force: true })
                        // Escape to close the flatpickr popover
                        .type("2021-05-27 {esc}", { force: true });
                });

                getFieldWithLabel("Datetime", "[data-test=date-field]").within(() => {
                    // flatpickr lib sets "readonly" attribute on the input. Testing date picker specifically should be a dedicated test, therefore we use "force".
                    cy.get("[data-test=date-field-input]")
                        .clear({ force: true })
                        // Escape to close the flatpickr popover
                        .type("2021-05-27 10:58 {esc}", { force: true });
                });

                getFieldWithLabel("Computed", "[data-test=computed-field]").within(() => {
                    cy.get("[data-test=computed-field-input]").clear().type("13");
                });

                getFieldWithLabel("Attachments", "[data-test=file-field]").within(() => {
                    cy.get("[data-test=file-field-file-input]").selectFile(
                        "cypress/fixtures/svg_attachment.svg"
                    );
                    cy.get("[data-test=file-field-description-input]").type("My SVG attachment");
                });

                getFieldWithLabel("Permissions", "[data-test=permission-field]").within(() => {
                    cy.get("[data-test=permission-field-select]").select([
                        "Project members",
                        "Contributors",
                    ]);
                });
            });

            getFieldsetWithLabel("List fields").within(() => {
                getFieldWithLabel("Selectbox static", "[data-test=selectbox-field]").within(() => {
                    selectLabelInListPickerDropdown("Tres");
                });

                getFieldWithLabel(
                    "Selectbox users (members)",
                    "[data-test=selectbox-field]"
                ).within(() => {
                    selectLabelInListPickerDropdown("ProjectAdministrator (ProjectAdministrator)");
                });

                getFieldWithLabel("Selectbox ugroups", "[data-test=selectbox-field]").within(() => {
                    selectLabelInListPickerDropdown("Contributors");
                });

                getFieldWithLabel("Radio static", "[data-test=radiobutton-field]").within(() => {
                    checkRadioButtonWithLabel("二");
                });

                getFieldWithLabel("Radio users (members)", "[data-test=radiobutton-field]").within(
                    () => {
                        checkRadioButtonWithLabel("ProjectAdministrator (ProjectAdministrator)");
                    }
                );

                getFieldWithLabel("Radio ugroups", "[data-test=radiobutton-field]").within(() => {
                    checkRadioButtonWithLabel("Integrators");
                });

                getFieldWithLabel("MSB static", "[data-test=multi-selectbox-field]").within(() => {
                    selectLabelInListPickerDropdown("Un");
                    selectLabelInListPickerDropdown("Quatre");
                });

                getFieldWithLabel(
                    "MSB users (members)",
                    "[data-test=multi-selectbox-field]"
                ).within(() => {
                    selectLabelInListPickerDropdown("ProjectMember (ProjectMember)");
                });

                getFieldWithLabel("MSB ugroups", "[data-test=multi-selectbox-field]").within(() => {
                    selectLabelInListPickerDropdown("Project administrators");
                    selectLabelInListPickerDropdown("Contributors");
                });

                getFieldWithLabel("Checkbox static", "[data-test=checkbox-field]").within(() => {
                    uncheckBoxWithLabel("One");
                    checkBoxWithLabel("Two");
                    uncheckBoxWithLabel("Three");
                    checkBoxWithLabel("Four");
                });

                getFieldWithLabel("Checkbox users (members)", "[data-test=checkbox-field]").within(
                    () => {
                        checkBoxWithLabel("ProjectMember (ProjectMember)");
                        uncheckBoxWithLabel("ProjectAdministrator (ProjectAdministrator)");
                    }
                );

                getFieldWithLabel("Checkbox ugroups", "[data-test=checkbox-field]").within(() => {
                    checkBoxWithLabel("Project administrators");
                    checkBoxWithLabel("Integrators");
                    uncheckBoxWithLabel("Contributors");
                });

                getFieldWithLabel("Openlist static", "[data-test=openlist-field]").within(() => {
                    selectLabelInSelect2Dropdown("Alpha");
                    selectLabelInSelect2Dropdown("Charlie");
                });

                getFieldWithLabel("Openlist users (members)", "[data-test=openlist-field]").within(
                    () => {
                        clearSelect2();
                        // The list is never populated, it is fetched dynamically by autocomplete
                        // eslint-disable-next-line cypress/require-data-selectors
                        cy.get("input[type=search]").type("proj");
                        selectLabelInSelect2Dropdown("ProjectMember (ProjectMember)");
                    }
                );

                getFieldWithLabel("Openlist ugroups", "[data-test=openlist-field]").within(() => {
                    selectLabelInSelect2Dropdown("Project members");
                    selectLabelInSelect2Dropdown("Integrators");
                });
            });

            getFieldWithLabel("Artifact link", "[data-test=artifactlink-field]").within(() => {
                cy.get("[data-test=artifactlink-field-input]").clear().type(this.artifact_link_id);
            });

            cy.get("[data-test=add-comment-form]").within(() => {
                cy.get("[data-test=format]").select("Markdown");
                cy.get("[data-test=textarea]").type("Follow-up comment");
            });

            cy.get("[data-test=artifact-modal-save-button]").click();
        });
        waitForKanbanCard(`Editable Artifact ${now}`);
    });
});

function getArtifactLinkIdFromREST(): Cypress.Chainable<number> {
    return cy
        .getProjectId("kanban-artifact-modal")
        .then((project_id: number) => {
            return cy
                .getFromTuleapAPI(`/api/projects/${project_id}/trackers`)
                .then((response) => parseInt(response.body[0].id, 10));
        })
        .then((tracker_id: number) => {
            return cy
                .getFromTuleapAPI(`/api/trackers/${tracker_id}/artifacts`)
                .then(
                    (response): Artifact =>
                        response.body.find(
                            (artifact: Artifact) => artifact.title === "Linked Artifact"
                        )
                )
                .then((artifact: Artifact) => {
                    return artifact.id;
                });
        });
}

interface Artifact {
    id: number;
    title: string;
}

type CypressWrapper = Cypress.Chainable<JQuery<HTMLElement>>;

function getFieldsetWithLabel(label: string): CypressWrapper {
    return cy
        .get("[data-test=fieldset-label]")
        .contains(label)
        .parents("[data-test=fieldset]")
        .within(() => {
            return cy.get("[data-test=fieldset-content]");
        });
}

function getFieldWithLabel(label: string, form_element_selector: string): CypressWrapper {
    // eslint-disable-next-line cypress/require-data-selectors
    return cy.get(form_element_selector).contains(label).parents(form_element_selector);
}

function checkRadioButtonWithLabel(label: string): void {
    cy.get("[data-test=radiobutton-field-value]")
        .contains(label)
        .within(() => {
            cy.get("[data-test=radiobutton-field-input]").check();
        });
}

function checkBoxWithLabel(label: string): void {
    cy.get("[data-test=checkbox-field-value]")
        .contains(label)
        .within(() => {
            cy.get("[data-test=checkbox-field-input]").check();
        });
}

function uncheckBoxWithLabel(label: string): void {
    cy.get("[data-test=checkbox-field-value]")
        .contains(label)
        .within(() => {
            cy.get("[data-test=checkbox-field-input]").uncheck();
        });
}

function selectLabelInListPickerDropdown(
    label: string
): Cypress.Chainable<JQuery<HTMLBodyElement>> {
    cy.get("[data-test=list-picker-selection]").click();
    return cy
        .root()
        .parents("body")
        .within(() => {
            cy.get("[data-test-list-picker-dropdown-open]").within(() => {
                cy.get("[data-test=list-picker-item]").contains(label).click();
            });
        });
}

function selectLabelInSelect2Dropdown(label: string): Cypress.Chainable<JQuery<HTMLBodyElement>> {
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-selection").click();
    return cy
        .root()
        .parents("body")
        .within(() => {
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-results").within(() => {
                // eslint-disable-next-line cypress/require-data-selectors
                cy.get(".select2-results__option").contains(label).click();
            });
        });
}

function clearSelect2(): void {
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-selection__clear").click();
}

function getKanbanCard(label: string): CypressWrapper {
    return cy
        .get("[data-test-static=kanban-item-content]")
        .contains(label)
        .parents("[data-test-static=kanban-item-content]");
}

function waitForKanbanCard(label: string): void {
    cy.contains("[data-test-static=kanban-item-content]", label, { timeout: 10000 });
}
