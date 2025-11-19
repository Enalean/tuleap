/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { selectOrThrow } from "@tuleap/dom";
import type { BuilderData, SubmitData, TriggeringField, TriggeringFieldFactory } from "./type";
import type { GetText } from "@tuleap/gettext";

export function getTriggeringField(
    id: number,
    builder_data: BuilderData,
    gettext_provider: GetText,
): TriggeringField {
    const trigger_triggering_field_list = selectOrThrow(document, "#trigger_triggering_field_list");

    const tr = document.createElement("tr");
    tr.className = "trigger_triggering_field";
    tr.dataset.triggerConditionInitial = "false";

    const td1 = document.createElement("td");
    td1.className = "trigger_condition_selector";
    const span1 = document.createElement("span");
    span1.className = "trigger_condition_artifact_operator_updater";
    td1.appendChild(span1);

    const td2 = document.createElement("td");

    const select_tracker = document.createElement("select");
    select_tracker.classList.add(
        "trigger_triggering_field_child_tracker_name",
        "tlp-select",
        "tlp-select-adjusted",
    );
    select_tracker.append(getSelectOption(gettext_provider.gettext("select child tracker name")));
    Object.values(builder_data.triggers).forEach((child_tracker) => {
        const option = document.createElement("option");
        option.value = String(child_tracker.id);
        option.textContent = child_tracker.name;

        select_tracker.append(option);
    });

    const have_field = document.createElement("span");
    have_field.className = "trigger_triggering_have_field";

    const select_field = document.createElement("select");
    select_field.classList.add(
        "trigger_triggering_field_child_tracker_field_name",
        "tlp-select",
        "tlp-select-adjusted",
    );
    select_field.appendChild(
        getSelectOption(gettext_provider.gettext("select child tracker field")),
    );

    const select_value = document.createElement("select");
    select_value.classList.add(
        "trigger_triggering_field_child_tracker_field_value",
        "tlp-select",
        "tlp-select-adjusted",
    );
    select_value.appendChild(getSelectOption(gettext_provider.gettext("select value")));

    const button = document.createElement("button");
    button.classList.add("tlp-button-danger", "tlp-button-mini", "tlp-button-outline");
    const icon = document.createElement("i");
    icon.classList.add("fa-regular", "fa-trash-alt", "tlp-button-icon");
    icon.setAttribute("aria-hidden", "true");
    button.append(icon, gettext_provider.gettext("Delete condition"));

    td2.append(
        gettext_provider.gettext(" of type "),
        select_tracker,
        " ",
        have_field,
        " ",
        select_field,
        gettext_provider.gettext(" set to "),
        select_value,
        button,
    );

    const select_when = document.createElement("select");
    select_when.id = "trigger_condition_quantity";
    select_when.classList.add("tlp-select", "tlp-select-adjusted");

    const at_least_one = document.createElement("option");
    at_least_one.value = "at_least_one";
    at_least_one.setAttribute("data-condition-operator", "or");
    at_least_one.textContent = gettext_provider.gettext("at least one child");

    const all_of = document.createElement("option");
    all_of.value = "all_of";
    all_of.setAttribute("data-condition-operator", "and");
    all_of.textContent = gettext_provider.gettext("all children");

    select_when.appendChild(at_least_one);
    select_when.appendChild(all_of);

    tr.append(td1, td2);

    trigger_triggering_field_list.appendChild(tr);

    select_tracker.addEventListener("change", () => {
        removeAllOptions();
        addTrackerFieldsData(Number(select_tracker.value));
    });

    select_field.addEventListener("change", () => {
        removeExistingFieldValues();
        addTrackerFieldValuesData(
            parseInt(select_tracker.value, 10),
            parseInt(select_field.value, 10),
        );
    });

    function addTrackerFieldsData(tracker_id: number): void {
        if (typeof builder_data.triggers[tracker_id] === "undefined") {
            return;
        }

        Object.values(builder_data.triggers[tracker_id].fields).forEach((field) => {
            const option = document.createElement("option");
            option.classList.add("trigger-triggering_field-tracker-field");
            option.value = String(field.id);
            option.textContent = field.label;
            select_field.append(option);
        });
    }

    function addTrackerFieldValuesData(tracker_id: number, field_id: number): void {
        if (typeof builder_data.triggers[tracker_id] === "undefined") {
            return;
        }

        if (typeof builder_data.triggers[tracker_id].fields[field_id] === "undefined") {
            return;
        }

        builder_data.triggers[tracker_id].fields[field_id].values.forEach((value) => {
            const option = document.createElement("option");
            option.classList.add("trigger-triggering_field-tracker-field-value");
            option.value = String(value.id);
            option.textContent = value.label;
            select_value.append(option);
        });
    }

    function removeAllOptions(): void {
        document
            .querySelectorAll(".trigger-triggering_field-tracker-field")
            .forEach(function (tracker_value) {
                if (tr.contains(tracker_value)) {
                    tracker_value.remove();
                }
            });

        removeExistingFieldValues();
    }

    function removeExistingFieldValues(): void {
        document
            .querySelectorAll(".trigger-triggering_field-tracker-field-value")
            .forEach(function (field_value) {
                if (tr.contains(field_value)) {
                    field_value.remove();
                }
            });
    }

    return {
        id,
        remove(): void {
            tr.remove();
        },
        removeAllOptions,
        activateDeleteButton(factory: TriggeringFieldFactory): void {
            button.addEventListener("click", () => {
                factory.removeTriggeringField(id);
            });
        },
        removeDeleteButton(): void {
            button.remove();
        },
        addConditionSelector(): void {
            const when = document.createTextNode(gettext_provider.gettext("When "));

            td1.innerHTML = "";
            td1.append(when, select_when);

            tr.setAttribute("data-trigger-condition-initial", "true");
        },
        isInitialCondition(): boolean {
            return tr.getAttribute("data-trigger-condition-initial") === "true";
        },
        makeOperatorDynamic(): void {
            updateOperators();

            select_when.addEventListener("change", updateOperators);

            function updateOperators(): void {
                document
                    .querySelectorAll(".trigger_condition_artifact_operator_updater")
                    .forEach(function (span) {
                        span.textContent = String(
                            document
                                .getElementById("triggers_existing")
                                ?.getAttribute("data-children-" + select_when.value),
                        );
                    });

                document
                    .querySelectorAll(".trigger_triggering_have_field")
                    .forEach(function (span) {
                        span.textContent = String(
                            document
                                .getElementById("triggers_existing")
                                ?.getAttribute("data-have-field-" + select_when.value),
                        );
                    });
            }
        },
        toJSON(): SubmitData["triggering_fields"][0] | null {
            const field_id = select_field.value;
            if (field_id === "") {
                return null;
            }

            const field_value_id = select_value.value;
            if (field_value_id === "") {
                return null;
            }

            return {
                field_id: parseInt(field_id, 10),
                field_value_id: parseInt(field_value_id, 10),
                field_label: select_field.options[select_field.selectedIndex].textContent,
                field_value_label: select_value.options[select_value.selectedIndex].textContent,
                tracker_name: select_tracker.options[select_tracker.selectedIndex].textContent,
            };
        },
    };
}

function getSelectOption(label: string): HTMLOptionElement {
    const option = document.createElement("option");
    option.selected = true;
    option.value = "";
    option.textContent = label;

    return option;
}
