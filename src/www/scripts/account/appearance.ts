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

import { DataFormat, GroupedDataFormat, IdTextPair, LoadingData, select2 } from "tlp";
import { sanitize } from "dompurify";
import { render } from "mustache";

initThemeColorSelector();

function initThemeColorSelector(): void {
    const selector = document.getElementById("user-preferences-color-selector");
    if (selector === null || !selector.dataset.colors) {
        return;
    }

    const colors = JSON.parse(selector.dataset.colors);
    select2(selector, {
        minimumResultsForSearch: Infinity,
        data: colors,
        escapeMarkup: sanitize,
        templateResult(result: LoadingData | DataFormat | GroupedDataFormat): string {
            return render(
                `<i class="fa fa-circle user-preferences-color-selector-selection-{{ id }}" aria-hidden="true"></i>
                    {{ text }}
                </div>`,
                result
            );
        },
        templateSelection(
            result: IdTextPair | LoadingData | DataFormat | GroupedDataFormat
        ): string {
            return render(
                `<i class="fa fa-circle user-preferences-color-selector-selection-{{ id }}" aria-hidden="true"></i>
                    {{ text }}
                </div>`,
                result
            );
        }
    });
}
