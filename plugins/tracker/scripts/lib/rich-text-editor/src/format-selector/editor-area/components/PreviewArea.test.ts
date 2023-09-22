/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { createPreviewArea } from "./PreviewArea";
import type { GettextProvider } from "@tuleap/gettext";
import { render } from "lit-html";
import { stripLitExpressionComments } from "../../../test-helper";
import { initGettextSync } from "@tuleap/gettext";

jest.mock("dompurify", () => {
    const realDomPurify = jest.requireActual("dompurify");
    const fakeSanitize = (source: string): string => source;
    return {
        __esModule: true,
        default: { ...realDomPurify, sanitize: fakeSanitize },
    };
});

const identity = <T>(param: T): T => param;

describe(`PreviewArea`, () => {
    let mount_point: HTMLDivElement, gettext_provider: GettextProvider;
    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        mount_point = doc.createElement("div");
        gettext_provider = initGettextSync("rich-text-editor", {}, "en_US");
    });

    it(`given a null promise, it will show nothing`, () => {
        const template = createPreviewArea(null, gettext_provider);
        render(template, mount_point);

        expect(mount_point.childElementCount).toBe(0);
    });

    it(`when the promise is fulfilled, it will return a div with its contents`, async () => {
        const html_string = "<p>Some HTML</p>";
        const promise = Promise.resolve(html_string);
        const template = createPreviewArea(promise, gettext_provider);
        render(template, mount_point);
        await promise;
        // // I don't really understand why, but I have to await twice
        await promise;

        expect(mount_point.innerHTML).toContain(html_string);
    });

    it.each([["tlp-mermaid-diagram"], ["tlp-syntax-highlighting"]])(
        `when the promise is fulfilled, it will return a div with its contents that contain custom elements %s`,
        async (custom_element) => {
            const html_string = `<${custom_element}>Some content</${custom_element}>`;
            const promise = Promise.resolve(html_string);
            const template = createPreviewArea(promise, gettext_provider);
            render(template, mount_point);
            await promise;
            // I don't really understand why, but I have to await twice
            await promise;

            expect(mount_point.innerHTML).toContain(html_string);
        },
    );

    it(`when the promise is rejected, it will return an alert with the error message`, () => {
        const promise = Promise.reject(new Error("Network Error"));
        const template = createPreviewArea(promise, gettext_provider);
        render(template, mount_point);
        return promise.catch(identity).then(() => {
            expect(stripLitExpressionComments(mount_point.innerHTML)).toMatchInlineSnapshot(`
"
        <div>
            
    <div class="alert alert-error">
        There was an error in the Markdown preview:
        <br>
        Network Error
    </div>

        </div>
    "
`);
        });
    });
});
