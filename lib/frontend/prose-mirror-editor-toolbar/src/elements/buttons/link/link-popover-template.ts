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

import type { UpdateFunction } from "hybrids";
import { html } from "hybrids";
import type { InternalLinkButtonElement } from "./link";
import type { GetText } from "@tuleap/gettext";

const onSubmit = (host: InternalLinkButtonElement, event: Event): void => {
    event.preventDefault();

    host.popover_instance.hide();
    host.toolbar_bus.link({
        href: host.link_href,
        title: host.link_title ? host.link_title : host.link_href,
    });
};

const getPopoverHeader = (
    host: InternalLinkButtonElement,
    gettext_provider: GetText,
): UpdateFunction<InternalLinkButtonElement> => {
    const popover_title = host.is_activated
        ? gettext_provider.gettext("Create link")
        : gettext_provider.gettext("Edit link");

    return html` <h1 class="tlp-popover-title">${popover_title}</h1> `;
};

export const renderLinkPopover = (
    host: InternalLinkButtonElement,
    gettext_provider: GetText,
): UpdateFunction<InternalLinkButtonElement> => html`
    <form
        data-role="popover"
        class="tlp-popover prose-mirror-toolbar-popover"
        onsubmit="${onSubmit}"
        data-test="toolbar-link-popover-form"
    >
        <div class="tlp-popover-arrow"></div>
        <div class="tlp-popover-header">${getPopoverHeader(host, gettext_provider)}</div>
        <div class="tlp-popover-body">
            <div class="tlp-form-element">
                <label for="toolbar-link-popover-href" class="tlp-label">
                    ${gettext_provider.gettext("Link")}
                    <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
                </label>
                <input
                    id="toolbar-link-popover-href"
                    data-test="input-href"
                    type="url"
                    class="tlp-input"
                    placeholder="https://example.com"
                    required=""
                    pattern="https?://.+"
                    value="${host.link_href}"
                    oninput="${html.set("link_href")}"
                />
            </div>
            <div class="tlp-form-element">
                <label for="toolbar-link-popover-title" class="tlp-label">
                    ${gettext_provider.gettext("Text")}
                </label>
                <input
                    id="toolbar-link-popover-title"
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
            >
                ${gettext_provider.gettext("Cancel")}
            </button>
            <button
                type="submit"
                class="tlp-button-primary tlp-button-small"
                disabled="${host.is_disabled}"
                data-test="submit-button"
            >
                ${gettext_provider.gettext("Save")}
            </button>
        </div>
    </form>
`;
