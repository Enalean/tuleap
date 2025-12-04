/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
import DOMPurify from "dompurify";
import { Option } from "@tuleap/option";
import { loadTooltips } from "@tuleap/tooltip";
import { getJSON, uri } from "@tuleap/fetch-result";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";

export const TAG = "tuleap-pull-request-title";

export type PullRequestTitle = {
    pull_request_id: number;
};

export type InternalPullRequestTitle = Readonly<PullRequestTitle> & {
    pull_request_title: Option<string>;
    render(): HostElement;
};

export type HostElement = InternalPullRequestTitle & HTMLElement;

export const renderPullRequestTitle = (
    host: InternalPullRequestTitle,
): UpdateFunction<InternalPullRequestTitle> =>
    html`
        <div class="tlp-pane-header pull-request-header">
            ${host.pull_request_title.mapOr(
                (title) => html`
                    <h2
                        data-test="pullrequest-title"
                        innerHTML="${DOMPurify.sanitize(title, {
                            ALLOWED_TAGS: ["a"],
                        })}"
                    ></h2>
                `,
                html`
                    <h2>
                        <span
                            class="tlp-skeleton-text"
                            data-test="pullrequest-title-skeleton"
                        ></span>
                    </h2>
                `,
            )}
        </div>
    `.css`
        .pull-request-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--tlp-medium-spacing) var(--tlp-medium-spacing) var(--tlp-small-spacing);

            > h2 {
                flex: 0 0 auto;
                margin: 0;
                font-size: 20px;
            }
        }
    `;

define<InternalPullRequestTitle>({
    tag: TAG,
    pull_request_title: {
        value: Option.nothing<string>(),
    },
    pull_request_id: 0,
    render: {
        value: renderPullRequestTitle,
        connect(host): () => void {
            getJSON<PullRequest>(uri`/api/v1/pull_requests/${host.pull_request_id}`).map(
                (pull_request): null => {
                    host.pull_request_title = Option.fromValue(pull_request.title);
                    loadTooltips(host.render());
                    return null;
                },
            );

            return (): void => {};
        },
        shadow: false,
    },
});
