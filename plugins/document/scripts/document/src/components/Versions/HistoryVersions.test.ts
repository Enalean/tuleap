import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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
import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { errAsync, okAsync } from "neverthrow";
import HistoryVersions from "./HistoryVersions.vue";
import HistoryVersionsLoadingState from "./HistoryVersionsLoadingState.vue";
import HistoryVersionsErrorState from "./HistoryVersionsErrorState.vue";
import HistoryVersionsEmptyState from "./HistoryVersionsEmptyState.vue";
import HistoryVersionsContent from "./HistoryVersionsContent.vue";
import HistoryVersionsContentForLink from "./HistoryVersionsContentForLink.vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type {
    Embedded,
    EmbeddedFileVersion,
    FileHistory,
    Item,
    ItemFile,
    Link,
    LinkVersion,
} from "../../type";
import { SHOULD_DISPLAY_SOURCE_COLUMN_FOR_VERSIONS } from "../../injection-keys";
import * as VersionRestQuerier from "../../api/version-rest-querier";

vi.useFakeTimers();

describe("HistoryVersions", () => {
    let getAllFileVersionHistory: MockInstance;
    let getAllLinkVersionHistory: MockInstance;
    let getAllEmbeddedFileVersionHistory: MockInstance;

    function getWrapper(item: Item): VueWrapper {
        return shallowMount(HistoryVersions, {
            props: {
                item,
            },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [SHOULD_DISPLAY_SOURCE_COLUMN_FOR_VERSIONS.valueOf()]: false,
                },
            },
        });
    }

    beforeEach(() => {
        getAllFileVersionHistory = vi.spyOn(VersionRestQuerier, "getAllFileVersionHistory");
        getAllLinkVersionHistory = vi.spyOn(VersionRestQuerier, "getAllLinkVersionHistory");
        getAllEmbeddedFileVersionHistory = vi.spyOn(
            VersionRestQuerier,
            "getAllEmbeddedFileVersionHistory",
        );
    });

    it("should display a loading state", () => {
        getAllFileVersionHistory.mockReturnValue(okAsync([]));

        const wrapper = getWrapper({ id: 42 } as ItemFile);

        expect(wrapper.findComponent(HistoryVersionsLoadingState).exists()).toBe(true);
        expect(wrapper.findComponent(HistoryVersionsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsContent).exists()).toBe(false);
    });

    it("should display an empty state", async () => {
        getAllFileVersionHistory.mockReturnValue(okAsync([]));

        const wrapper = getWrapper({ id: 42 } as ItemFile);

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.findComponent(HistoryVersionsLoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsEmptyState).exists()).toBe(true);
        expect(wrapper.findComponent(HistoryVersionsContent).exists()).toBe(false);
    });

    it("should display an error state", async () => {
        getAllFileVersionHistory.mockReturnValue(errAsync(Error("You cannot!")));

        const wrapper = getWrapper({ id: 42 } as ItemFile);

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.findComponent(HistoryVersionsLoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsErrorState).exists()).toBe(true);
        expect(wrapper.findComponent(HistoryVersionsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsContent).exists()).toBe(false);
    });

    it("should display content", async () => {
        getAllFileVersionHistory.mockReturnValue(okAsync([{} as FileHistory]));

        const wrapper = getWrapper({ id: 42, type: "file" } as ItemFile);

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.findComponent(HistoryVersionsLoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsContent).exists()).toBe(true);
    });

    it("should display content for a Link", async () => {
        getAllLinkVersionHistory.mockReturnValue(okAsync([{} as LinkVersion]));

        const wrapper = getWrapper({ id: 42, type: "link" } as Link);

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.findComponent(HistoryVersionsLoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsContentForLink).exists()).toBe(true);
    });

    it("should display content for an embedded file", async () => {
        getAllEmbeddedFileVersionHistory.mockReturnValue(okAsync([{} as EmbeddedFileVersion]));

        const wrapper = getWrapper({ id: 42, type: "embedded" } as Embedded);

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.findComponent(HistoryVersionsLoadingState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsErrorState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsEmptyState).exists()).toBe(false);
        expect(wrapper.findComponent(HistoryVersionsContent).exists()).toBe(true);
    });
});
