<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div
        v-if="fully_migrated_fields_count > 0"
        class="alert alert-info"
        data-test="dry-run-message-info"
    >
        <i class="fa fa-info-circle move-artifact-icon"></i>
        <span>{{ message }}</span>
        <fields-list-displayer
            v-bind:fields="fully_migrated_fields"
            v-bind:type="TYPE_FULLY_MIGRATED"
        />
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { useDryRunStore } from "../stores/dry-run";
import type { ArtifactField } from "../api/types";
import { TYPE_FULLY_MIGRATED } from "../types";
import FieldsListDisplayer from "./FieldsListDisplayer.vue";

const { interpolate, $ngettext } = useGettext();

const dry_run_store = useDryRunStore();

const fully_migrated_fields = computed((): ArtifactField[] => dry_run_store.fields_migrated);

const fully_migrated_fields_count = computed((): number => dry_run_store.fields_migrated.length);

const message = computed((): string => {
    return interpolate(
        $ngettext(
            "1 field will be fully migrated:",
            "%{ fully_migrated_fields_count } fields will be fully migrated:",
            fully_migrated_fields_count.value,
        ),
        { fully_migrated_fields_count: fully_migrated_fields_count.value },
    );
});
</script>
