<!--
  - Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <error-message v-bind:error_message="error_message" />
    <no-more-viewable-users-warning v-bind:no_more_viewable_users="no_more_viewable_users" />
    <widget-query-displayer
        v-if="!is_query_being_edited"
        v-on:click="is_query_being_edited = true"
        v-bind:query="current_query"
        v-bind:widget_id="widget_id"
    />
    <widget-query-editor
        v-else
        v-bind:query="current_query"
        v-bind:is_query_being_saved="is_query_being_saved"
        v-bind:save="save"
        v-bind:close="closeEdition"
    />
    <query-results
        v-if="!is_query_being_edited"
        v-bind:widget_id="widget_id"
        v-bind:query="current_query"
    />
</template>

<script setup lang="ts">
import WidgetQueryDisplayer from "./WidgetQueryDisplayer.vue";
import WidgetQueryEditor from "./WidgetQueryEditor.vue";
import { ref } from "vue";
import NoMoreViewableUsersWarning from "./NoMoreViewableUsersWarning.vue";
import { putQuery } from "../api/rest-querier";
import type { User } from "@tuleap/core-rest-api-types";
import type { Query } from "../type";
import { useGettext } from "vue3-gettext";
import ErrorMessage from "./ErrorMessage.vue";
import QueryResults from "./QueryResults.vue";

const props = defineProps<{
    initial_query: Query;
    widget_id: number;
}>();

const { $gettext } = useGettext();

const is_query_being_edited = ref(false);
const is_query_being_saved = ref(false);
const error_message = ref("");

const no_more_viewable_users = ref<User[]>([]);
const current_query = ref<Query>(sortUsers(props.initial_query));

function save(query: Query): void {
    is_query_being_saved.value = true;
    putQuery(props.widget_id, query).match(
        (result) => {
            current_query.value = sortUsers({
                ...query,
                users_list: result.viewable_users,
            });
            no_more_viewable_users.value = result.no_more_viewable_users;

            is_query_being_saved.value = false;
            closeEdition();
        },
        (fault) => {
            error_message.value = $gettext(
                "Error while saving the query: %{error}",
                { error: String(fault) },
                true,
            );
            is_query_being_saved.value = false;
        },
    );
}

function sortUsers(query: Query): Query {
    return {
        ...query,
        users_list: query.users_list.sort(compareUsers),
    };
}

function closeEdition(): void {
    error_message.value = "";
    is_query_being_edited.value = false;
}

function compareUsers(a: User, b: User): number {
    return a.display_name.localeCompare(b.display_name, undefined, { numeric: true });
}
</script>
