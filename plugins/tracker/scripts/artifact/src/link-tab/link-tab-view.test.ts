/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
 *
 */

import { deleteUnlinkCheckBoxes } from "./link-tab-view";

describe(`TabLinkView`, () => {
    it(`should remove the checkbox to unlink artifact link from "link" tab view`, () => {
        const doc = document.implementation.createHTMLDocument();
        doc.body.insertAdjacentHTML(
            "beforeend",
            `
            <table>
             <tr><td class="tracker_report_table_unlink" width="1"> <span><input type="checkbox" name="artifact[7860][removed_values][6391][]" value="6391"></span></td><td>6391</td></tr>
             <tr><td class="tracker_report_table_unlink" width="1"> <span><input type="checkbox" name="artifact[7860][removed_values][6392][]" value="6392"></span></td><td>6392</td></tr>
             <tr> <td class="tracker_report_table_unlink" width="1"> <span><input type="checkbox" name="artifact[7860][removed_values][6393][]" value="6393"></span></td><td>6393</td></tr>
            </table>`,
        );

        deleteUnlinkCheckBoxes();

        const delete_buttons = document.querySelectorAll(".tracker_report_table_unlink");
        expect(delete_buttons).toHaveLength(0);
    });
});
