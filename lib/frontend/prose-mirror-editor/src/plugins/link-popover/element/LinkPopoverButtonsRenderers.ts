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
import { sanitizeURL } from "@tuleap/url-sanitizer";
import type { RemoveLinkCallback } from "./items/RemoveLinkButtonElement";

import "./items/OpenLinkButtonElement";
import "./items/CopyToClipboardButtonElement";
import "./items/RemoveLinkButtonElement";

export type RenderButtons = {
    render(): UpdateFunction<HTMLElement>;
};

export const RegularLinkPopoverButtonsRenderer = (
    gettext_provider: GetText,
    link_href: string,
    remove_link_callback: RemoveLinkCallback,
): RenderButtons => ({
    render: () => html`
        <open-link-button
            data-test="open-link-button"
            gettext_provider="${gettext_provider}"
            sanitized_link_href="${sanitizeURL(link_href)}"
        ></open-link-button>
        <copy-to-clipboard-button
            data-test="copy-to-clipboard-button"
            value_to_copy="${sanitizeURL(link_href)}"
            copy_value_title="${gettext_provider.gettext("Copy link url")}"
            value_copied_title="${gettext_provider.gettext("Link url has been copied!")}"
        ></copy-to-clipboard-button>
        <remove-link-button
            data-test="remove-link-button"
            gettext_provider="${gettext_provider}"
            remove_link_callback="${remove_link_callback}"
        ></remove-link-button>
    `,
});

export const CrossReferenceLinkPopoverButtonsRenderer = (
    gettext_provider: GetText,
    link_href: string,
    cross_reference_text: string,
): RenderButtons => ({
    render: () => html`
        <open-link-button
            data-test="open-link-button"
            gettext_provider="${gettext_provider}"
            sanitized_link_href="${sanitizeURL(link_href)}"
        ></open-link-button>
        <copy-to-clipboard-button
            data-test="copy-to-clipboard-button"
            value_to_copy="${cross_reference_text}"
            copy_value_title="${gettext_provider.gettext("Copy Tuleap reference")}"
            value_copied_title="${gettext_provider.gettext("Tuleap reference has been copied!")}"
        ></copy-to-clipboard-button>
    `,
});
