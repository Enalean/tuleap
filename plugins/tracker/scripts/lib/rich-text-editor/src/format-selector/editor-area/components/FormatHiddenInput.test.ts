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

import { TEXT_FORMAT_HTML } from "@tuleap/plugin-tracker-constants";
import { createFormatHiddenInput } from "./FormatHiddenInput";
import { render } from "lit/html.js";
import { stripLitExpressionComments } from "../../../test-helper";

describe(`FormatHiddenInput`, () => {
    let mount_point: HTMLDivElement;
    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        mount_point = doc.createElement("div");
    });

    it(`will return a hidden input with the given name and value`, () => {
        const template = createFormatHiddenInput({ name: "basalt", value: TEXT_FORMAT_HTML });
        render(template, mount_point);

        expect(stripLitExpressionComments(mount_point.innerHTML)).toMatchInlineSnapshot(
            `<input type="hidden" name="basalt" value="html">`,
        );
    });
});
