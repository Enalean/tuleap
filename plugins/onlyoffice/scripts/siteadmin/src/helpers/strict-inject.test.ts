/**
 * Copyright (c) 2021-Present Enalean
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

import { describe, it, expect, vi } from "vitest";
import type { InjectionKey, VNode } from "vue";
import { h, provide } from "vue";
import { strictInject } from "./strict-inject";
import { mount } from "@vue/test-utils";

describe("strict-inject", () => {
    it("resolves the value when it has been provided", () => {
        const key: InjectionKey<string> = Symbol("Test");
        const expected_value = "my_injected_value";

        const Consumer = {
            setup(): () => string {
                const value = strictInject(key);
                return (): string => value;
            },
        };

        const Provider = {
            setup(): () => VNode {
                provide(key, expected_value);
                return (): VNode => h(Consumer);
            },
        };
        const wrapper = mount(Provider);

        expect(wrapper.text()).toStrictEqual(expected_value);
    });

    it("throws an error when the value has not been provided", () => {
        const Consumer = {
            setup(): () => unknown {
                const value = strictInject(Symbol("TestUndefined"));
                return (): unknown => value;
            },
        };

        vi.spyOn(console, "warn").mockImplementation(() => {
            // Do nothing
        });
        expect(() => mount(Consumer)).toThrowError(/TestUndefined/);
    });
});
