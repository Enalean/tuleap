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
        v-if="!is_move_possible || not_migrated_fields_count > 0"
        class="alert alert-error"
        data-test="dry-run-message-error"
    >
        <i class="fa fa-exclamation-circle move-artifact-icon move-artifact-error-icon"></i>
        <span v-if="!is_move_possible" data-test="move-action-not-possible-error-message">
            {{
                $gettext(
                    "This artifact cannot be moved to the selected tracker because none of its fields matches with it."
                )
            }}
        </span>

        <span v-if="is_move_possible" data-test="not-migrated-field-error-message">
            {{ message }}
        </span>
        <fields-list-displayer
            v-if="is_move_possible"
            v-bind:fields="not_migrated_fields"
            v-bind:type="'not-migrated'"
        />
    </div>
</template>
<script setup lang="ts">
import { computed } from "vue";
import { useGettext } from "vue3-gettext";
import { useState } from "vuex-composition-helpers";
import type { ArtifactField, RootState } from "../store/types";
import FieldsListDisplayer from "./FieldsListDisplayer.vue";

const { interpolate, $ngettext, $gettext } = useGettext();

const { dry_run_fields, is_move_possible } = useState<
    Pick<RootState, "dry_run_fields" | "is_move_possible">
>(["dry_run_fields", "is_move_possible"]);

const not_migrated_fields = computed(
    (): ArtifactField[] => dry_run_fields.value.fields_not_migrated
);

const not_migrated_fields_count = computed(
    (): number => dry_run_fields.value.fields_not_migrated.length
);

const message = computed((): string => {
    return interpolate(
        $ngettext(
            "1 field does not match with the targeted tracker. If you confirm your action, its value will be lost forever:",
            "%{ not_migrated_fields_count } fields do not match with the targeted tracker. If you confirm your action, their values will be lost forever:",
            not_migrated_fields_count.value
        ),
        { not_migrated_fields_count: not_migrated_fields_count.value }
    );
});
</script>
