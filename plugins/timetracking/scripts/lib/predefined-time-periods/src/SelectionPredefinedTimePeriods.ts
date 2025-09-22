/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { define, html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import { Option } from "@tuleap/option";
import { gettext_provider } from "./gettext-provider";
import type { Period, PredefinedTimePeriod } from "./predefined-time-periods";
import {
    TODAY,
    YESTERDAY,
    CURRENT_WEEK,
    LAST_7_DAYS,
    LAST_WEEK,
    LAST_MONTH,
    getCurrentWeekPeriod,
    getLastMonthPeriod,
    getLastSevenDaysPeriod,
    getLastWeekPeriod,
    getTodayPeriod,
    getYesterdayPeriod,
} from "./predefined-time-periods";

export const TAG = "tuleap-predefined-time-period-select";

export type PeriodOption = Option<Period>;

export type PredefinedTimePeriodSelect = {
    onselection: (selected_time_period: string, period: PeriodOption) => void;
    selected_time_period: PredefinedTimePeriod | "";
    resetSelection(): void;
};

export type InternalPredefinedTimePeriodSelect = Readonly<PredefinedTimePeriodSelect> & {
    render: () => HTMLElement;
    select_element: HTMLSelectElement;
    onChange(host: InternalPredefinedTimePeriodSelect): void;
};

export type HostElement = InternalPredefinedTimePeriodSelect & HTMLElement;

type PredefinedTimePeriodOption = {
    readonly value: PredefinedTimePeriod | "";
    readonly label: string;
    readonly is_selected: boolean;
};

export const getPeriodAccordingToSelectedPreset = (selected_value: string): PeriodOption => {
    switch (selected_value) {
        case TODAY:
            return Option.fromValue(getTodayPeriod());
        case YESTERDAY:
            return Option.fromValue(getYesterdayPeriod());
        case LAST_7_DAYS:
            return Option.fromValue(getLastSevenDaysPeriod());
        case CURRENT_WEEK:
            return Option.fromValue(getCurrentWeekPeriod());
        case LAST_WEEK:
            return Option.fromValue(getLastWeekPeriod());
        case LAST_MONTH:
            return Option.fromValue(getLastMonthPeriod());
        default:
            return Option.nothing();
    }
};

const getOptions = (host: InternalPredefinedTimePeriodSelect): PredefinedTimePeriodOption[] => {
    const isSelected = (period: PredefinedTimePeriod): boolean =>
        period === host.selected_time_period;

    return [
        {
            value: "",
            label: gettext_provider.gettext("Please choose..."),
            is_selected: host.selected_time_period === "",
        },
        {
            value: TODAY,
            label: gettext_provider.gettext("Today"),
            is_selected: isSelected(TODAY),
        },
        {
            value: YESTERDAY,
            label: gettext_provider.gettext("Yesterday"),
            is_selected: isSelected(YESTERDAY),
        },
        {
            value: LAST_7_DAYS,
            label: gettext_provider.gettext("Last 7 days"),
            is_selected: isSelected(LAST_7_DAYS),
        },
        {
            value: CURRENT_WEEK,
            label: gettext_provider.gettext("Current week"),
            is_selected: isSelected(CURRENT_WEEK),
        },
        {
            value: LAST_WEEK,
            label: gettext_provider.gettext("Last week"),
            is_selected: isSelected(LAST_WEEK),
        },
        {
            value: LAST_MONTH,
            label: gettext_provider.gettext("Last month"),
            is_selected: isSelected(LAST_MONTH),
        },
    ];
};

export const renderContent = (
    host: InternalPredefinedTimePeriodSelect,
): UpdateFunction<InternalPredefinedTimePeriodSelect> => html`
    <div class="tlp-form-element">
        <label for="people-timetracking-query-editor-predefined-periods" class="tlp-label">
            ${gettext_provider.gettext("Predefined periods")}
        </label>
        <div class="tlp-form-element tlp-form-element-prepend">
            <span class="tlp-prepend">
                <i class="fa-solid fa-calendar-days" aria-hidden="true"></i>
            </span>
            <select
                class="tlp-select tlp-input"
                onchange="${host.onChange}"
                data-test="predefined-periods-select"
                data-role="select-element"
            >
                ${getOptions(host).map(
                    (option) => html`
                        <option value="${option.value}" selected="${option.is_selected}">
                            ${option.label}
                        </option>
                    `,
                )}
            </select>
        </div>
    </div>
`;

export const onChange = (host: InternalPredefinedTimePeriodSelect): void => {
    const selected_option = host.select_element.value;
    host.onselection(selected_option, getPeriodAccordingToSelectedPreset(selected_option));
};

export const resetSelection = (host: InternalPredefinedTimePeriodSelect): (() => void) => {
    return (): void => {
        host.select_element.value = "";
        onChange(host);
    };
};

export const element = define.compile<InternalPredefinedTimePeriodSelect>({
    tag: TAG,
    onselection: (host, value) => value,
    selected_time_period: "",
    resetSelection,
    select_element: (host: InternalPredefinedTimePeriodSelect): HTMLSelectElement => {
        const select_element = host.render().querySelector("[data-role=select-element]");
        if (!(select_element instanceof HTMLSelectElement)) {
            throw new Error("Unable to find a select element in PredefinedTimePeriodSelect");
        }

        return select_element;
    },
    onChange: () => onChange,
    render: renderContent,
});

if (!customElements.get(TAG)) {
    customElements.define(TAG, element);
}
