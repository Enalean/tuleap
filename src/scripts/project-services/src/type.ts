/**
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
 */

export type CsrfToken = {
    readonly value: string;
    readonly name: string;
};

type IconInfo = {
    readonly description: string;
    readonly "fa-icon": string;
};

export type AllowedIcons = Record<string, IconInfo>;

export type Service = {
    readonly id: number | null;
    icon_name: string;
    label: string;
    short_name: string;
    link: string;
    description: string;
    is_active: boolean;
    is_used: boolean;
    is_in_iframe: boolean;
    is_in_new_tab: boolean;
    rank: number;
    is_project_scope: boolean;
    is_disabled_reason: boolean;
    readonly is_link_customizable: boolean;
};
