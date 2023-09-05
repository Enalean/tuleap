/*
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

import { WatchHandler } from "./WatchHandler";
import { CachedPurifier } from "./CachedPurifier";

const buildValueSpan = (scope, element) => {
    const card_field_div = document.createElement("div");
    card_field_div.classList.add("extra-card-field-content");
    const title_span = document.createElement("span");
    title_span.classList.add("extra-card-field-title");
    title_span.textContent = scope.card_field.label + ": ";
    const value_span = document.createElement("span");
    card_field_div.append(title_span, value_span);
    element[0].append(card_field_div);
    return value_span;
};

export default () => {
    return {
        restrict: "AE",
        scope: {
            card_field: "<field",
            filter_terms: "@filterTerms",
        },
        link(scope, element) {
            if (scope.card_field.value === "" || scope.card_field.value === null) {
                return;
            }
            const value_span = buildValueSpan(scope, element);

            const handler = WatchHandler(document, window, value_span, CachedPurifier());
            handler.init(scope.card_field);

            scope.$watchGroup(
                [(scope) => scope.filter_terms, (scope) => scope.card_field],
                handler.onWatch,
            );
        },
    };
};
