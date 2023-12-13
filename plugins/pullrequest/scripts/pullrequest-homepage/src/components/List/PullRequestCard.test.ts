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
import * as strict_inject from "@tuleap/vue-strict-inject";
import * as tooltip from "@tuleap/tooltip";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import type { PullRequestStatusType } from "@tuleap/plugin-pullrequest-constants";
import {
    PULL_REQUEST_STATUS_REVIEW,
    PULL_REQUEST_STATUS_MERGED,
} from "@tuleap/plugin-pullrequest-constants";
import { injected_base_url, injection_symbols_stub } from "../../../tests/injection-symbols-stub";
import PullRequestCard from "./PullRequestCard.vue";
import { buildPullRequestOverviewUrl } from "../../urls/base-url-builders";
import { buildVueDompurifyHTMLDirective } from "vue-dompurify-html";

const pull_request_id = 2;
const pull_request_title = "Please pull my request";

describe("PullRequestCard", () => {
    let pull_request_status: PullRequestStatusType;

    beforeEach(() => {
        pull_request_status = PULL_REQUEST_STATUS_REVIEW;
    });

    const getWrapper = (): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation(injection_symbols_stub);

        return shallowMount(PullRequestCard, {
            global: {
                directives: {
                    "dompurify-html": buildVueDompurifyHTMLDirective(),
                },
            },
            props: {
                pull_request: {
                    id: pull_request_id,
                    title: pull_request_title,
                    status: pull_request_status,
                } as PullRequest,
            },
        });
    };

    it("Should have a href pointing to the overview page of the pull-request", () => {
        const card = getWrapper().find("[data-test=pull-request-card]").element;

        expect(card.getAttribute("href")).toContain(
            buildPullRequestOverviewUrl(injected_base_url, pull_request_id).toString(),
        );
    });

    it("should display the title of the pull-request", () => {
        const card_title = getWrapper().find("[data-test=pull-request-card-title]").element;

        expect(card_title.textContent).toBe(pull_request_title);
    });

    it("should be displayed inactive when the pull-request is not open", () => {
        pull_request_status = PULL_REQUEST_STATUS_MERGED;

        const wrapper = getWrapper();
        const card_classes = Array.from(
            wrapper.find("[data-test=pull-request-card]").element.classList,
        );
        const card_title_classes = Array.from(
            wrapper.find("[data-test=pull-request-card-title]").element.classList,
        );

        expect(card_classes).toContain("tlp-card-inactive");
        expect(card_title_classes).toContain("tlp-text-muted");
    });

    it("Should load the tooltips inside the card's title", () => {
        const loadTooltips = vi.spyOn(tooltip, "loadTooltips");
        const card_title = getWrapper().find("[data-test=pull-request-card-title]").element;

        expect(loadTooltips).toHaveBeenCalledOnce();
        expect(loadTooltips).toHaveBeenCalledWith(card_title);
    });
});
