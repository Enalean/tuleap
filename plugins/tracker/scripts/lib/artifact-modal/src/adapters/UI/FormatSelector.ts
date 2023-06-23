/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import { getCommonMarkLabel, getHTMLLabel, getTextLabel } from "../../gettext-catalog";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "@tuleap/plugin-tracker-constants";
import "./CommonmarkSyntaxHelper";
import "./CommonmarkPreviewButton";

export interface FormatSelector {
    identifier: string;
    label: string;
    value: string;
    disabled: boolean;
    required: boolean;
    isInPreviewMode: boolean;
    isPreviewLoading: boolean;
    content: () => HTMLElement;
}

const isFormatSelectboxDisabled = (host: FormatSelector): boolean =>
    host.disabled || host.isInPreviewMode || host.isPreviewLoading;

const isCommonmarkFormat = (host: FormatSelector): boolean => host.value === TEXT_FORMAT_COMMONMARK;

export const isSyntaxHelperDisabled = (host: FormatSelector): boolean =>
    host.isInPreviewMode || host.isPreviewLoading;

const onPreview = (host: HTMLElement): void => {
    dispatch(host, "interpret-content-event");
};

const isSelected = (host: FormatSelector, value: TextFieldFormat): boolean => host.value === value;

export const FormatSelector = define<FormatSelector>({
    tag: "tuleap-artifact-modal-format-selector",
    identifier: "",
    label: "",
    value: "",
    disabled: false,
    required: false,
    isInPreviewMode: false,
    isPreviewLoading: false,
    content: (host) => html`
        <div class="artifact-modal-text-label-with-format">
            <label for="${host.identifier}" class="tlp-label artifact-modal-text-label">
                ${host.label}
                ${host.required &&
                html`<i class="fas fa-asterisk artifact-modal-text-asterisk"></i>`}
            </label>
            <div class="artifact-modal-text-label-with-format-and-helper-container">
                <select
                    id="${"format_" + host.identifier}"
                    oninput="${html.set("value")}"
                    disabled="${isFormatSelectboxDisabled(host)}"
                    class="tlp-select tlp-select-small tlp-select-adjusted"
                    value="${host.value}"
                    data-test="format"
                >
                    <option
                        value="${TEXT_FORMAT_TEXT}"
                        selected="${isSelected(host, TEXT_FORMAT_TEXT)}"
                    >
                        ${getTextLabel()}
                    </option>
                    <option
                        value="${TEXT_FORMAT_HTML}"
                        selected="${isSelected(host, TEXT_FORMAT_HTML)}"
                    >
                        ${getHTMLLabel()}
                    </option>
                    <option
                        value="${TEXT_FORMAT_COMMONMARK}"
                        selected="${isSelected(host, TEXT_FORMAT_COMMONMARK)}"
                    >
                        ${getCommonMarkLabel()}
                    </option>
                </select>
                ${isCommonmarkFormat(host) &&
                html`
                    <tuleap-artifact-modal-commonmark-preview
                        isInPreviewMode="${host.isInPreviewMode}"
                        isPreviewLoading="${host.isPreviewLoading}"
                        oncommonmark-preview-event="${onPreview}"
                        data-test="preview-button"
                    ></tuleap-artifact-modal-commonmark-preview>
                    <tuleap-artifact-modal-commonmark-syntax-helper
                        disabled="${isSyntaxHelperDisabled(host)}"
                        data-test="syntax-button"
                    ></tuleap-artifact-modal-commonmark-syntax-helper>
                `}
            </div>
        </div>
    `,
});
