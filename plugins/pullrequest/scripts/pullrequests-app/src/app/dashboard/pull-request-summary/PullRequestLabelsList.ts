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

import { define, html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import { uri, getAllJSON } from "@tuleap/fetch-result";
import type { ProjectLabelsCollection, ProjectLabel } from "@tuleap/core-rest-api-types";

const TAG = "tuleap-pull-request-labels-list";

type PullRequestLabelsList = {
    readonly pullRequestId: number;
};

type InternalPullRequestLabelsList = Readonly<PullRequestLabelsList> & {
    content(): HTMLElement;
    labels: ProjectLabel[];
    readonly after_render_once: unknown;
};

export type HostElement = InternalPullRequestLabelsList & HTMLElement;

export const after_render_once_descriptor = {
    get: (host: InternalPullRequestLabelsList): unknown => host.content(),
    observe: (host: HostElement): void => {
        getAllJSON(uri`/api/v1/pull_requests/${host.pullRequestId}/labels`, {
            params: {
                limit: 50,
            },
            getCollectionCallback: (payload: ProjectLabelsCollection) => {
                return payload.labels;
            },
        }).match(
            (labels) => {
                host.labels = [...labels];
            },
            () => {
                // Do nothing
            },
        );
    },
};

const displayLabelsList = (label: ProjectLabel): UpdateFunction<InternalPullRequestLabelsList> => {
    const badge_classes = {
        [`tlp-badge-${label.color}`]: true,
        "tlp-badge-outline": label.is_outline,
    };

    return html`
        <span class="${badge_classes}" data-test="pull-request-label">
            <i class="fa-solid fa-tag tlp-badge-icon" aria-hidden="true"></i>
            ${label.label}
        </span>
    `;
};

export const PullRequestLabelsList = define<InternalPullRequestLabelsList>({
    tag: TAG,
    after_render_once: after_render_once_descriptor,
    labels: {
        set: (host, value) => value ?? [],
    },
    pullRequestId: 0,
    content: (host) => html`${host.labels.map(displayLabelsList)}`,
});
