/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import WritingMode from "./WritingMode.vue";
import { EMITTER } from "../../injection-symbols";
import type { Query } from "../../type";
import { EmitterStub } from "../../../tests/stubs/EmitterStub";
import { REFRESH_ARTIFACTS_EVENT } from "../../helpers/emitter-provider";

describe("WritingMode", () => {
    let emitter: EmitterStub;
    let backend_query: Query;

    beforeEach(() => {
        emitter = EmitterStub();
        backend_query = {
            id: "00000000-03e8-70c0-9e41-6ea7a4e2b78d",
            tql_query: "",
            title: "",
            description: "",
            is_default: false,
        };
    });

    function getWrapper(writing_query: Query): VueWrapper<InstanceType<typeof WritingMode>> {
        return shallowMount(WritingMode, {
            props: {
                writing_query,
                backend_query,
            },
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [EMITTER.valueOf()]: emitter,
                },
            },
        });
    }

    describe("cancel()", () => {
        it("when I hit cancel, then an event will be emitted to cancel the query edition and switch the widget back to reading mode", () => {
            const wrapper = getWrapper({
                id: "",
                tql_query: "",
                title: "",
                description: "",
                is_default: false,
            });

            wrapper.find("[data-test=writing-mode-cancel-button]").trigger("click");
            const emitted = wrapper.emitted("cancel-query-edition");
            expect(emitted).toBeDefined();
            expect(emitter.emitted_event_name.length).toBe(1);
            expect(emitter.emitted_event_name[0]).toBe(REFRESH_ARTIFACTS_EVENT);
            expect(emitter.emitted_event_message[0].unwrapOr("")).toStrictEqual({
                query: backend_query,
            });
        });
    });

    describe("search()", () => {
        it("when I hit search, then an event will be emitted to preview the results and switch the widget to reading mode", () => {
            const wrapper = getWrapper({
                id: "",
                tql_query: "",
                title: "",
                description: "",
                is_default: false,
            });

            wrapper.find("[data-test=search-report-button]").trigger("click");
            const emitted = wrapper.emitted("preview-result");
            expect(emitted).toBeDefined();
            expect(emitter.emitted_event_name.length).toBe(1);
            expect(emitter.emitted_event_name[0]).toBe(REFRESH_ARTIFACTS_EVENT);
        });
    });
});
