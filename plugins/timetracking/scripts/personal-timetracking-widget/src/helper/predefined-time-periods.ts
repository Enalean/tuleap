/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

export const TODAY = "today";
export const YESTERDAY = "yesterday";
export const LAST_7_DAYS = "last_7_days";
export const CURRENT_WEEK = "current_week";
export const LAST_WEEK = "last_week";
export const LAST_MONTH = "last_month";

const today: Date = new Date();
const days_since_last_monday = 1 - today.getDay();

export type Period = {
    start: Date;
    end: Date;
};

export const getTodayPeriod = (): Period => {
    return {
        start: today,
        end: today,
    };
};

export const getYesterdayPeriod = (): Period => {
    const yesterday = new Date();
    yesterday.setDate(today.getDate() - 1);

    return {
        start: yesterday,
        end: yesterday,
    };
};

export const getLastSevenDaysPeriod = (): Period => {
    const a_week_ago: Date = new Date();
    a_week_ago.setDate(a_week_ago.getDate() - 7);

    return {
        start: a_week_ago,
        end: today,
    };
};

export const getCurrentWeekPeriod = (): Period => {
    const monday: Date = new Date();
    monday.setDate(today.getDate() + days_since_last_monday);
    const sunday = new Date();
    sunday.setDate(monday.getDate() + 6);

    return {
        start: monday,
        end: sunday,
    };
};

export const getLastWeekPeriod = (): Period => {
    const days_until_sunday = 7 - new Date().getDay();
    const last_sunday: Date = new Date();
    last_sunday.setDate(today.getDate() + days_until_sunday - 7);

    const last_monday: Date = new Date();
    last_monday.setDate(today.getDate() + days_since_last_monday - 7);

    return {
        start: last_monday,
        end: last_sunday,
    };
};

export const getLastMonthPeriod = (): Period => {
    const start_last_month = new Date();
    start_last_month.setMonth(today.getMonth() - 1, 1);

    const start_this_month = new Date();
    start_this_month.setMonth(today.getMonth(), 1);

    const end_last_month = new Date();
    end_last_month.setMonth(start_this_month.getMonth());
    end_last_month.setDate(start_this_month.getDate() - 1);

    return {
        start: start_last_month,
        end: end_last_month,
    };
};
