/**
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
const deleteVersion = jest.fn();
jest.mock("../../api/version-rest-querier", () => {
    return {
        deleteVersion,
    };
});

import { okAsync } from "neverthrow";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../helpers/local-vue";
import HistoryVersionsContentRow from "./HistoryVersionsContentRow.vue";
import type { RestUser } from "../../api/rest-querier";
import type { FileHistory, User } from "../../type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { ConfigurationState } from "../../store/configuration";

describe("HistoryVersionsContentRow", () => {
    let location: Pick<Location, "reload">;

    function getWrapper(
        item: User,
        has_more_than_one_version: boolean
    ): Wrapper<HistoryVersionsContentRow> {
        return shallowMount(HistoryVersionsContentRow, {
            localVue,
            propsData: {
                item,
                has_more_than_one_version,
                version: {
                    number: 1,
                    name: "Plop",
                    changelog: "The changelog",
                    filename: "duck.png",
                    download_href: "/path/to/dl",
                    approval_href: "/path/to/table",
                    date: "2021-10-06",
                    author: { id: 102 } as unknown as RestUser,
                } as FileHistory,
                location,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            date_time_format: "Y-m-d H:i",
                            relative_dates_display: "relative_first-absolute_shown",
                            user_locale: "en_US",
                        } as unknown as ConfigurationState,
                    },
                }),
            },
            stubs: {
                "tlp-relative-date": true,
            },
        });
    }

    beforeEach(() => {
        location = { reload: jest.fn() };
    });

    it("should display a link to the approval table", () => {
        const wrapper = getWrapper({ user_can_delete: true } as unknown as User, true);

        expect(wrapper.find("[data-test=approval-link]").exists()).toBe(true);
    });

    it("should not display a delete button if user cannot delete", () => {
        const wrapper = getWrapper({ user_can_delete: false } as unknown as User, true);

        expect(wrapper.find("[data-test=delete-button]").exists()).toBe(false);
    });

    it("should display a disabled button if user can delete but there is only one version", () => {
        const wrapper = getWrapper({ user_can_delete: true } as unknown as User, false);

        const button = wrapper.find("[data-test=delete-button]").element;
        if (!(button instanceof HTMLButtonElement)) {
            throw Error("Unable to find button");
        }

        expect(button.disabled).toBe(true);
    });

    it("should display a delete button if user can delete and there is more than one version", () => {
        const wrapper = getWrapper({ user_can_delete: true } as unknown as User, true);

        const button = wrapper.find("[data-test=delete-button]").element;
        if (!(button instanceof HTMLButtonElement)) {
            throw Error("Unable to find button");
        }

        expect(button.disabled).toBe(false);
    });

    it("should delete the version if user confirm the deletion and refresh the page to display latest data", async () => {
        deleteVersion.mockReturnValue(okAsync(null));

        const wrapper = getWrapper({ user_can_delete: true } as unknown as User, true);

        await wrapper.find("[data-test=confirm-button]").trigger("click");

        expect(deleteVersion).toHaveBeenCalled();
        expect(location.reload).toHaveBeenCalled();
    });
});
