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

import { describe, it, expect, vi } from "vitest";
import { okAsync } from "neverthrow";
import { Option } from "@tuleap/option";
import { selectOrThrow } from "@tuleap/dom";
import { uri } from "@tuleap/fetch-result";
import * as tooltip from "@tuleap/tooltip";
import * as fetch_result from "@tuleap/fetch-result";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    type PullRequestTitle,
    type HostElement,
    renderPullRequestTitle,
    TAG,
} from "./PullRequestTitle";

vi.mock("@tuleap/tooltip", () => ({
    loadTooltips: (): void => {
        // do nothing
    },
}));

const pull_request_id = 12;
const isTuleapPullRequestTitle = (
    pull_request_title_element: HTMLElement,
): pull_request_title_element is HTMLElement & PullRequestTitle =>
    pull_request_title_element.tagName.toLowerCase() === TAG;

describe("PullRequestTitle", () => {
    it("Given a pull-request id, When the component is added to the DOM tree, Then it should fetch the pull-request title and load the tooltips", async () => {
        vi.useFakeTimers();

        const pull_request = { title: "Pull-request title" } as PullRequest;
        const getJSON = vi.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(pull_request));
        const loadTooltips = vi.spyOn(tooltip, "loadTooltips").mockImplementation(() => {
            // do nothing
        });

        const pull_request_title_element = document.createElement(TAG);
        if (!isTuleapPullRequestTitle(pull_request_title_element)) {
            throw new Error(`Failed to build a <${TAG}/> element.`);
        }
        pull_request_title_element.pull_request_id = pull_request_id;

        const doc = document.implementation.createHTMLDocument();
        doc.body.append(pull_request_title_element);

        await vi.runOnlyPendingTimersAsync();

        expect(getJSON).toHaveBeenCalledWith(uri`/api/v1/pull_requests/${pull_request_id}`);
        expect(loadTooltips).toHaveBeenCalledTimes(1);
        expect(selectOrThrow(doc.body, "[data-test=pullrequest-title]").textContent.trim()).toBe(
            pull_request.title,
        );
    });

    it("When the title is loading, Then it should display a skeleton", () => {
        const target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;

        const host = { pull_request_id, pull_request_title: Option.nothing() } as HostElement;
        const update = renderPullRequestTitle(host);

        update(host, target);

        expect(selectOrThrow(target, "[data-test=pullrequest-title-skeleton]")).toBeDefined();
    });
});
