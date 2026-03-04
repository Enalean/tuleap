/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

import { beforeEach, describe, it, expect, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import App from "./App.vue";
import * as tlp_modal from "@tuleap/tlp-modal";
import * as rest_querier from "../api/rest-querier";
import type { Modal } from "@tuleap/tlp-modal";

vi.mock("@tuleap/tlp-modal");

let parent_repository_id = NaN;
let user_can_see_parent_repository = false;

describe("App", () => {
    beforeEach(() => {
        parent_repository_id = NaN;
        user_can_see_parent_repository = false;
        vi.spyOn(rest_querier, "getBranches").mockResolvedValue([]);
    });

    function createWrapper(): VueWrapper {
        vi.mocked(tlp_modal.createModal)
            .mockReturnValueOnce({ toggle: vi.fn(), addEventListener: vi.fn() } as unknown as Modal)
            .mockReturnValueOnce({
                toggle: vi.fn(),
                addEventListener: vi.fn(),
            } as unknown as Modal);

        const wrapper = shallowMount(App, {
            props: {
                repository_id: 1,
                project_id: 101,
                parent_repository_id,
                parent_repository_name: "",
                parent_project_id: 0,
                user_can_see_parent_repository,
            },
            global: { plugins: [createGettext({ silent: true })] },
        });

        return wrapper;
    }

    describe("display_parent_repository_warning", () => {
        it("user has no warning when repository does not have parent", () => {
            const wrapper = createWrapper();

            expect(
                wrapper
                    .findComponent({ name: "CreatePullrequestModal" })
                    .props("displayParentRepositoryWarning"),
            ).toBe(false);
        });

        it("user has no warning when he can see parent repository", () => {
            parent_repository_id = 2;
            user_can_see_parent_repository = true;
            const wrapper = createWrapper();

            expect(
                wrapper
                    .findComponent({ name: "CreatePullrequestModal" })
                    .props("displayParentRepositoryWarning"),
            ).toBe(false);
        });

        it("user has a warning displayed when he can't see parent repository", () => {
            parent_repository_id = 2;
            user_can_see_parent_repository = false;
            const wrapper = createWrapper();

            expect(
                wrapper
                    .findComponent({ name: "CreatePullrequestModal" })
                    .props("displayParentRepositoryWarning"),
            ).toBe(true);
        });
    });
});
