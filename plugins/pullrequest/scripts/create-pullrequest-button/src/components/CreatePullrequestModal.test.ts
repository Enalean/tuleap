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

import { describe, it, expect, vi } from "vitest";
import { ref } from "vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import CreatePullrequestModal from "./CreatePullrequestModal.vue";
import {
    SOURCE_BRANCHES,
    DESTINATION_BRANCHES,
    SELECTED_SOURCE_BRANCH,
    SELECTED_DESTINATION_BRANCH,
    CREATE_ERROR_MESSAGE,
    IS_CREATING_PULLREQUEST,
    CREATE_PULLREQUEST,
} from "../injection-keys";
import type { ExtendedBranch } from "../helpers/pullrequest-helper";

function buildBranch(name: string): ExtendedBranch {
    return { name, display_name: name, repository_id: 1, project_id: 101 };
}

function createWrapper({
    display_parent_repository_warning = false,
    source_branches = [],
    destination_branches = [],
    selected_source_branch = "" as ExtendedBranch | "",
    selected_destination_branch = "" as ExtendedBranch | "",
    create_error_message = "",
    is_creating_pullrequest = false,
    create_pullrequest = vi.fn(),
}: {
    display_parent_repository_warning?: boolean;
    source_branches?: ExtendedBranch[];
    destination_branches?: ExtendedBranch[];
    selected_source_branch?: ExtendedBranch | "";
    selected_destination_branch?: ExtendedBranch | "";
    create_error_message?: string;
    is_creating_pullrequest?: boolean;
    create_pullrequest?: () => Promise<void>;
} = {}): VueWrapper {
    return shallowMount(CreatePullrequestModal, {
        props: { displayParentRepositoryWarning: display_parent_repository_warning },
        global: {
            plugins: [createGettext({ silent: true })],
            provide: {
                [SOURCE_BRANCHES.valueOf()]: ref(source_branches),
                [DESTINATION_BRANCHES.valueOf()]: ref(destination_branches),
                [SELECTED_SOURCE_BRANCH.valueOf()]: ref(selected_source_branch),
                [SELECTED_DESTINATION_BRANCH.valueOf()]: ref(selected_destination_branch),
                [CREATE_ERROR_MESSAGE.valueOf()]: ref(create_error_message),
                [IS_CREATING_PULLREQUEST.valueOf()]: ref(is_creating_pullrequest),
                [CREATE_PULLREQUEST.valueOf()]: create_pullrequest,
            },
        },
    });
}

describe("CreatePullrequestModal", () => {
    it("is disabled when pull request creation is in progress", () => {
        const wrapper = createWrapper({ is_creating_pullrequest: true });

        expect(
            wrapper.find("[data-test='pull-request-create-button']").attributes("disabled"),
        ).toBeDefined();
    });

    it("is disabled when no source branch is selected", () => {
        const wrapper = createWrapper({
            selected_destination_branch: buildBranch("main"),
        });

        expect(
            wrapper.find("[data-test='pull-request-create-button']").attributes("disabled"),
        ).toBeDefined();
    });

    it("is disabled when no destination branch is selected", () => {
        const wrapper = createWrapper({
            selected_source_branch: buildBranch("feature"),
        });

        expect(
            wrapper.find("[data-test='pull-request-create-button']").attributes("disabled"),
        ).toBeDefined();
    });

    it("is disabled when source and destination are the same branch", () => {
        const branch = buildBranch("main");
        const wrapper = createWrapper({
            selected_source_branch: branch,
            selected_destination_branch: branch,
        });

        expect(
            wrapper.find("[data-test='pull-request-create-button']").attributes("disabled"),
        ).toBeDefined();
    });

    it("is enabled when different source and destination branches are selected", () => {
        const wrapper = createWrapper({
            selected_source_branch: buildBranch("feature"),
            selected_destination_branch: buildBranch("main"),
        });

        expect(
            wrapper.find("[data-test='pull-request-create-button']").attributes("disabled"),
        ).toBeUndefined();
    });

    it("shows a branch icon when not creating", () => {
        const wrapper = createWrapper({ is_creating_pullrequest: false });

        const icon = wrapper.find("[data-test='pull-request-create-button'] i");
        expect(icon.classes()).toContain("fa-code-branch");
        expect(icon.classes()).not.toContain("fa-circle-notch");
    });

    it("shows a spinner icon when creating is in progress", () => {
        const wrapper = createWrapper({ is_creating_pullrequest: true });

        const icon = wrapper.find("[data-test='pull-request-create-button'] i");
        expect(icon.classes()).toContain("fa-circle-notch");
        expect(icon.classes()).not.toContain("fa-code-branch");
    });

    it("displays a warning when displayParentRepositoryWarning is true", () => {
        const wrapper = createWrapper({ display_parent_repository_warning: true });

        expect(wrapper.find(".tlp-modal-feedback").exists()).toBe(true);
    });

    it("does not display a warning when displayParentRepositoryWarning is false", () => {
        const wrapper = createWrapper({ display_parent_repository_warning: false });

        expect(wrapper.find(".tlp-modal-feedback").exists()).toBe(false);
    });

    it("displays an error message when create_error_message is set", () => {
        const wrapper = createWrapper({ create_error_message: "Something went wrong" });

        expect(wrapper.find(".tlp-alert-danger").exists()).toBe(true);
    });

    it("does not display an error message when create_error_message is empty", () => {
        const wrapper = createWrapper({ create_error_message: "" });

        expect(wrapper.find(".tlp-alert-danger").exists()).toBe(false);
    });

    it("calls create_pullrequest when the create button is clicked", async () => {
        const create_pullrequest = vi.fn().mockResolvedValue(undefined);
        const wrapper = createWrapper({
            selected_source_branch: buildBranch("feature"),
            selected_destination_branch: buildBranch("main"),
            create_pullrequest,
        });

        await wrapper.find("[data-test='pull-request-create-button']").trigger("click");

        expect(create_pullrequest).toHaveBeenCalledOnce();
    });
});
