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

import type { GettextProvider } from "@tuleap/gettext";
import type { TemplateResult } from "lit-html";
import { html } from "lit-html";
import { until } from "lit-html/directives/until.js";

export interface PreviewButtonPresenter {
    readonly is_in_edit_mode: boolean;
    readonly promise_of_preview: Promise<unknown>;
    readonly onClickCallback: () => void;
}

export function createPreviewEditButton(
    presenter: PreviewButtonPresenter,
    gettext_provider: GettextProvider,
): TemplateResult {
    const button_label = presenter.is_in_edit_mode
        ? gettext_provider.gettext("Preview")
        : gettext_provider.gettext("Edit");
    const loading_button = html`
        <button
            type="button"
            class="tlp-button-secondary tlp-button-outline tlp-button-small rte-button"
            disabled
        >
            <i
                class="fa-solid fa-fw fa-spin fa-circle-notch tlp-button-icon"
                aria-hidden="true"
            ></i>
            ${button_label}
        </button>
    `;
    const icon_class = presenter.is_in_edit_mode ? "fa-solid fa-eye" : "fa-solid fa-pencil";
    const enabled_button = html`
        <button
            type="button"
            class="tlp-button-secondary tlp-button-outline tlp-button-small rte-button"
            data-test="preview-edit-button"
            @click="${presenter.onClickCallback}"
        >
            <i
                class="fa-fw ${icon_class} tlp-button-icon"
                aria-hidden="true"
                data-test="button-icon"
            ></i>
            ${button_label}
        </button>
    `;
    // If loading fails, keep the button enabled to retry
    const promise_of_button = presenter.promise_of_preview.then(
        () => enabled_button,
        () => enabled_button,
    );

    return html`${until(promise_of_button, loading_button)}`;
}
