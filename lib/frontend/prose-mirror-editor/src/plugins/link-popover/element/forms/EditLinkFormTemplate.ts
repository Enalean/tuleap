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
import type { GetText } from "@tuleap/gettext";
import type { InternalEditLinkFormElement } from "./EditLinkFormElement";

export const renderEditLinkForm = (
    host: InternalEditLinkFormElement,
    gettext_provider: GetText,
): UpdateFunction<InternalEditLinkFormElement> => {
    const onSubmit = (host: InternalEditLinkFormElement, event: Event): void => {
        event.preventDefault();

        const href = host.link_href.trim();
        const title = host.link_title.trim();

        host.edit_link_callback({
            href,
            title: title.length ? title : href,
        });
    };

    return html`
        <form
            data-role="popover"
            class="tlp-popover"
            onsubmit="${onSubmit}"
            data-test="edit-link-form"
        >
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-header">
                <h1 class="tlp-popover-title">${gettext_provider.gettext("Edit link")}</h1>
            </div>
            <div id="link-edition-form" class="tlp-popover-body">
                <div class="tlp-form-element">
                    <label for="link-href" class="tlp-label">
                        ${gettext_provider.gettext("Link")}
                        <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
                    </label>
                    <input
                        id="link-href"
                        data-test="input-href"
                        type="url"
                        class="tlp-input"
                        placeholder="https://example.com"
                        required=""
                        pattern="https?://.+"
                        value="${host.link_href}"
                        oninput="${html.set("link_href")}"
                        autofocus
                    />
                </div>
                <div class="tlp-form-element">
                    <label for="link-title" class="tlp-label">
                        ${gettext_provider.gettext("Text")}
                    </label>
                    <input
                        id="link-title"
                        data-test="input-title"
                        type="text"
                        class="tlp-input"
                        placeholder="${gettext_provider.gettext("Text")}"
                        value="${host.link_title}"
                        oninput="${html.set("link_title")}"
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
