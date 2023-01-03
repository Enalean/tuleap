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
import type { Configuration } from "./configuration";
import { unserializeConfiguration } from "./configuration";

describe("configuration", () => {
    it("unserializes a configuration", () => {
        const expected_config: Configuration = {
            project: {
                name: "project1",
                href: "/projects/project1",
            },
        } as Configuration;

        const config = unserializeConfiguration(JSON.stringify(expected_config));

        expect(config).toStrictEqual(expected_config);
    });

    it("does nothing when no configuration is given", () => {
        expect(unserializeConfiguration(undefined)).toBeUndefined();
    });

    it("does not return a configuration when the unserialization fails", () => {
        expect(unserializeConfiguration("{")).toBeUndefined();
    });
});
