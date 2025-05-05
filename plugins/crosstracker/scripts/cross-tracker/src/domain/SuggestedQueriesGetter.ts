/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { VueGettextProvider } from "../helpers/vue-gettext-provider";

export type QuerySuggestion = {
    readonly title: string;
    readonly description: string;
    readonly tql_query: string;
};

export type SuggestedQueriesGetter = {
    getTranslatedProjectSuggestedQueries(): QuerySuggestion[];
    getTranslatedPersonalSuggestedQueries(): QuerySuggestion[];
};

export const SuggestedQueries = (gettext_provider: VueGettextProvider): SuggestedQueriesGetter => {
    return {
        getTranslatedProjectSuggestedQueries(): QuerySuggestion[] {
            return [
                {
                    title: gettext_provider.$gettext("All open artifacts"),
                    description: "",
                    tql_query: `SELECT @pretty_title, @status, @last_update_date, @submitted_by
FROM @project = 'self'
WHERE @status = OPEN()
ORDER BY @last_update_date DESC`,
                },
                {
                    title: gettext_provider.$gettext("Open artifacts assigned to me"),
                    description: "",
                    tql_query: `SELECT @pretty_title, @status, @last_update_date, @submitted_by
FROM @project = 'self'
WHERE @status = OPEN() AND @assigned_to = MYSELF()
ORDER BY @last_update_date DESC`,
                },
                {
                    title: gettext_provider.$gettext("All artifacts created in the last 10 days"),
                    description: "",
                    tql_query: `SELECT @pretty_title, @status, @last_update_date, @submitted_by
FROM @project = 'self'
WHERE @submitted_on > NOW() - 10d
ORDER BY @last_update_date DESC`,
                },
            ];
        },
        getTranslatedPersonalSuggestedQueries(): QuerySuggestion[] {
            return [
                {
                    title: gettext_provider.$gettext(
                        "Open artifacts assigned to me in my projects",
                    ),
                    description: "",
                    tql_query: `SELECT @pretty_title, @project.name, @last_update_date, @submitted_by
FROM @project = MY_PROJECTS()
WHERE @assigned_to = MYSELF() AND @status = OPEN()
ORDER BY @last_update_date DESC`,
                },
            ];
        },
    };
};
