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

import { describe, it, expect } from "vitest";
import type { InjectionKey, Component, VNode } from "vue";
import { createApp, defineComponent, h, provide } from "vue";
import { strictInject } from "./strict-inject";

function mount(component: Component): void {
    const el = document.createElement("div");
    const app = createApp(component);
    app.config.warnHandler = (): void => {
        // Do nothing, we might throw error on purposes
    };

    app.mount(el);
}

describe("strict-inject", () => {
    it("resolves the value when it has been provided", () => {
        const key: InjectionKey<string> = Symbol("Test");
        const expected_value = "my_injected_value";
        let setup_consumer_called = false;

        const Consumer = defineComponent({
            setup(): void {
                expect(strictInject(key)).toStrictEqual(expected_value);
                setup_consumer_called = true;
            },
            render() {
                return h("div", []);
            },
        });

        const Provider = defineComponent({
            setup(): void {
                provide(key, expected_value);
            },
            render(): VNode {
                return h(Consumer);
            },
        });

        mount(Provider);

        expect(setup_consumer_called).toBe(true);
    });

    it("throws an error when the value has not been provided", () => {
        const Consumer = {
            setup(): void {
                strictInject(Symbol("TestUndefined"));
            },
            render(): VNode {
                return h("div", []);
            },
        };

        expect(() => mount(Consumer)).toThrowError(/TestUndefined/);
    });
});
