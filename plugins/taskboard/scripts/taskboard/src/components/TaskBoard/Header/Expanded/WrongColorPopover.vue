<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="taskboard-header-wrong-color-container">
        <span
            class="taskboard-header-wrong-color"
            ref="trigger"
            data-placement="bottom"
            data-trigger="click"
        >
            <i class="fas fa-exclamation-triangle"></i>
        </span>
        <section
            class="tlp-popover tlp-popover-warning taskboard-header-wrong-color-popover"
            ref="container"
        >
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-header">
                <span class="tlp-popover-title">
                    {{ $gettext("Incompatible usage of color") }}
                </span>
            </div>
            <div class="tlp-popover-body taskboard-header-wrong-color-body">
                <p v-dompurify-html="legacy_palette_message"></p>
                <p>{{ $gettext("Only colors from the new palette can be used.") }}</p>
                <p v-dompurify-html="adjust_configuration_message"></p>
            </div>
        </section>
    </div>
</template>
<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { useState } from "vuex-composition-helpers";
import { createPopover } from "@tuleap/tlp-popovers";
import { useGettext } from "vue3-gettext";
import type { State } from "../../../../store/type";

const gettext_provider = useGettext();

const props = defineProps<{ color: string }>();

const { admin_url } = useState<State>(["admin_url"]);

const trigger = ref<HTMLElement>();
const container = ref<HTMLElement>();

onMounted(() => {
    if (trigger.value && container.value) {
        createPopover(trigger.value, container.value);
    }
});

const legacy_palette_message = computed(() =>
    gettext_provider.interpolate(
        gettext_provider.$gettext(
            "The column is configured to use a color (%{ color }) from the legacy palette.",
        ),
        {
            color: `<span class="taskboard-header-wrong-color-preview"><span class="taskboard-header-wrong-color-preview-color" style="background: ${props.color};"></span><code>${props.color}</code></span>`,
        },
        true,
    ),
);

const adjust_configuration_message = gettext_provider.interpolate(
    gettext_provider.$gettext(
        `Please <a href="%{ admin_url }">adjust configuration</a> to use a suitable color.`,
    ),
    { admin_url: admin_url.value },
);
</script>
