/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import "../styles/main.scss";
import { createDropdown } from "@tuleap/tlp-dropdown";
import { createModal, openAllTargetModalsOnClick } from "@tuleap/tlp-modal";
import { getJSON, uri } from "@tuleap/fetch-result";
import { getAttributeOrThrow, selectOrThrow } from "@tuleap/dom";

const HIDDEN_CLASSNAME = "git-admin-hidden";

document.addEventListener("DOMContentLoaded", () => {
    initGerritTemplates();
    const project_input = document.getElementById("project_id");
    if (!(project_input instanceof HTMLInputElement)) {
        return;
    }
    const project_id = project_input.value;
    initLoadConfigTemplate(project_id);
    initLoadGerritConfig(project_id);
    initEditTemplateModal(project_id);
    initViewTemplateModal(project_id);
});

function initGerritTemplates(): void {
    const dropdown_button = document.getElementById("git_admin_config_btn_create");
    const dropdown_menu = document.getElementById("git-admin-gerrit-choice");
    if (!dropdown_button || !dropdown_menu) {
        return;
    }
    createDropdown(dropdown_button, { dropdown_menu });
    openAllTargetModalsOnClick(document, "[data-open-modal]");
}

function initEditTemplateModal(project_id: string): void {
    const buttons = document.querySelectorAll("[data-edit-template-button]");
    for (const button of buttons) {
        if (!(button instanceof HTMLElement)) {
            continue;
        }
        button.addEventListener("click", () => {
            const template_id = getAttributeOrThrow(button, "data-template-id");
            const template_name = getAttributeOrThrow(button, "data-template-name");
            const feedback = selectOrThrow(document, "#gerrit-admin-feedback");
            const icon = selectOrThrow(button, "[data-button-icon]");

            icon.classList.remove("fa-solid", "fa-pencil");
            icon.classList.add("fa-solid", "fa-circle-notch", "fa-spin");
            button.setAttribute("disabled", "");

            getJSON<string>(
                uri`/plugins/git/?group_id=${project_id}&action=fetch_git_template&template_id=${template_id}`,
            )
                .match(
                    (gerrit_config) => {
                        const config_textarea = selectOrThrow(document, "#edit-template-text");
                        const template_name_span = selectOrThrow(
                            document,
                            "#git_admin_config_template_name",
                        );
                        const template_id_input = selectOrThrow(
                            document,
                            "#git_admin_template_id",
                            HTMLInputElement,
                        );
                        const modal_element = selectOrThrow(document, "#edit-gerrit-template");

                        template_id_input.value = template_id;
                        template_name_span.textContent = template_name;
                        config_textarea.textContent = gerrit_config;
                        createModal(modal_element, {
                            destroy_on_hide: true,
                            keyboard: true,
                        }).show();
                    },
                    (fault) => {
                        feedback.classList.remove(HIDDEN_CLASSNAME);
                        feedback.textContent = String(fault);
                    },
                )
                .then(() => {
                    icon.classList.remove("fa-solid", "fa-circle-notch", "fa-spin");
                    icon.classList.add("fa-solid", "fa-pencil");
                    button.removeAttribute("disabled");
                });
        });
    }
}

function initViewTemplateModal(project_id: string): void {
    const buttons = document.querySelectorAll("[data-view-template-button]");
    for (const button of buttons) {
        if (!(button instanceof HTMLElement)) {
            continue;
        }
        button.addEventListener("click", () => {
            const template_id = getAttributeOrThrow(button, "data-template-id");
            const template_name = getAttributeOrThrow(button, "data-template-name");
            const feedback = selectOrThrow(document, "#gerrit-admin-feedback");
            const icon = selectOrThrow(button, "[data-button-icon]");

            icon.classList.remove("fa-solid", "fa-eye");
            icon.classList.add("fa-solid", "fa-circle-notch", "fa-spin");
            button.setAttribute("disabled", "");

            getJSON<string>(
                uri`/plugins/git/?group_id=${project_id}&action=fetch_git_template&template_id=${template_id}`,
            )
                .match(
                    (gerrit_config) => {
                        const config_textarea = selectOrThrow(document, "#view-template-text");
                        const template_name_span = selectOrThrow(document, "#view-template-name");
                        const modal_element = selectOrThrow(document, "#view-gerrit-template");

                        template_name_span.textContent = template_name;
                        config_textarea.textContent = gerrit_config;
                        createModal(modal_element, {
                            destroy_on_hide: true,
                            keyboard: true,
                        }).show();
                    },
                    (fault) => {
                        feedback.classList.remove(HIDDEN_CLASSNAME);
                        feedback.textContent = String(fault);
                    },
                )
                .then(() => {
                    icon.classList.remove("fa-solid", "fa-circle-notch", "fa-spin");
                    icon.classList.add("fa-solid", "fa-eye");
                    button.removeAttribute("disabled");
                });
        });
    }
}

function initLoadConfigTemplate(project_id: string): void {
    const template_selector = document.getElementById("git_admin_template_list");
    if (!(template_selector instanceof HTMLSelectElement)) {
        return;
    }
    template_selector.addEventListener("change", () => {
        const template = template_selector.value;
        if (template === "") {
            return;
        }
        const feedback = selectOrThrow(document, "#template-feedback");
        const spinner = selectOrThrow(document, "#template-spinner");
        const config_inputs = document.querySelectorAll("[data-template-config]");
        const save_button = selectOrThrow(document, "[data-template-button]");

        feedback.classList.add(HIDDEN_CLASSNAME);
        spinner.classList.remove(HIDDEN_CLASSNAME);
        for (const input of config_inputs) {
            input.classList.add(HIDDEN_CLASSNAME);
        }
        save_button.setAttribute("disabled", "");

        getJSON<string>(
            uri`/plugins/git/?group_id=${project_id}&action=fetch_git_template&template_id=${template}`,
        )
            .match(
                (gerrit_config) => {
                    const config_textarea = selectOrThrow(document, "#template-config-text");
                    config_textarea.textContent = gerrit_config;
                    for (const input of config_inputs) {
                        input.classList.remove(HIDDEN_CLASSNAME);
                    }
                    save_button.removeAttribute("disabled");
                },
                (fault) => {
                    feedback.classList.remove(HIDDEN_CLASSNAME);
                    selectOrThrow(feedback, "[data-alert]").textContent = String(fault);
                },
            )
            .then(() => {
                spinner.classList.add(HIDDEN_CLASSNAME);
            });
    });
}

function initLoadGerritConfig(project_id: string): void {
    const config_selector = document.getElementById("git_admin_config_list");
    if (!(config_selector instanceof HTMLSelectElement)) {
        return;
    }
    config_selector.addEventListener("change", () => {
        const remote_repository = config_selector.value;
        if (remote_repository === "") {
            return;
        }
        const feedback = selectOrThrow(document, "#template-from-gerrit-feedback");
        const spinner = selectOrThrow(document, "#template-from-gerrit-spinner");
        const config_inputs = document.querySelectorAll("[data-gerrit-config]");
        const save_button = selectOrThrow(document, "[data-gerrit-button]");

        feedback.classList.add(HIDDEN_CLASSNAME);
        spinner.classList.remove(HIDDEN_CLASSNAME);
        for (const input of config_inputs) {
            input.classList.add(HIDDEN_CLASSNAME);
        }
        save_button.setAttribute("disabled", "");

        getJSON<string>(
            uri`/plugins/git/?group_id=${project_id}&action=fetch_git_config&repo_id=${remote_repository}`,
        )
            .match(
                (gerrit_config) => {
                    const config_textarea = selectOrThrow(document, "#gerrit-config-text");
                    config_textarea.textContent = gerrit_config;
                    for (const input of config_inputs) {
                        input.classList.remove(HIDDEN_CLASSNAME);
                    }
                    save_button.removeAttribute("disabled");
                },
                (fault) => {
                    feedback.classList.remove(HIDDEN_CLASSNAME);
                    selectOrThrow(feedback, "[data-alert]").textContent = String(fault);
                },
            )
            .then(() => {
                spinner.classList.add(HIDDEN_CLASSNAME);
            });
    });
}
