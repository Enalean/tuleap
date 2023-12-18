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
import { PullRequestStub } from "@tuleap/plugin-pullrequest-stub";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    injected_base_url,
    injection_symbols_stub,
} from "../../../../tests/injection-symbols-stub";
import { buildPullRequestOverviewUrl } from "../../../urls/base-url-builders";
import PullRequestCard from "./PullRequestCard.vue";

const pull_request_id = 2;

describe("PullRequestCard", () => {
    let pull_request: PullRequest;

    beforeEach(() => {
        pull_request = PullRequestStub.buildOpenPullRequest({ id: pull_request_id });
    });

    const getWrapper = (): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation(injection_symbols_stub);

        return shallowMount(PullRequestCard, {
            props: {
                pull_request,
            },
        });
    };

    it("Should have a href pointing to the overview page of the pull-request", () => {
        const card = getWrapper().find("[data-test=pull-request-card]").element;

        expect(card.getAttribute("href")).toContain(
            buildPullRequestOverviewUrl(injected_base_url, pull_request_id).toString(),
        );
    });

    it("should be displayed inactive when the pull-request is not open", () => {
        pull_request = PullRequestStub.buildMergedPullRequest();

        const wrapper = getWrapper();
        const card_classes = Array.from(
            wrapper.find("[data-test=pull-request-card]").element.classList,
        );

        expect(card_classes).toContain("tlp-card-inactive");
    });
});
