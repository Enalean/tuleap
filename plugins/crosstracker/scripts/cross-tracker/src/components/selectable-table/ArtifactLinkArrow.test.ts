/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { beforeEach, describe, expect, it } from "vitest";
import type { Emitter } from "mitt";
import mitt from "mitt";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import type { Events } from "../../helpers/widget-events";
import { EMITTER } from "../../injection-symbols";
import ArtifactLinkArrow from "./ArtifactLinkArrow.vue";
import type { ArtifactLinkDirection } from "../../domain/ArtifactsTable";
import { FORWARD_DIRECTION, REVERSE_DIRECTION } from "../../domain/ArtifactsTable";

const parent_cell = {
    offsetHeight: 32,
    offsetTop: 437,
    offsetLeft: 34,
    offsetWidth: 773,
} as HTMLElement;

const parent_caret = {
    offsetHeight: 13,
    offsetTop: 446,
    offsetLeft: 38,
    offsetWidth: 17,
} as HTMLElement;

const child_cell = {
    offsetHeight: 40,
    offsetTop: 533,
    offsetLeft: 34,
    offsetWidth: 773,
} as HTMLElement;

const child_caret = {
    offsetHeight: 13,
    offsetTop: 546,
    offsetLeft: 70,
    offsetWidth: 17,
} as HTMLElement;

describe("ArtifactLinkArrow", () => {
    let emitter: Emitter<Events>,
        is_last_link: boolean,
        reverse_links_count: number,
        direction: ArtifactLinkDirection;

    beforeEach(() => {
        emitter = mitt<Events>();
        is_last_link = false;
        reverse_links_count = 0;
        direction = FORWARD_DIRECTION;
    });

    function getWrapper(): VueWrapper {
        return shallowMount(ArtifactLinkArrow, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [EMITTER.valueOf()]: emitter,
                },
            },
            props: {
                parent_cell,
                parent_caret,
                child_cell,
                child_caret,
                is_last_link,
                direction,
                reverse_links_count,
            },
        });
    }

    describe("Forward links", () => {
        it("should display an horizontal arrow, when it is not the last link", () => {
            const wrapper = getWrapper();
            const path = wrapper.find("path");

            expect(path.attributes("d")).toMatchInlineSnapshot(`
              "M8.5 83.5 L31 83.5M27 87.5
                      L31 83.5
                      M27 79.5
                      L31 83.5"
            `);
        });

        it("should display an 'L' arrow, when it is the last link", () => {
            is_last_link = true;
            const wrapper = getWrapper();
            const path = wrapper.find("path");

            expect(path.attributes("d")).toMatchInlineSnapshot(`
              "M8.5 83.5 L31 83.5M27 87.5
                      L31 83.5
                      M27 79.5
                      L31 83.5M8.5 1 L8.5 83.5"
            `);
        });

        it("should display an 'L' arrow and an offset, when it is the last link and there are reverse links too", () => {
            is_last_link = true;
            reverse_links_count = 2;

            const wrapper = getWrapper();
            const path = wrapper.find("path");

            expect(path.attributes("d")).toMatchInlineSnapshot(`
              "M18.5 83.5 L31 83.5M27 87.5
                      L31 83.5
                      M27 79.5
                      L31 83.5M18.5 1 L18.5 83.5"
            `);
        });
    });

    describe("Reverse links", () => {
        it("should display an horizontal line, when it is not the last link", () => {
            direction = REVERSE_DIRECTION;

            const wrapper = getWrapper();
            const path = wrapper.find("path");

            expect(path.attributes("d")).toMatchInlineSnapshot(`"M8.5 83.5 L31 83.5"`);
        });

        it("should display an 'L' arrow, when it is the last link", () => {
            direction = REVERSE_DIRECTION;
            is_last_link = true;

            const wrapper = getWrapper();
            const path = wrapper.find("path");

            expect(path.attributes("d")).toMatchInlineSnapshot(`
              "M8.5 83.5 L31 83.5M8.5 83.5 L8.5 1M8.5 1
                      L4.5 5
                      M8.5 1
                      L12.5 5"
            `);
        });
    });
});
