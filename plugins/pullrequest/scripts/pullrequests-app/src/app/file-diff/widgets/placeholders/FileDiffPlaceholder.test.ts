/*
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

import { FileDiffPlaceholder } from "./FileDiffPlaceholder";
import type { HostElement } from "./FileDiffPlaceholder";
import { selectOrThrow } from "@tuleap/dom";

describe("FileDiffPlaceholder", () => {
    let target: ShadowRoot;

    beforeEach(() => {
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    describe("code placeholder VS comment placeholder", () => {
        it(`Given that the placeholder is meant to replace a missing line of code
            Then it should have only the default class`, () => {
            const host = {
                isReplacingAComment: false,
                height: 60,
            } as unknown as HostElement;

            const update = FileDiffPlaceholder.content(host);
            update(host, target);

            const placeholder = selectOrThrow(
                target,
                "[data-test=pullrequest-file-diff-placeholder]",
            );

            expect(Array.from(placeholder.classList)).toStrictEqual([
                "pull-request-file-diff-placeholder-block",
            ]);
            expect(placeholder.style.height).toBe("60px");
        });

        it(`Given that the placeholder is meant to take the place of an inline-comment
            Then it should have only the default and the comment-placeholder classes`, () => {
            const host = {
                isReplacingAComment: true,
                height: 80,
            } as unknown as HostElement;

            const update = FileDiffPlaceholder.content(host);
            update(host, target);

            const placeholder = selectOrThrow(
                target,
                "[data-test=pullrequest-file-diff-placeholder]",
            );

            expect(Array.from(placeholder.classList)).toStrictEqual([
                "pull-request-file-diff-placeholder-block",
                "pull-request-file-diff-comment-placeholder-block",
            ]);
            expect(placeholder.style.height).toBe("80px");
        });
    });
});
