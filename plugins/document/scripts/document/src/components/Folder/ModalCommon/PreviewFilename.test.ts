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

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import PreviewFilename from "./PreviewFilename.vue";
import type { DefaultFileItem } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { FILENAME_PATTERN, IS_FILENAME_PATTERN_ENFORCED } from "../../../configuration-keys";

describe("PreviewFilename", () => {
    function getWrapper(
        item: DefaultFileItem,
        filename_pattern: string,
        is_filename_pattern_enforced: boolean,
    ): VueWrapper<InstanceType<typeof PreviewFilename>> {
        return shallowMount(PreviewFilename, {
            props: {
                item,
            },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [FILENAME_PATTERN.valueOf()]: filename_pattern,
                    [IS_FILENAME_PATTERN_ENFORCED.valueOf()]: is_filename_pattern_enforced,
                },
            },
        });
    }

    it("should update the preview according to item's values", () => {
        const item = {
            id: 42,
            type: "file",
            title: "Lorem ipsum",
            status: "approved",
            description: "",
            file_properties: {
                file: new File([], "values.json"),
            },
        } as DefaultFileItem;
        const wrapper = getWrapper(
            item,
            // eslint-disable-next-line no-template-curly-in-string
            "${ID}-toto-${TITLE}-${STATUS}-${VERSION_NAME}",
            true,
        );

        expect(wrapper.vm.preview).toBe(
            // eslint-disable-next-line no-template-curly-in-string
            "${ID}-toto-Lorem ipsum-approved-.json",
        );
    });
});
