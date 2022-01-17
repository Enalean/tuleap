/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

export interface Campaign {
    readonly id: number;
    readonly label: string;
    readonly nb_of_notrun: number;
    readonly nb_of_blocked: number;
    readonly nb_of_failed: number;
    readonly nb_of_passed: number;
    readonly is_being_refreshed: boolean;
    readonly is_just_refreshed: boolean;
    readonly is_error: boolean;
}

export interface GlobalExportProperties {
    readonly platform_name: string;
    readonly platform_logo_url: string;
    readonly project_name: string;
    readonly campaign_name: string;
    readonly campaign_url: string;
    readonly user_display_name: string;
    readonly user_timezone: string;
    readonly user_locale: string;
    readonly base_url: string;
}

export interface DateTimeLocaleInformation {
    readonly locale: string;
    readonly timezone: string;
}

export interface ExportDocument {
    readonly name: string;
}

export interface GettextProvider {
    getString(
        translatable_string: string,
        scope?: null | Record<string, string>,
        context?: string
    ): string;
}
