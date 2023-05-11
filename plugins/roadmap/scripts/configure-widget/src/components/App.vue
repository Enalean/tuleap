<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
        <div class="tlp-form-element">
            <label class="tlp-label" v-bind:for="title_id">
                {{ $gettext("Title") }}
                <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
            </label>
            <input
                type="text"
                class="tlp-input"
                v-bind:id="title_id"
                name="roadmap[title]"
                v-model="user_selected_title"
                required
                v-bind:placeholder="$gettext('Roadmap')"
            />
        </div>
        <div class="tlp-form-element">
            <label class="tlp-label" v-bind:for="progress_of_id">
                {{ $gettext("Show progress of") }}
                <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
            </label>

            <select
                v-bind:id="progress_of_id"
                name="roadmap[tracker_ids][]"
                v-model="user_selected_tracker_ids"
                multiple
                required
                data-test="tracker"
                ref="trackers_picker"
            >
                <option
                    v-for="tracker of suitable_trackers"
                    v-bind:key="tracker.id"
                    v-bind:value="tracker.id"
                >
                    {{ tracker.title }}
                </option>
            </select>
        </div>
        <div class="tlp-form-element">
            <label class="tlp-label" v-bind:for="timescale_id">
                {{ $gettext("Default timescale") }}
            </label>
            <select
                class="tlp-select tlp-select-small tlp-select-adjusted"
                v-bind:id="timescale_id"
                name="roadmap[default_timescale]"
                data-test="timescale"
                v-model="user_selected_default_timescale"
            >
                <option value="week">{{ $gettext("Week") }}</option>
                <option value="month">{{ $gettext("Month") }}</option>
                <option value="quarter">{{ $gettext("Quarter") }}</option>
            </select>
        </div>
        <hr class="roadmap-widget-configuration-separator" />
        <h2 class="tlp-modal-subtitle" v-bind:class="subtitle_class">Timeframe ribbons</h2>
        <p>
            {{
                $gettext(
                    "Artifacts of the selected tracker will appear in the upper part of the Roadmap, below the Quarters/Months/Weeks."
                )
            }}
        </p>
        <p>
            {{
                $gettext(
                    "Selected trackers are expected to have continuous time (i.e. artifacts timeframe do not overlap)."
                )
            }}
        </p>
        <div class="tlp-form-element">
            <label class="tlp-label" v-bind:for="lvl1_id">
                {{ $gettext("Timeframe ribbon, level 1 (eg. Release)") }}
            </label>

            <select
                class="tlp-select tlp-select-adjusted"
                v-bind:id="lvl1_id"
                name="roadmap[lvl1_iteration_tracker_id]"
                v-model="user_selected_lvl1_iteration_tracker_id"
                data-test="lvl1-iteration-tracker"
            >
                <option value="" selected>
                    {{ $gettext("Please choose a tracker") }}
                </option>
                <option
                    v-for="tracker of suitable_lvl1_iteration_trackers"
                    v-bind:key="tracker.id"
                    v-bind:value="tracker.id"
                >
                    {{ tracker.title }}
                </option>
            </select>
        </div>
        <div
            class="tlp-form-element"
            v-bind:class="{ 'tlp-form-element-disabled': is_lvl2_disabled }"
        >
            <label class="tlp-label" v-bind:for="lvl2_id">
                {{ $gettext("Timeframe ribbon, level 2 (eg. Sprint)") }}
            </label>

            <select
                class="tlp-select tlp-select-adjusted"
                v-bind:id="lvl2_id"
                name="roadmap[lvl2_iteration_tracker_id]"
                v-model="user_selected_lvl2_iteration_tracker_id"
                v-bind:disabled="is_lvl2_disabled"
                data-test="lvl2-iteration-tracker"
            >
                <option value="" selected>
                    {{ $gettext("Please choose a tracker") }}
                </option>
                <option
                    v-for="tracker of suitable_lvl2_iteration_trackers"
                    v-bind:key="tracker.id"
                    v-bind:value="tracker.id"
                >
                    {{ tracker.title }}
                </option>
            </select>
            <p class="tlp-text-info">
                <i class="far fa-life-ring" aria-hidden="true"></i>
                {{ $gettext("Level 2 is expected to be a sub-division of level 1.") }}
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
import type { Tracker } from "../type";
import type { ListPicker } from "@tuleap/list-picker";
import { createListPicker } from "@tuleap/list-picker";
import type { TimeScale } from "../../../roadmap-widget/src/type";
import { useGettext } from "vue3-gettext";

const trackers_picker = ref<HTMLSelectElement | null>(null);

interface AppProps {
    widget_id: number;
    title: string;
    trackers: Tracker[];
    selected_tracker_ids: number[];
    selected_default_timescale: TimeScale;
    selected_lvl1_iteration_tracker_id: number | "";
    selected_lvl2_iteration_tracker_id: number | "";
    is_in_creation: boolean;
}

const props = withDefaults(defineProps<AppProps>(), {
    title: "",
    trackers: () => [],
    selected_tracker_ids: () => [],
    selected_default_timescale: "month",
    selected_lvl1_iteration_tracker_id: "",
    selected_lvl2_iteration_tracker_id: "",
});

const { $gettext } = useGettext();

const user_selected_tracker_ids = ref<number[]>(props.selected_tracker_ids);
const user_selected_lvl1_iteration_tracker_id = ref<number | "">(
    props.selected_lvl1_iteration_tracker_id
);
const user_selected_lvl2_iteration_tracker_id = ref<number | "">(
    props.selected_lvl2_iteration_tracker_id
);
const user_selected_default_timescale = ref<TimeScale>(props.selected_default_timescale);
const user_selected_title = ref<string>(props.title);

let list_picker: ListPicker | undefined = undefined;

onMounted(() => {
    const select = trackers_picker.value;
    if (select) {
        list_picker = createListPicker(select, {
            locale: document.body.dataset.userLocale,
            is_filterable: true,
            placeholder: $gettext("Please choose a tracker"),
        });
    }
});

onUnmounted((): void => {
    list_picker?.destroy();
});

watch(user_selected_lvl1_iteration_tracker_id, (): void => {
    if (!user_selected_lvl1_iteration_tracker_id.value) {
        user_selected_lvl2_iteration_tracker_id.value = "";
    }
});

const title_id = computed((): string => "title-" + props.widget_id);

const progress_of_id = computed((): string => "roadmap-tracker-" + props.widget_id);

const timescale_id = computed((): string => "roadmap-timescale-" + props.widget_id);

const lvl1_id = computed((): string => "lvl1-" + props.widget_id);

const lvl2_id = computed((): string => "lvl2-" + props.widget_id);

const subtitle_class = computed((): string =>
    props.is_in_creation ? "roadmap-widget-configuration-subtitle" : ""
);

const suitable_trackers = computed((): Tracker[] =>
    props.trackers.filter(
        (tracker) =>
            tracker.id !== user_selected_lvl1_iteration_tracker_id.value &&
            tracker.id !== user_selected_lvl2_iteration_tracker_id.value
    )
);

const suitable_lvl1_iteration_trackers = computed((): Tracker[] => {
    return props.trackers.filter(
        (tracker) =>
            !user_selected_tracker_ids.value.some((id) => tracker.id === id) &&
            tracker.id !== user_selected_lvl2_iteration_tracker_id.value
    );
});

const suitable_lvl2_iteration_trackers = computed((): Tracker[] => {
    return props.trackers.filter(
        (tracker) =>
            !user_selected_tracker_ids.value.some((id) => tracker.id === id) &&
            tracker.id !== user_selected_lvl1_iteration_tracker_id.value
    );
});

const is_lvl2_disabled = computed((): boolean => {
    return (
        !user_selected_lvl1_iteration_tracker_id.value &&
        !user_selected_lvl2_iteration_tracker_id.value
    );
});
</script>
