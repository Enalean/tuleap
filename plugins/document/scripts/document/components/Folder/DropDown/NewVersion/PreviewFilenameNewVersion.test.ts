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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { ConfigurationState } from "../../../../store/configuration";
import type { DefaultFileNewVersionItem, NewVersion } from "../../../../type";
import PreviewFilenameNewVersion from "./PreviewFilenameNewVersion.vue";
import PreviewFilenameProperty from "../../ModalCommon/PreviewFilenameProperty.vue";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import { TYPE_FILE } from "../../../../constants";

describe("PreviewFilenameNewVersion", () => {
    function getWrapper(
        item: DefaultFileNewVersionItem,
        version: NewVersion,
        filename_pattern: string,
    ): VueWrapper<InstanceType<typeof PreviewFilenameNewVersion>> {
        return shallowMount(PreviewFilenameNewVersion, {
            props: {
                item,
                version,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: { filename_pattern } as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    it("should display the preview according to item and version's values", () => {
        const item = {
            id: 42,
            type: TYPE_FILE,
            title: "Lorem ipsum",
            status: "approved",
            description: "",
            file_properties: {
                file: new File([], "values.json"),
            },
        } as DefaultFileNewVersionItem;

        const version = { title: "wololo", changelog: "" } as NewVersion;

        const wrapper = getWrapper(
            item,
            version,
            // eslint-disable-next-line no-template-curly-in-string
            "${ID}-toto-${TITLE}-${STATUS}-${VERSION_NAME}",
        );

        expect(wrapper.vm.preview).toBe("42-toto-Lorem ipsum-approved-wololo.json");
    });
    it("does not display the filename preview if the current item is not a file", () => {
        const item = {
            id: 42,
            type: "link",
            title: "Lorem ipsum",
            status: "approved",
            description: "",
        } as unknown as DefaultFileNewVersionItem;

        const wrapper = getWrapper(
            item,
            {} as NewVersion,
            // eslint-disable-next-line no-template-curly-in-string
            "${ID}-toto-${TITLE}-${STATUS}-${VERSION_NAME}",
        );
        expect(wrapper.findComponent(PreviewFilenameProperty).exists()).toBe(false);
    });

    it("displays the filename preview if the current item is a file", () => {
        const item = {
            id: 42,
            type: TYPE_FILE,
            title: "Lorem ipsum",
            status: "approved",
            description: "",
            file_properties: {
                file: new File([], "values.json"),
            },
        } as DefaultFileNewVersionItem;

        const version = { title: "wololo", changelog: "" } as NewVersion;

        const wrapper = getWrapper(
            item,
            version,
            // eslint-disable-next-line no-template-curly-in-string
            "${ID}-toto-${TITLE}-${STATUS}-${VERSION_NAME}",
        );
        expect(wrapper.findComponent(PreviewFilenameProperty).exists()).toBe(true);
    });
});
