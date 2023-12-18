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

import { describe, it, expect, beforeEach, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { buildVueDompurifyHTMLDirective } from "vue-dompurify-html";
import * as tooltip from "@tuleap/tooltip";
import { PullRequestStub } from "@tuleap/plugin-pullrequest-stub";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import PullRequestSummary from "./PullRequestSummary.vue";

describe("PullRequestSummary", () => {
    let pull_request: PullRequest;

    beforeEach(() => {
        pull_request = PullRequestStub.buildOpenPullRequest();
    });

    const getWrapper = (): VueWrapper => {
        return shallowMount(PullRequestSummary, {
            global: {
                directives: {
                    "dompurify-html": buildVueDompurifyHTMLDirective(),
                },
            },
            props: {
                pull_request,
            },
        });
    };

    it("should display the title of the pull-request", () => {
        const title = "My beloved pull-request";
        pull_request = PullRequestStub.buildOpenPullRequest({ title });

        const card_title = getWrapper().find("[data-test=pull-request-card-title]").element;

        expect(card_title.textContent).toBe(title);
    });

    it("When the pull-request is closed, then its title should have the tlp-text-muted class", () => {
        pull_request = PullRequestStub.buildMergedPullRequest();

        const wrapper = getWrapper();
        const card_title_classes = Array.from(
            wrapper.find("[data-test=pull-request-card-title]").element.classList,
        );

        expect(card_title_classes).toContain("tlp-text-muted");
    });

    it("Should load the tooltips inside the card's title", () => {
        const loadTooltips = vi.spyOn(tooltip, "loadTooltips");
        const card_title = getWrapper().find("[data-test=pull-request-card-title]").element;

        expect(loadTooltips).toHaveBeenCalledOnce();
        expect(loadTooltips).toHaveBeenCalledWith(card_title);
    });
});
