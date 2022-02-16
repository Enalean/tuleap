/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { PurifyHTML } from "../../src/card-text-field/PurifyHTML";

export interface PurifyHTMLStub extends PurifyHTML {
    getCallCount(): number;
}

export const PurifyHTMLStub = {
    withParserAndCount(doc: Document): PurifyHTMLStub {
        let call_count = 0;
        return {
            sanitize: (html_string): DocumentFragment => {
                const template = doc.createElement("template");
                // It's a stub, we are in a test context, it's okay
                // eslint-disable-next-line no-unsanitized/property
                template.innerHTML = html_string;
                return template.content;
            },
            invalidate(): void {
                call_count++;
            },
            getCallCount: (): number => call_count,
        };
    },
};
