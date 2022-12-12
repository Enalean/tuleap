/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import type { Editor } from "codemirror";
import { setInterval, clearInterval } from "../../window-helper";

const DELAY_IN_MILLISECONDS = 20;

export function synchronize(left_code_mirror: Editor, right_code_mirror: Editor): void {
    let active_handler: Editor | null = null;
    let state: 0 | 1 | 2;

    function scrollHandler(source_code_mirror: Editor, destination_code_mirror: Editor): void {
        let timer: number;
        const timerHandler = (): void => {
            if (active_handler === source_code_mirror) {
                fixup();
            }
        };

        if (active_handler === null) {
            active_handler = source_code_mirror;
            timer = setInterval(timerHandler, DELAY_IN_MILLISECONDS);
        }
        if (active_handler === source_code_mirror) {
            const { left, top } = source_code_mirror.getScrollInfo();
            destination_code_mirror.scrollTo(left, top);
            state = 0;
        }

        function fixup(): void {
            switch (state) {
                default:
                case 0:
                    state = 1;
                    break;
                case 1:
                    state = 2;
                    return;
                case 2:
                    active_handler = null;
                    clearInterval(timer);
            }
        }
    }

    left_code_mirror.on("scroll", () => scrollHandler(left_code_mirror, right_code_mirror));
    right_code_mirror.on("scroll", () => scrollHandler(right_code_mirror, left_code_mirror));
}
