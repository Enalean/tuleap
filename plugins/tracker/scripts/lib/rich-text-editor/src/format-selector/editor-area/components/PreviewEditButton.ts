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

export interface PreviewButtonPresenter {
    readonly is_in_edit_mode: boolean;
    readonly onClickCallback: () => void;
}

export function createPreviewEditButton(
    presenter: PreviewButtonPresenter,
    gettext_provider: GettextProvider
): TemplateResult {
    const button_label = presenter.is_in_edit_mode
        ? gettext_provider.gettext("Preview")
        : gettext_provider.gettext("Edit");
    const icon_class = presenter.is_in_edit_mode ? "fa-eye" : "fa-pencil-alt";

    return html`
        <button
            type="button"
            class="btn btn-small rte-button"
            @click="${presenter.onClickCallback}"
        >
            <i class="fas fa-fw ${icon_class}" aria-hidden="true" data-test="button-icon"></i>
            ${button_label}
        </button>
    `;
}
