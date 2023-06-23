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

import { define, html, dispatch } from "hybrids";
import type { TextAndFormat } from "../../text-and-format";
import { getTextAndFormatTemplate } from "../../text-and-format";
import { getValidFormat } from "../../RichTextEditor";

export interface TextField extends TextAndFormat {
    fieldId: number;
    content: () => HTMLElement;
}
export type HostElement = TextField & HTMLElement;

const isRequiredAndEmpty = (host: TextField): boolean => host.required && host.contentValue === "";

type MapOfClasses = Record<string, boolean>;
export const getClasses = (host: TextField): MapOfClasses => {
    return {
        "tlp-form-element": true,
        "tlp-form-element-disabled": host.disabled,
        "tlp-form-element-error": isRequiredAndEmpty(host),
    };
};

export const getIdentifier = (host: TextField): string => "tracker_field_" + host.fieldId;

const onFormatChange = (host: HostElement, event: CustomEvent): void => {
    const { format, content } = event.detail;
    host.format = format;
    host.contentValue = content;
    dispatch(host, "value-changed", {
        detail: { field_id: host.fieldId, value: { format, content } },
    });
};

const onContentChange = (host: HostElement, event: CustomEvent): void => {
    const { content } = event.detail;
    host.contentValue = content;
    dispatch(host, "value-changed", {
        detail: { field_id: host.fieldId, value: { format: host.format, content } },
    });
};

export const TextField = define<TextField>({
    tag: "tuleap-artifact-modal-text-field",
    fieldId: 0,
    label: "",
    format: { set: getValidFormat },
    contentValue: "",
    required: false,
    disabled: false,
    interpreted_commonmark: "",
    is_in_preview_mode: false,
    is_preview_loading: false,
    has_error: false,
    error_message: "",
    controller: undefined,
    content: (host) => {
        return html`
            <div class="${getClasses(host)}">
                ${getTextAndFormatTemplate(host, {
                    identifier: getIdentifier(host),
                    rows: 5,
                    onContentChange,
                    onFormatChange,
                })}
            </div>
        `;
    },
});
