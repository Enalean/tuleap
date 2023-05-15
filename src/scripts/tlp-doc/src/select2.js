/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import { select2 } from "tlp";

export function initSingleSelect2(example) {
    if (example.id !== "example-select2-") {
        return;
    }
    select2(example.querySelector("#area-select2"), {
        placeholder: "Choose an area",
        allowClear: true,
    });
    select2(example.querySelector("#area-select2-adjusted"), {
        placeholder: "Choose an area",
        allowClear: true,
    });
    select2(example.querySelector("#area-without-autocomplete"), {
        placeholder: "Choose an area",
        allowClear: true,
        minimumResultsForSearch: Infinity,
    });
    select2(example.querySelector("#area-select2-help"), {
        placeholder: "Choose an area",
        allowClear: true,
    });
    select2(example.querySelector("#area-select2-mandatory"), {
        placeholder: "Choose an area",
    });
    select2(example.querySelector("#area-select2-disabled"));
    select2(example.querySelector("#area-select2-error"), {
        placeholder: "Choose an area",
        allowClear: true,
    });
    select2(example.querySelector("#area-select2-small"), {
        placeholder: "Choose an area",
        allowClear: true,
    });
    select2(example.querySelector("#area-select2-large"), {
        placeholder: "Choose an area",
        allowClear: true,
    });
}

export function initMultiSelect2(example) {
    if (example.id !== "example-multi-select2-") {
        return;
    }
    select2(example.querySelector("#types-select2"), {
        placeholder: "Choose a type",
        allowClear: true,
    });
    select2(example.querySelector("#typess-select2"), {
        placeholder: "Choose a type",
        allowClear: true,
    });
    select2(example.querySelector("#types-select2-adjusted"), {
        placeholder: "Choose a type",
        allowClear: true,
    });
    select2(example.querySelector("#types-select2-help"), {
        placeholder: "Choose a type",
        allowClear: true,
    });
    select2(example.querySelector("#types-select2-mandatory"), {
        placeholder: "Choose a type",
    });
    select2(example.querySelector("#types-select2-disabled"), {
        placeholder: "Choose a type",
        allowClear: true,
    });
    select2(example.querySelector("#types-select2-error"), {
        placeholder: "Choose a type",
        allowClear: true,
    });
    select2(example.querySelector("#types-select2-small"), {
        placeholder: "Choose a type",
        allowClear: true,
    });
    select2(example.querySelector("#types-select2-large"), {
        placeholder: "Choose a type",
        allowClear: true,
    });
}

export function initAppendSelect2(example) {
    if (example.id !== "example-appends-") {
        return;
    }
    select2(example.querySelector("#append-select2"), {
        placeholder: "Choose an area",
        allowClear: true,
    });
    select2(example.querySelector("#append-select2-small"), {
        placeholder: "Choose an area",
        allowClear: true,
    });
    select2(example.querySelector("#append-select2-large"), {
        placeholder: "Choose an area",
        allowClear: true,
    });
}

export function initPrependSelect2(example) {
    if (example.id !== "example-prepends-") {
        return;
    }
    select2(example.querySelector("#select2-prepend"), {
        placeholder: "Choose an area",
        allowClear: true,
    });
    select2(example.querySelector("#select2-prepend-small"), {
        placeholder: "Choose an area",
        allowClear: true,
    });
    select2(example.querySelector("#select2-prepend-large"), {
        placeholder: "Choose an area",
        allowClear: true,
    });
}
