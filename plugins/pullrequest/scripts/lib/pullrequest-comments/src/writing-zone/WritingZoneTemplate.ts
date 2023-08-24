/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
import type { GettextProvider } from "@tuleap/gettext";
import type { InternalWritingZone } from "./WritingZone";
import { buildWriteTab } from "./WritingZoneTabsTemplate";

export const TEXTAREA_CLASSNAME = "pull-request-comment-textarea";

export const getWritingZoneTemplate = (
    host: InternalWritingZone,
    gettext_provider: GettextProvider
): UpdateFunction<InternalWritingZone> => {
    return html`
        <div class="pull-request-comment-write-mode-header">
            <div class="tlp-tabs pull-request-comment-write-mode-header-tabs">
                ${buildWriteTab(host, gettext_provider)}
            </div>
        </div>
        <textarea
            data-test="writing-zone-textarea"
            class="${TEXTAREA_CLASSNAME} tlp-textarea"
            rows="10"
            placeholder="${gettext_provider.gettext("Say something…")}"
            oninput="${host.controller.onTextareaInput}"
            onfocus="${host.controller.focusTextArea}"
            onblur="${host.controller.blurTextArea}"
        ></textarea>
    `;
};
