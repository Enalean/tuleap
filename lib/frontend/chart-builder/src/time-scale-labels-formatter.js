/*
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import moment from "moment";
import { select } from "d3-selection";
import { sprintf } from "sprintf-js";
import { initGettextSync } from "@tuleap/gettext";
import fr_FR from "../po/fr_FR.po";
import pt_BR from "../po/pt_BR.po";
import { getDifference, getGranularity } from "./chart-dates-service.js";

const DAY = "day";
const WEEK = "week";

export class TimeScaleLabelsFormatter {
    constructor({ layout, first_date, last_date }) {
        const locale = document.body.dataset.userLocale ?? "en_US";
        const gettext_provider = initGettextSync("chart-builder", { fr_FR, pt_BR }, locale);

        const localized_date_formats = {
            day: gettext_provider.gettext("ddd DD"),
            month: gettext_provider.gettext("MMM YYYY"),
            week: gettext_provider.gettext("WW"),
            /// Week format prefix. Chart ticks will be rendered like W01 for week 01, W02 for week 02 and so on.
            week_prefix: gettext_provider.gettext("W%s"),
        };

        Object.assign(this, {
            layout,
            localized_date_formats,
            timeframe_granularity: getGranularity(first_date, last_date),
        });
    }

    formatTicks() {
        const all_ticks = this.layout.selectAll(`.chart-x-axis > .tick`).nodes();
        const format = this.getFormatter();

        all_ticks.forEach(function (tick) {
            const text_element = select(tick).select("text");
            const formatted_label = format(text_element.text());

            text_element.text(formatted_label);
        });

        this.ticksEvery();
    }

    ticksEvery() {
        const all_ticks = this.layout.selectAll(`.chart-x-axis > .tick`).nodes();

        let previous_label;

        all_ticks.forEach((node) => {
            const label = select(node).text();

            if (!previous_label) {
                previous_label = label;
                return;
            }

            if (label === previous_label) {
                select(node).remove();
                return;
            }

            previous_label = label;
        });

        const displayed_ticks = this.layout.selectAll(`.chart-x-axis > .tick`).nodes();

        if (this.canFirstLabelOverlapSecondLabel(displayed_ticks[0], displayed_ticks[1])) {
            select(displayed_ticks[0]).remove();
        }
    }

    getFormatter() {
        if (!this.timeframe_granularity) {
            return (tick_label) => tick_label;
        }

        const tick_format = this.localized_date_formats[this.timeframe_granularity];

        if (this.timeframe_granularity === WEEK) {
            const prefix = this.localized_date_formats.week_prefix;

            return function (date) {
                return sprintf(prefix, moment(date, moment.ISO_8601).format(tick_format));
            };
        }

        return function (date) {
            return moment(date, moment.ISO_8601).format(tick_format);
        };
    }

    canFirstLabelOverlapSecondLabel(first_tick, second_tick) {
        if (this.timeframe_granularity === DAY) {
            return false;
        }

        const first_label = select(first_tick);
        const second_label = select(second_tick);

        const { weeks, days } = getDifference(first_label.datum(), second_label.datum());

        if (this.timeframe_granularity === WEEK) {
            return days < 4;
        }

        return weeks < 2;
    }
}
