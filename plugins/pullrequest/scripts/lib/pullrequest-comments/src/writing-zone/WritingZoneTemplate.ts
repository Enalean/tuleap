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
import { buildPreviewTab, buildWriteTab } from "./WritingZoneTabsTemplate";

const displayWritingMode = (host: InternalWritingZone): UpdateFunction<InternalWritingZone> => {
    if (!host.presenter.is_in_writing_mode) {
        return html``;
    }

    return html`${host.textarea}`;
};

const displayPreviewMode = (host: InternalWritingZone): UpdateFunction<InternalWritingZone> => {
    if (!host.presenter.is_comments_markdown_mode_enabled || !host.presenter.is_in_preview_mode) {
        return html``;
    }

    return html`
        <div
            class="pull-request-comment-writing-zone-commonmark-preview"
            data-test="writing-zone-preview"
        >
            <div class="tlp-alert-info">This feature is under implementation.</div>
        </div>
    `;
};

export const getWritingZoneTemplate = (
    host: InternalWritingZone,
    gettext_provider: GettextProvider
): UpdateFunction<InternalWritingZone> => {
    return html`
        <div class="pull-request-comment-write-mode-header">
            <div class="tlp-tabs pull-request-comment-write-mode-header-tabs">
                ${buildWriteTab(host, gettext_provider)} ${buildPreviewTab(host, gettext_provider)}
            </div>
        </div>
        ${displayWritingMode(host)} ${displayPreviewMode(host)}
    `;
};
