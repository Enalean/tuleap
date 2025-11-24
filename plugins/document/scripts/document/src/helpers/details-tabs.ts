/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

export type DetailsTabs =
    | "versions"
    | "logs"
    | "references"
    | "notifications"
    | "statistics"
    | "approval-table";

export const VersionsTab: DetailsTabs = "versions";
export const LogsTab: DetailsTabs = "logs";
export const ReferencesTab: DetailsTabs = "references";
export const NotificationsTab: DetailsTabs = "notifications";
export const StatisticsTab: DetailsTabs = "statistics";
export const ApprovalTableTab: DetailsTabs = "approval-table";
