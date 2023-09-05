/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import mustache from "mustache";
import { sanitize } from "dompurify";
import { get } from "jquery";
import type { Modal } from "tlp";
import { createModal } from "tlp";
import { filterInlineTable } from "@tuleap/filter-inline-table";

interface Widget {
    id: string;
    is_used: boolean;
    can_be_added_from_widget_list: boolean;
}

interface WidgetCategory {
    name: string;
    widgets: Widget[];
}

export default init;

function init(): void {
    initSingleButtonModals();
    initAddWidgetModal();
}

function getModalContent(button: HTMLElement, button_id: string): HTMLElement {
    const modal_id = button.dataset.targetModalId;
    if (!modal_id) {
        throw new Error("Missing data-target-modal-id attribute for button " + button_id);
    }
    const modal_content = document.getElementById(modal_id);
    if (!modal_content) {
        throw new Error("Cannot find the modal " + modal_id);
    }
    return modal_content;
}

function initAddWidgetModal(): void {
    const buttons_class = "add-widget-button";
    const buttons = document.querySelectorAll("." + buttons_class);
    if (buttons.length <= 0) {
        return;
    }
    const first_button = buttons[0];
    if (!(first_button instanceof HTMLElement)) {
        throw new Error("First button is not a HTMLElement");
    }
    const button_href = first_button.dataset.href;
    if (!button_href) {
        throw new Error("No href data in button");
    }

    const modal_content = getModalContent(first_button, buttons_class);
    const modal = createModal(modal_content);

    [].forEach.call(buttons, function (button: HTMLElement) {
        button.addEventListener("click", function (event) {
            event.preventDefault();
            modal.toggle();
        });
    });

    modal.addEventListener("tlp-modal-shown", function () {
        loadDynamicallyWidgetsContent(modal, modal_content, button_href);
    });
}

function initSingleButtonModals(): void {
    const buttons = document.querySelectorAll(
        [
            "#add-dashboard-button",
            "#delete-dashboard-button",
            "#edit-dashboard-button",
            "#no-widgets-edit-dashboard-button",
            ".delete-widget-button",
            ".edit-widget-button",
        ].join(", "),
    );

    [].forEach.call(buttons, function (button: HTMLElement) {
        if (!button) {
            return;
        }

        const modal_content = getModalContent(button, button.id);
        const modal = createModal(modal_content);

        button.addEventListener("click", function (event) {
            event.preventDefault();
            modal.toggle();
        });

        if (button.classList.contains("edit-widget-button")) {
            modal.addEventListener("tlp-modal-shown", function () {
                loadDynamicallyEditModalContent(modal, modal_content);
            });
        }
    });
}

function loadDynamicallyEditModalContent(modal: Modal, modal_content: HTMLElement): void {
    const widget_id = modal_content.dataset.widgetId,
        container = modal_content.querySelector(".edit-widget-modal-content"),
        button = modal_content.querySelector("button[type=submit]");

    if (!container) {
        throw new Error("edit-widget-modal-content element does not exist");
    }
    if (!widget_id) {
        throw new Error("No widget id in dataset");
    }
    if (!(button instanceof HTMLButtonElement)) {
        throw new Error("No button in modal");
    }

    if (!container.classList.contains("edit-widget-modal-content-loading")) {
        container.innerHTML = "";
        container.classList.add("edit-widget-modal-content-loading");
    }

    get("/widgets/?widget-id=" + encodeURIComponent(widget_id) + "&action=get-edit-modal-content")
        .done(function (html) {
            button.disabled = false;
            container.innerHTML = sanitize(html);

            document.dispatchEvent(
                new CustomEvent("dashboard-edit-widget-modal-content-loaded", {
                    detail: { target: container },
                }),
            );
        })
        .fail(function (data) {
            container.innerHTML = sanitize(
                '<div class="tlp-alert-danger">' + data.responseJSON + "</div>",
            );
        })
        .always(function () {
            container.classList.remove("edit-widget-modal-content-loading");
            modal.removeEventListener("tlp-modal-shown", function () {
                loadDynamicallyEditModalContent(modal, modal_content);
            });
        });
}

function loadDynamicallyWidgetsContent(
    modal: Modal,
    modal_content: HTMLElement,
    url: string,
): void {
    const widgets_categories = document.getElementById(
        "dashboard-add-widget-list-table-placeholder",
    );
    if (!widgets_categories) {
        throw new Error("dashboard-add-widget-list-table-placeholder element does not exist");
    }
    const widgets_categories_template = widgets_categories.textContent;
    if (!widgets_categories_template) {
        throw new Error("widgets_categories_template is null");
    }

    const table = modal_content.querySelector("#dashboard-add-widget-list-table");
    if (!table) {
        throw new Error("dashboard-add-widget-list-table element does not exist");
    }
    const container = modal_content.querySelector(".dashboard-add-widget-content-container");

    get(url)
        .done(function (data) {
            const header = document.getElementById("dashboard-add-widget-list-header-filter-table");
            if (!(header instanceof HTMLInputElement)) {
                throw new Error(
                    "dashboard-add-widget-list-header-filter-table element does not exist",
                );
            }
            const filter = filterInlineTable(header);
            modal.addEventListener("tlp-modal-hidden", function () {
                filter.filterTable();
            });

            if (container) {
                container.outerHTML = sanitize(mustache.render(widgets_categories_template, data));
                initializeWidgets(table, data);
            }
        })
        .fail(function (data) {
            const alert = document.getElementById("dashboard-add-widget-error-message");
            if (!alert) {
                throw new Error("dashboard-add-widget-error-message element does not exist");
            }
            alert.classList.add("tlp-alert-danger");
            alert.innerHTML = sanitize(data.responseJSON);

            const header_filter = document.getElementById(
                "dashboard-add-widget-list-header-filter",
            );
            const list_table = document.getElementById("dashboard-add-widget-list-table");
            if (!header_filter) {
                throw new Error("dashboard-add-widget-list-header-filter element does not exist");
            }
            if (!list_table) {
                throw new Error("dashboard-add-widget-list-table element does not exist");
            }
            header_filter.remove();
            list_table.remove();
        })
        .always(function () {
            modal.removeEventListener("tlp-modal-shown", function () {
                loadDynamicallyWidgetsContent(modal, modal_content, url);
            });
        });
}

function initializeWidgets(table: Element, data: { widgets_categories: WidgetCategory[] }): void {
    const data_widgets: Widget[] = [];

    data.widgets_categories.forEach((category) => {
        category.widgets.forEach((widget) => {
            data_widgets.push(widget);
        });
    });

    const widgets_element = table.querySelectorAll(".dashboard-add-widget-list-table-widget");
    [].forEach.call(widgets_element, function (widget_element: HTMLElement) {
        widget_element.addEventListener("click", function () {
            displayWidgetSettings(table, widget_element, data_widgets);
        });
    });
}

function displayWidgetSettings(
    table: Element,
    widget_element: HTMLElement,
    data_widgets: Widget[],
): void {
    const widget_settings = document.getElementById("dashboard-add-widget-settings-placeholder");
    if (!widget_settings) {
        throw new Error("dashboard-add-widget-settings-placeholder element does not exist");
    }
    const widget_settings_template = widget_settings.textContent;
    if (!widget_settings_template) {
        throw new Error("widget_settings_template is null");
    }

    const widget_data = data_widgets.find(
        (widget) => widget.id === widget_element.dataset.widgetId,
    );
    if (widget_data === undefined) {
        return;
    }

    const settings = document.getElementById("dashboard-add-widget-settings");
    if (!settings) {
        throw new Error("dashboard-add-widget-settings element does not exist");
    }

    settings.innerHTML = sanitize(mustache.render(widget_settings_template, widget_data));
    document.dispatchEvent(
        new CustomEvent("dashboard-add-widget-settings-loaded", {
            detail: { target: settings },
        }),
    );

    const already_selected_widget = table.querySelector(
        ".dashboard-add-widget-list-table-widget-selected",
    );
    if (already_selected_widget) {
        already_selected_widget.classList.remove("dashboard-add-widget-list-table-widget-selected");
    }
    widget_element.classList.add("dashboard-add-widget-list-table-widget-selected");

    const add_widget_button = document.getElementById("dashboard-add-widget-button");
    if (!(add_widget_button instanceof HTMLButtonElement)) {
        throw new Error("dashboard-add-widget-button element does not exist");
    }
    add_widget_button.disabled = !(
        !widget_data.is_used && widget_data.can_be_added_from_widget_list
    );
}
