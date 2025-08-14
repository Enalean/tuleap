<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div class="tlp-form-element">
        <label class="tlp-label" for="tracker-creation-field-color">
            {{ $gettext("Color") }}
            <i class="fa fa-asterisk"></i>
        </label>
        <select
            ref="color_selector"
            class="tlp-select tracker-color-selector"
            id="tracker-creation-field-color"
            name="tracker-color"
            data-select2-id="tracker-creation-field-color"
            tabindex="-1"
            aria-hidden="true"
        ></select>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from "vue";
import { useStore } from "vuex-composition-helpers";
import mustache from "mustache";
import DOMPurify from "dompurify";
import $ from "jquery";
import type { DataFormat, GroupedDataFormat, LoadingData, Select2Plugin } from "tlp";
import { select2 } from "tlp";
import { useGettext } from "vue3-gettext";
import type { DataForColorPicker } from "../../../../store/type";

const { $gettext } = useGettext();

const store = useStore();
const color_selector = ref<HTMLSelectElement | null>(null);
const select2_color = ref<Select2Plugin | null>(null);

const formatOptionColor = (result: DataFormat | GroupedDataFormat | LoadingData): string => {
    if (!result.id) {
        return "";
    }
    return mustache.render("<span class={{ id }}></span>", result);
};

const hasTrackerAValidColor = (): boolean => {
    return (
        store.state.color_picker_data.findIndex(
            (data: DataForColorPicker) => store.state.tracker_to_be_created.color === data.id,
        ) !== -1
    );
};

const selectColor = (): void => {
    if (!color_selector.value) {
        return;
    }

    if (hasTrackerAValidColor()) {
        $(color_selector.value).val(store.state.tracker_to_be_created.color);
    } else {
        $(color_selector.value).val(store.state.default_tracker_color);
    }

    color_selector.value.dispatchEvent(new Event("change"));
};

watch(
    () => store.state.tracker_to_be_created,
    (newValue, oldValue) => {
        if (oldValue.color !== newValue.color) {
            selectColor();
        }
    },
    { deep: true },
);

onMounted(() => {
    if (!color_selector.value) {
        return;
    }

    select2_color.value = select2(color_selector.value, {
        data: store.state.color_picker_data,
        containerCssClass: "tracker-color-container",
        dropdownCssClass: "tracker-color-results",
        minimumResultsForSearch: Infinity,
        dropdownAutoWidth: true,
        escapeMarkup: DOMPurify.sanitize,
        templateResult: formatOptionColor,
        templateSelection: formatOptionColor,
    });

    selectColor();
});

onBeforeUnmount(() => {
    if (select2_color.value !== null && color_selector.value) {
        $(color_selector.value).off().select2("destroy");
    }
});
</script>
