/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

// Based upon https://gist.github.com/lgarron/d1dee380f4ed9d825ca7

interface WindowIE11 extends Window {
    clipboardData: {
        setData: (key: string, value: string) => boolean;
    };
}

function looksLikeIE11(window: Window): window is WindowIE11 {
    return typeof ClipboardEvent === "undefined" && "clipboardData" in window;
}

export function writeTextToClipboard(str: string): Promise<void> {
    return new Promise(function (resolve, reject) {
        let success = false;
        function listener(event: ClipboardEvent): void {
            const clipboardData = event.clipboardData;
            if (clipboardData === null) {
                success = false;
                return;
            }
            clipboardData.setData("text/plain", str);
            event.preventDefault();
            success = true;
        }

        if (looksLikeIE11(window)) {
            success = window.clipboardData.setData("Text", str);
        } else {
            document.addEventListener("copy", listener);
            document.execCommand("copy");
            document.removeEventListener("copy", listener);
        }
        success ? resolve() : reject();
    });
}
