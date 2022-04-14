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
            <i v-bind:title="tooltip_text" class="fas fa-question-circle" role="img"></i>
        </label>
        <p data-test="preview">{{ preview }}</p>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";
import type { DefaultFileItem } from "../../../type";
import { computed, ref } from "@vue/composition-api";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../store/configuration";
import { addOriginalFilenameExtension } from "../../../helpers/add-original-filename-extension";
import { isFile } from "../../../helpers/type-check-helper";

const { $gettext } = useGettext();

const filename_preview_label = ref($gettext("Filename preview"));

const props = defineProps<{ item: DefaultFileItem }>();

const { filename_pattern, is_filename_pattern_enforced } = useNamespacedState<ConfigurationState>(
    "configuration",
    ["filename_pattern", "is_filename_pattern_enforced"]
);

const tooltip_text = ref(
    $gettext("Filename will follow a pattern enforced by the document manager configuration.")
);

const preview = computed((): string => {
    return addOriginalFilenameExtension(
        filename_pattern.value
            // eslint-disable-next-line no-template-curly-in-string
            .replace("${TITLE}", props.item.title)
            // eslint-disable-next-line no-template-curly-in-string
            .replace("${VERSION_NAME}", "")
            // eslint-disable-next-line no-template-curly-in-string
            .replace("${STATUS}", props.item.status),
        props.item.file_properties.file
    );
});
</script>

<script lang="ts">
import { defineComponent } from "@vue/composition-api";

export default defineComponent({});
</script>
