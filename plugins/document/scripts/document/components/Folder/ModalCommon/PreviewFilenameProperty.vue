<!--
  - Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
  -
  -->

<template>
    <div class="tlp-property" v-if="is_filename_pattern_enforced && isFile(props.item)">
        <label class="tlp-label">
            {{ filename_preview_label }}
            <i v-bind:title="tooltip_text" class="fa-solid fa-circle-question" role="img"></i>
        </label>
        <p data-test="preview">
            <slot></slot>
        </p>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { DefaultFileItem } from "../../../type";
import { ref } from "vue";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../store/configuration";
import { isFile } from "../../../helpers/type-check-helper";

const { $gettext } = useGettext();

const filename_preview_label = ref($gettext("Filename preview"));

const props = defineProps<{ item: DefaultFileItem }>();

const { is_filename_pattern_enforced } = useNamespacedState<ConfigurationState>("configuration", [
    "is_filename_pattern_enforced",
]);

const tooltip_text = ref(
    $gettext("Filename will follow a pattern enforced by the document manager configuration."),
);
</script>
