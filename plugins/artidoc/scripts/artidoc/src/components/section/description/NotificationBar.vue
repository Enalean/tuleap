<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <div>
        <span v-if="upload_progress > 0" class="tlp-alert-info">{{ upload_message }}</span>
        <span v-else-if="message" class="tlp-alert-danger">
            {{ message }}
        </span>
    </div>
</template>
<script setup lang="ts">
import { computed, toRefs, watch } from "vue";
import { useGettext } from "vue3-gettext";

const { $gettext, interpolate } = useGettext();

const props = withDefaults(
    defineProps<{
        upload_progress?: number;
        message?: string | null;
        reset_progress: () => void;
    }>(),
    {
        upload_progress: 0,
        message: "",
    },
);

const { upload_progress } = toRefs(props);

watch(upload_progress, () => {
    if (upload_progress.value === 100) {
        setTimeout(() => {
            props.reset_progress();
        });
    }
});

const upload_message = computed(() =>
    interpolate($gettext("Upload image progress: %{ upload_progress }%"), {
        upload_progress: String(upload_progress.value),
    }),
);
</script>
<style lang="scss" scoped>
@use "@tuleap/burningparrot-theme/css/includes/global-variables";

$title-height: 65px;

div {
    display: flex;
    position: sticky;
    top: calc(#{global-variables.$navbar-height} + var(--tlp-medium-spacing) + #{$title-height});
    justify-content: center;
}
</style>
