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
    <div class="tlp-alert-warning" v-if="recommendations.length > 0">
        <p class="tlp-alert-title">{{ $gettext("Tracker layout evolution") }}</p>
        <p>
            {{
                $gettext(
                    "New constraints have been introduced to ensure consistent artifact form layouts across the platform.",
                )
            }}
            {{ $gettext("This will provide a smoother user experience and simplify onboarding.") }}
        </p>
        <p>
            {{ $gettext("The new constraints are as follows:") }}
        </p>
        <ul>
            <li>
                {{ $gettext("Fieldsets can only be placed at the root level of the tracker.") }}
            </li>
            <li>{{ $gettext("Fields can only be placed within a column inside a fieldset.") }}</li>
        </ul>
        <p>
            <strong>
                {{ $gettext("Important:") }}
            </strong>
            {{
                $gettext(
                    "Your current layout is not broken per se. You are encouraged, though not required, to update it to align with the new recommendations.",
                )
            }}
        </p>
        <p>{{ $ngettext("Recommendation:", "Recommendations:", recommendations.length) }}</p>
        <ul>
            <li v-for="recommendation of recommendations" v-bind:key="recommendation">
                {{ recommendation }}
            </li>
        </ul>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import { TRACKER_ROOT } from "../injection-symbols";
import { useGettext } from "vue3-gettext";
import { isFieldset } from "../helpers/is-fieldset";
import { flatMapFieldsets } from "../helpers/flat-map-fieldsets";
import { flatMapColumns } from "../helpers/flat-map-columns";
import { isColumnWrapper } from "../helpers/is-column-wrapper";

const tracker_root = strictInject(TRACKER_ROOT);
const { $gettext, $ngettext } = useGettext();

const has_fields_at_root = computed(() =>
    tracker_root.value.children.some((child) => !isFieldset(child)),
);

const flattened_columns = computed(() => flatMapColumns(tracker_root.value));
const has_columns_in_columns = computed(() =>
    flattened_columns.value.some((column) => column.children.some(isColumnWrapper)),
);

const flattened_fieldsets = computed(() => flatMapFieldsets(tracker_root.value));
const has_fieldsets_in_fieldsets = computed(() =>
    flattened_fieldsets.value.some((fieldset) => fieldset.children.some(isFieldset)),
);
const has_fieldsets_in_columns = computed(() =>
    flattened_columns.value.some((column) => column.children.some(isFieldset)),
);
const has_fields_not_in_fieldset_columns = computed(() =>
    flattened_fieldsets.value.some((fieldset) => !fieldset.children.every(isColumnWrapper)),
);

const recommendations = computed(() => [
    ...(has_fields_at_root.value || has_fields_not_in_fieldset_columns.value
        ? [$gettext("Fields should be placed within a column inside a fieldset.")]
        : []),
    ...(has_columns_in_columns.value ? [$gettext("Nested columns should be flattened.")] : []),
    ...(has_fieldsets_in_columns.value || has_fieldsets_in_fieldsets.value
        ? [$gettext("Fieldsets should be placed at the root level of the tracker.")]
        : []),
]);
</script>
