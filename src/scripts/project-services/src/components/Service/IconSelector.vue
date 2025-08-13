<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div class="tlp-form-element">
        <label class="tlp-label" v-bind:for="id">
            {{ $gettext("Icon") }}
            <i class="fas fa-asterisk" aria-hidden="true"></i>
        </label>
        <select
            class="tlp-select"
            v-bind:id="id"
            name="icon_name"
            required
            ref="select"
            v-on:change="onChangeEmit"
        >
            <option
                v-for="(icon_info, icon_id) in allowed_icons"
                v-bind:key="icon_id"
                v-bind:value="icon_id"
                v-bind:selected="icon_name === icon_id"
            >
                {{ icon_info.description }}
            </option>
        </select>
    </div>
</template>
<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import type { ListPicker } from "@tuleap/list-picker";
import { createListPicker } from "@tuleap/list-picker";
import { strictInject } from "@tuleap/vue-strict-inject";
import { ALLOWED_ICONS } from "../../injection-symbols";

const { $gettext } = useGettext();
const allowed_icons = strictInject(ALLOWED_ICONS);

defineProps<{
    id: string;
    icon_name: string;
}>();

const emit = defineEmits<{
    (e: "input", value: string): void;
}>();

const selector = ref<ListPicker | null>(null);
const select = ref<HTMLSelectElement>();

onMounted(() => {
    if (!(select.value instanceof HTMLSelectElement)) {
        return;
    }

    selector.value = createListPicker(select.value, {
        is_filterable: true,
        placeholder: $gettext("Choose an icon"),
        items_template_formatter: (html_processor, value_id) => {
            const icon_info = allowed_icons[value_id];

            return html_processor`
                    <i aria-hidden="true" class="project-admin-services-modal-icon-item fa-fw ${icon_info["fa-icon"]}"></i>
                    <span>${icon_info.description}</span>
                `;
        },
        locale: document.body.dataset.userLocale ?? "en_US",
    });
});

onBeforeUnmount(() => {
    selector.value?.destroy();
});

function onChangeEmit($event: Event): void {
    if (!($event.target instanceof HTMLSelectElement)) {
        return;
    }

    emit("input", $event.target.value);
}
</script>
