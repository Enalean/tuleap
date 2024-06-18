/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { define, html } from "hybrids";
import type { UpdateFunction } from "hybrids";

export const TAG_NAME = "tuleap-pullrequest-placeholder";

export type HostElement = FileDiffPlaceholder & HTMLElement;

export interface FileDiffPlaceholder {
    readonly render: () => HTMLElement;
    readonly isReplacingAComment: boolean;
    post_rendering_callback: () => void;
    height: number;
}

const getPlaceholderClasses = (host: FileDiffPlaceholder): Record<string, boolean> => ({
    "pull-request-file-diff-placeholder-block": true,
    "pull-request-file-diff-comment-placeholder-block": host.isReplacingAComment,
});

const getStyle = (host: FileDiffPlaceholder): Record<string, string> => ({
    height: host.height + "px",
});

export const renderPlaceholder = (
    host: FileDiffPlaceholder,
): UpdateFunction<FileDiffPlaceholder> => {
    return html`
        <div
            class="${getPlaceholderClasses(host)}"
            style="${getStyle(host)}"
            data-test="pullrequest-file-diff-placeholder"
        ></div>
    `;
};

export const FileDiffPlaceholder = define<FileDiffPlaceholder>({
    tag: TAG_NAME,
    isReplacingAComment: false,
    height: {
        value: 0,
        observe(host: FileDiffPlaceholder) {
            host.post_rendering_callback();
        },
    },
    post_rendering_callback: (host, callback) => callback,
    render: renderPlaceholder,
});
