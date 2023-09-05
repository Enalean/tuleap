/**
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

import type { DataFormat, GroupedDataFormat, IdTextPair, LoadingData } from "tlp";
import { select2 } from "tlp";
import { sanitize } from "dompurify";
import mustache from "mustache";
import $ from "jquery";

document.addEventListener("DOMContentLoaded", () => {
    initThemeColorSelector();
    initAccessibilitySelector();
    initSelectBoxesPreviews();
});

function initThemeColorSelector(): void {
    const selector = document.getElementById("user-preferences-color-selector");
    if (!(selector instanceof HTMLSelectElement) || !selector.dataset.colors) {
        return;
    }

    const colors = JSON.parse(selector.dataset.colors);
    select2(selector, {
        minimumResultsForSearch: Infinity,
        data: colors,
        escapeMarkup: sanitize,
        templateResult(result: LoadingData | DataFormat | GroupedDataFormat): string {
            return mustache.render(
                `<i class="fa fa-circle user-preferences-color-selector-selection-{{ id }}" aria-hidden="true"></i>
                    {{ text }}
                </div>`,
                result,
            );
        },
        templateSelection(
            result: IdTextPair | LoadingData | DataFormat | GroupedDataFormat,
        ): string {
            return mustache.render(
                `<i class="fa fa-circle user-preferences-color-selector-selection-{{ id }}" aria-hidden="true"></i>
                    {{ text }}
                </div>`,
                result,
            );
        },
    });

    $(selector).on("change", changePreviewColor);
    changePreviewColor();

    function changePreviewColor(): void {
        const preview = document.getElementById("user-preferences-section-appearance-preview");
        if (preview === null || selector === null || !(selector instanceof HTMLSelectElement)) {
            return;
        }

        const class_prefix = "user-preferences-section-appearance-preview-";
        colors.forEach((color: DataFormat) => preview.classList.remove(class_prefix + color.id));
        preview.classList.add(class_prefix + selector.value);
    }
}

function initAccessibilitySelector(): void {
    const selector = document.getElementById("user-preferences-accessibility-selector");
    if (selector === null) {
        return;
    }

    selector.addEventListener("change", changePreviewAccessibility);
    changePreviewAccessibility();

    function changePreviewAccessibility(): void {
        const preview = document.getElementById("user-preferences-section-appearance-preview");
        if (preview === null || !(selector instanceof HTMLInputElement)) {
            return;
        }

        const classname = "user-preferences-section-appearance-preview-without-accessibility";
        if (selector.checked) {
            preview.classList.remove(classname);
        } else {
            preview.classList.add(classname);
        }
    }
}

function initSelectBoxesPreviews(): void {
    // init relative dates preview
    initSelectBoxPreview(
        document,
        "#relative-dates-display",
        "#user-preferences-section-appearance-relative-dates-preview",
        ".user-preferences-section-appearance-relative-dates-preview-option",
        "data-relative-date-option-name",
    );

    // init usernames format preview
    initSelectBoxPreview(
        document,
        "#user-prefs-username-display-format-select",
        "#user-preferences-section-appearance-usernames-display-preview",
        ".user-preference-appearance-section-usernames-display-preview-option",
        "data-usernames-display-option-value",
    );
}

export function initSelectBoxPreview(
    doc: HTMLDocument,
    selectbox_id: string,
    preview_id: string,
    option_css_class: string,
    data_attribute_name: string,
): void {
    const selector = doc.querySelector(selectbox_id);
    const preview = doc.querySelector(preview_id);

    if (selector === null || preview === null) {
        return;
    }

    if (!(selector instanceof HTMLSelectElement) || !(preview instanceof SVGElement)) {
        return;
    }

    changePreviewAccordingToSelectedValue(selector, preview, option_css_class, data_attribute_name);

    selector.addEventListener("change", () => {
        changePreviewAccordingToSelectedValue(
            selector,
            preview,
            option_css_class,
            data_attribute_name,
        );
    });
}

function changePreviewAccordingToSelectedValue(
    selector: HTMLSelectElement,
    preview: SVGElement,
    option_css_class: string,
    data_attribute_name: string,
): void {
    const previously_selected_option = preview.querySelector(option_css_class + ".shown");

    if (previously_selected_option !== null && previously_selected_option instanceof SVGElement) {
        previously_selected_option.classList.remove("shown");
    }

    const selected_option_name = selector.value;
    const selected_option = preview.querySelector(
        `[${data_attribute_name}="${selected_option_name}"]`,
    );

    if (selected_option === null || !(selected_option instanceof SVGElement)) {
        return;
    }

    selected_option.classList.toggle("shown");
}
