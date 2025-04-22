<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <select ref="selector" class="tlp-input" style="width: 100%">
        <option v-if="!is_multiple"></option>
    </select>
</template>
<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from "vue";
import { useGettext } from "vue3-gettext";
import type {
    DataFormat,
    GroupedDataFormat,
    IdTextPair,
    LoadingData,
    Options,
    Select2Plugin,
} from "tlp";
import { select2 } from "tlp";
import $ from "jquery";
import DOMPurify from "dompurify";
import mustache from "mustache";
import type { UserForPeoplePicker } from "../../../../../../../store/swimlane/card/UserForPeoplePicker";

const { $gettext } = useGettext();

const props = defineProps<{
    is_multiple: boolean;
    users: UserForPeoplePicker[];
}>();

const emit = defineEmits<{
    (e: "input", user_ids: number[]): void;
}>();

let select2_people_picker: Select2Plugin | null = null;

const selector = ref<HTMLSelectElement>();

onMounted(() => {
    const placeholder = props.is_multiple
        ? { text: $gettext("John"), id: "0" }
        : $gettext("Please choose…");

    const configuration: Options = {
        allowClear: true,
        data: props.users,
        multiple: props.is_multiple,
        placeholder,
        escapeMarkup: DOMPurify.sanitize,
        templateResult: formatUser,
        templateSelection: formatUserWhenSelected,
    };

    if (!selector.value) {
        return;
    }
    select2_people_picker = select2(selector.value, configuration);

    $(selector.value).on("change", onChange).select2("open");
});

onBeforeUnmount(() => {
    if (select2_people_picker !== null && selector.value) {
        $(selector.value).off().select2("destroy");
    }
});

function onChange(): void {
    if (!selector.value) {
        return;
    }
    let selected_ids: string[];
    const val: string | number | string[] | undefined = $(selector.value).val();
    if (!val) {
        selected_ids = [];
    } else if (typeof val === "string" || typeof val === "number") {
        selected_ids = [`${val}`];
    } else {
        selected_ids = val;
    }

    const selected_ids_as_number: number[] = selected_ids.map((id) => Number(id));

    emit("input", selected_ids_as_number);
}

function formatUser(user: DataFormat | GroupedDataFormat | LoadingData): string {
    if (!isForPeoplePicker(user)) {
        return "";
    }

    return mustache.render(
        `<div class="select2-result-user">
            <div class="tlp-avatar-mini select2-result-user__avatar">
                <img src="{{ avatar_url }}" loading="lazy">
            </div>
            {{ display_name }}
        </div>`,
        user,
    );
}

function isForPeoplePicker(
    user: IdTextPair | DataFormat | GroupedDataFormat | LoadingData,
): boolean {
    return "avatar_url" in user;
}

function formatUserWhenSelected(
    user: IdTextPair | LoadingData | DataFormat | GroupedDataFormat,
): string {
    if (!isForPeoplePicker(user)) {
        return user.text;
    }

    return mustache.render(
        `<div class="tlp-avatar-mini">
            <img src="{{ avatar_url }}" loading="lazy">
        </div>
        {{ display_name }}`,
        user,
    );
}
</script>
