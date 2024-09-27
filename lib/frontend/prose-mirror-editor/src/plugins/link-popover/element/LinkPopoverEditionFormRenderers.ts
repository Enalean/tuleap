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
import type { InternalLinkPopoverElement } from "./LinkPopoverElement";

import "./forms/EditLinkFormElement";
import "./forms/EditCrossReferenceFormElement";

import { sanitizeURL } from "@tuleap/url-sanitizer";
import type { LinkProperties } from "../../../types/internal-types";
import type { EditCrossReferenceCallback } from "./forms/EditCrossReferenceFormElement";

export type EditLinkCallback = (link: LinkProperties) => void; // TODO: move in the component

export type RenderEditionForm = {
    render(host: InternalLinkPopoverElement): UpdateFunction<InternalLinkPopoverElement>;
};

const onClickCancel = (host: InternalLinkPopoverElement): void => {
    host.is_in_edition_mode = false;
};

export const RegularLinkEditionPopoverRenderer = (
    gettext_provider: GetText,
    link: LinkProperties,
    edit_link_callback: EditLinkCallback,
): RenderEditionForm => ({
    render: (host: InternalLinkPopoverElement) => html`
        <edit-link-form
            gettext_provider="${gettext_provider}"
            edit_link_callback="${edit_link_callback}"
            cancel_callback="${(): void => {
                onClickCancel(host);
            }}"
            link_href="${sanitizeURL(link.href)}"
            link_title="${link.title}"
            popover_anchor="${host.popover_anchor}"
        ></edit-link-form>
    `,
});

export const CrossReferenceLinkEditionPopoverRenderer = (
    gettext_provider: GetText,
    link: LinkProperties,
    edit_cross_reference_callback: EditCrossReferenceCallback,
): RenderEditionForm => ({
    render: (host: InternalLinkPopoverElement): UpdateFunction<InternalLinkPopoverElement> => html`
        <edit-cross-reference-form
            gettext_provider="${gettext_provider}"
            edit_cross_reference_callback="${edit_cross_reference_callback}"
            cancel_callback="${(): void => {
                onClickCancel(host);
            }}"
            reference_text="${link.title}"
            popover_anchor="${host.popover_anchor}"
        ></edit-cross-reference-form>
    `,
});
