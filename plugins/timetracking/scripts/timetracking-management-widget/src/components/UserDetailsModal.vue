<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <div role="dialog" v-bind:aria-labelledby="title_id" class="tlp-modal" ref="root">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" v-bind:id="title_id">
                {{ $gettext("Times details by project") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="close"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <div class="query-user">
                <div class="tlp-avatar">
                    <img v-bind:src="user_times.user.avatar_url" loading="lazy" />
                </div>
                <a v-bind:href="user_times.user.user_url">
                    {{ user_times.user.display_name }}
                </a>
            </div>
            <div class="query-time-period">
                <div class="tlp-property">
                    <label class="tlp-label">{{ $gettext("From") }}</label>
                    <span data-test="start-date">{{ query.start_date }}</span>
                </div>
                <div class="tlp-property">
                    <label class="tlp-label">{{ $gettext("To") }}</label>
                    <span data-test="end-date">{{ query.end_date }}</span>
                </div>
            </div>
            <table class="tlp-table">
                <thead>
                    <tr>
                        <th>{{ $gettext("Projects") }}</th>
                        <th class="tlp-table-cell-numeric">{{ $gettext("Times") }}</th>
                    </tr>
                </thead>
                <tbody v-if="user_times.times.length === 0">
                    <tr>
                        <td colspan="2" class="tlp-table-cell-empty">
                            {{ $gettext("No times to display") }}
                        </td>
                    </tr>
                </tbody>
                <template v-else>
                    <tbody>
                        <tr v-for="times in sorted_projects" v-bind:key="times.project.id">
                            <td>
                                <a v-bind:href="`/projects/${times.project.shortname}`">
                                    {{ times.project.label }}
                                </a>
                            </td>
                            <td class="tlp-table-cell-numeric">
                                {{ formatMinutes(times.minutes) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>
                                {{ total }}
                            </th>
                            <th class="tlp-table-cell-numeric">
                                {{ sum }}
                            </th>
                        </tr>
                    </tfoot>
                </template>
            </table>
        </div>
        <div class="tlp-modal-footer">
            <button
                id="button-close"
                type="button"
                data-dismiss="modal"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
            >
                {{ $gettext("Close") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, onBeforeUnmount, onMounted } from "vue";
import { useGettext } from "vue3-gettext";
import type { Query, UserTimes } from "../type";
import { createModal } from "@tuleap/tlp-modal";
import type { Modal } from "@tuleap/tlp-modal";
import { formatMinutes } from "@tuleap/plugin-timetracking-time-formatters";

const { $gettext } = useGettext();

const props = defineProps<{
    query: Query;
    user_times: UserTimes;
    widget_id: number;
}>();

const title_id = computed(
    () => `timetracking-management-user-details-modal-title-${props.widget_id}`,
);
const close = $gettext("Close");

const root = ref<HTMLElement | undefined>();
let modal: Modal | null = null;

const sorted_projects = computed(() =>
    props.user_times.times.toSorted((a, b) =>
        a.project.label_without_icon.localeCompare(b.project.label_without_icon, undefined, {
            numeric: true,
        }),
    ),
);

const total = computed(() =>
    $gettext("Total: %{nb}", {
        nb: String(props.user_times.times.length),
    }),
);

const sum = computed(() =>
    $gettext("∑ %{sum}", {
        sum: formatMinutes(
            props.user_times.times.reduce((total, current) => total + current.minutes, 0),
        ),
    }),
);

onMounted(() => {
    if (root.value) {
        modal = createModal(root.value);
    }
});

onBeforeUnmount(() => {
    modal?.destroy();
});

defineExpose({
    show: () => {
        modal?.show();
    },
});
</script>

<style scoped lang="scss">
// a TLP CSS rule is too greedy, forcing any avatar in a table to be small.
// Since our avatar is in a modal (which is itself included in a table) we should reset the avatar size to the default.
.tlp-modal .tlp-avatar {
    width: 40px;
    height: 40px;
}

.tlp-avatar {
    margin: 0 var(--tlp-small-spacing) 0 0;
}

.query-user {
    margin: 0 0 var(--tlp-medium-spacing);
}

.query-time-period {
    display: flex;
    gap: var(--tlp-medium-spacing);
}
</style>
