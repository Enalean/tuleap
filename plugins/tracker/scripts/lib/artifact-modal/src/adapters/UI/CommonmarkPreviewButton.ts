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

import { define, dispatch, html } from "hybrids";
import { getEditButtonLabel, getPreviewButtonLabel } from "../../gettext-catalog";

export interface CommonmarkPreviewButton {
    isInPreviewMode: boolean;
    isPreviewLoading: boolean;
    content: () => HTMLElement;
}

export const iconClasses = (host: CommonmarkPreviewButton): string[] => {
    if (host.isPreviewLoading) {
        return ["fas", "tlp-button-icon", "fa-circle-notch", "fa-spin"];
    }
    if (host.isInPreviewMode) {
        return ["fas", "tlp-button-icon", "fa-pencil-alt"];
    }
    return ["fas", "tlp-button-icon", "fa-eye"];
};

export const buttonLabel = (host: CommonmarkPreviewButton): string => {
    if (host.isInPreviewMode) {
        return getEditButtonLabel();
    }
    return getPreviewButtonLabel();
};

export const onClick = (host: HTMLElement): void => {
    dispatch(host, "commonmark-preview-event");
};

export const CommonmarkPreviewButton = define<CommonmarkPreviewButton>({
    tag: "tuleap-artifact-modal-commonmark-preview",
    isInPreviewMode: false,
    isPreviewLoading: false,
    content: (host) => html`
        <button
            class="tlp-button-secondary tlp-button-small artifact-modal-preview-button"
            type="button"
            onclick="${onClick}"
            disabled="${host.isPreviewLoading}"
            data-test="button-commonmark-preview"
        >
            <i
                class="${iconClasses(host)}"
                data-test="button-commonmark-preview-icon"
                aria-hidden="true"
            ></i>
            ${buttonLabel(host)}
        </button>
    `,
});
