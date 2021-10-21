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

import * as tuleap_api from "../api/tuleap-api";
import type { CommonMarkInterpreter } from "./interpret-commonmark";
import { interpretCommonMark } from "./interpret-commonmark";

function getHost(data: Partial<CommonMarkInterpreter>): CommonMarkInterpreter {
    return { ...data, interpreted_commonmark: "" } as CommonMarkInterpreter;
}

describe(`interpret-commonmark`, () => {
    describe(`interpretCommonMark()`, () => {
        it(`when in preview mode, it switches to edit mode`, async () => {
            const post = jest.spyOn(tuleap_api, "postInterpretCommonMark");
            const content = "# Oh no! Anyway...";

            await interpretCommonMark(getHost({ is_in_preview_mode: true }), content);

            expect(post).not.toHaveBeenCalled();
        });

        it(`when in edit mode, it switches to preview mode
            and sets the interpreted CommonMark on the host`, async () => {
            const post = jest
                .spyOn(tuleap_api, "postInterpretCommonMark")
                .mockResolvedValue("<p>HTML</p>");
            const content = "# Markdown title";

            const host = getHost({ is_in_preview_mode: false });
            const promise = interpretCommonMark(host, content);
            expect(host.is_preview_loading).toBe(true);
            expect(post).toHaveBeenCalled();

            await promise;
            expect(host.has_error).toBe(false);
            expect(host.error_message).toBe("");
            expect(host.is_preview_loading).toBe(false);
            expect(host.is_in_preview_mode).toBe(true);
            expect(host.interpreted_commonmark).toBe("<p>HTML</p>");
        });

        it(`sets the error message when CommonMark cannot be interpreted`, async () => {
            const error = new Error("Failed to interpret the CommonMark");
            jest.spyOn(tuleap_api, "postInterpretCommonMark").mockRejectedValue(error);
            const content = "# Oh no! Anyway...";

            const host = getHost({ is_in_preview_mode: false });
            await interpretCommonMark(host, content);

            expect(host.has_error).toBe(true);
            expect(host.error_message).toBe(error.message);
            expect(host.is_preview_loading).toBe(false);
            expect(host.is_in_preview_mode).toBe(true);
            expect(host.interpreted_commonmark).toBe("");
        });
    });
});
