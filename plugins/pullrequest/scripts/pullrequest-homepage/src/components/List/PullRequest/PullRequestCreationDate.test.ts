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
import type { PullRequest, User } from "@tuleap/plugin-pullrequest-rest-api-types";
import {
    injected_user_locale,
    injection_symbols_stub,
} from "../../../../tests/injection-symbols-stub";
import { getGlobalTestOptions } from "../../../../tests/global-options-for-tests";
import PullRequestCreationDate from "./PullRequestCreationDate.vue";

describe("PullRequestCreationDate", () => {
    let pull_request: PullRequest, creation_date: string, creator: User;

    const getWrapper = (): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation(injection_symbols_stub);

        return shallowMount(PullRequestCreationDate, {
            global: {
                ...getGlobalTestOptions(),
                stubs: {
                    tlpRelativeDate: true,
                },
            },
            props: {
                pull_request,
            },
        });
    };

    beforeEach(() => {
        creation_date = "2023-12-18T12:30:00Z";
        creator = {
            id: 102,
            display_name: "Joe l'asticot (jolasti)",
            avatar_url: "/url/to/jolasti.png",
            user_url: "/url/to/jolasti_profile.html",
        };
        pull_request = PullRequestStub.buildOpenPullRequest({
            creation_date,
            creator,
        });
    });

    it("should display the pull-request creation date in a tlp-relative-date element", () => {
        const relative_date_component = getWrapper().find("[data-test=pull-request-creation-date]");

        expect(relative_date_component.attributes("date")).toBe(creation_date);
        expect(relative_date_component.attributes("placement")).toBe("right");
        expect(relative_date_component.attributes("preference")).toBe("relative");
        expect(relative_date_component.attributes("locale")).toBe(injected_user_locale);
    });

    it("should display the pull-request's creator avatar and name", () => {
        const wrapper = getWrapper();
        const creator_avatar = wrapper.find("[data-test=pull-request-creator-avatar-img]");
        const creator_link = wrapper.find("[data-test=pull-request-creator-link]");

        expect(creator_avatar.attributes().src).toBe(creator.avatar_url);
        expect(creator_link.attributes().href).toBe(creator.user_url);
        expect(creator_link.text().trim()).toBe(creator.display_name);
    });
});
