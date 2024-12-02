/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import type { InternalEditCrossReferenceFormElement } from "./EditCrossReferenceFormElement";
import type { GetText } from "@tuleap/gettext";

export const renderEditCrossReferenceFormElement = (
    host: InternalEditCrossReferenceFormElement,
    gettext_provider: GetText,
): UpdateFunction<InternalEditCrossReferenceFormElement> => {
    const onSubmit = (host: InternalEditCrossReferenceFormElement, event: Event): void => {
        event.preventDefault();
        host.edit_cross_reference_callback(host.reference_text.trim());
    };

    return html`
        <form
            data-role="popover"
            class="tlp-popover"
            onsubmit="${onSubmit}"
            data-test="edit-cross-ref-form"
        >
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-header">
                <h1 class="tlp-popover-title">
                    ${gettext_provider.gettext("Edit cross reference")}
                </h1>
            </div>
            <div class="tlp-popover-body">
                <div class="tlp-form-element">
                    <label for="reference-text" class="tlp-label">
                        ${gettext_provider.gettext("Reference")}
                        <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
                    </label>
                    <input
                        id="reference-text"
                        data-test="reference-text"
                        type="text"
                        class="tlp-input"
                        placeholder="art #123"
                        value="${host.reference_text}"
                        oninput="${html.set("reference_text")}"
                        pattern="\\w+\\s#(?:\\w|:|\\.|\\/|-)+"
                        required
                        autofocus
                    />
                </div>
            </div>
            <div class="tlp-popover-footer">
                <button
                    type="button"
                    class="tlp-button-primary tlp-button-small tlp-button-outline"
                    data-dismiss="popover"
                    onclick="${host.cancel_callback}"
                    data-test="cancel-button"
                >
                    ${gettext_provider.gettext("Cancel")}
                </button>
                <button type="submit" class="tlp-button-primary tlp-button-small">
                    ${gettext_provider.gettext("Save")}
                </button>
            </div>
        </form>
    `;
};
