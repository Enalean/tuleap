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
        <blockquote v-if="user_selected_tracker_ids.length === 1">
            <div
                class="tlp-form-element"
                v-bind:class="{ 'tlp-form-element-error': load_reports_error }"
            >
                <label class="tlp-label" v-bind:for="filter_report_id">
                    {{ $gettext("Filter") }}
                </label>

                <select
                    v-bind:id="filter_report_id"
                    name="roadmap[filter_report_id]"
                    v-model="user_selected_filter_report_id"
                    data-test="report"
                    class="tlp-select tlp-select-adjusted roadmap-widget-configuration-filter"
                >
                    <option value="" selected>
                        {{ $gettext("None") }}
                    </option>
                    <option
                        v-for="report of reports_to_display"
                        v-bind:key="report.id"
                        v-bind:value="report.id"
                    >
                        {{ report.label }}
                    </option>
                </select>
                <i
                    class="fa-solid fa-circle-notch fa-spin roadmap-widget-configuration-filter-loading"
                    aria-hidden="true"
                    v-if="is_loading_reports"
                ></i>
                <p class="tlp-text-danger" v-if="load_reports_error">
                    {{ load_reports_error }}
                </p>
            </div>
        </blockquote>
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
                <option value="week">
                    {{ $gettext("Week") }}
                </option>
                <option value="month">
                    {{ $gettext("Month") }}
                </option>
                <option value="quarter">
                    {{ $gettext("Quarter") }}
                </option>
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
import type { ReportDefinition, Tracker } from "../type";
import type { ListPicker } from "@tuleap/list-picker";
import { createListPicker } from "@tuleap/list-picker";
import type { TimeScale } from "../../../roadmap-widget/src/type";
import { useGettext } from "vue3-gettext";
import { getAllJSON, uri } from "@tuleap/fetch-result";
import { ok } from "neverthrow";

const trackers_picker = ref<HTMLSelectElement | null>(null);

interface AppProps {
    widget_id: number;
    title: string;
    trackers: Tracker[];
    selected_tracker_ids: number[];
    selected_filter_report_id: number | "";
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

const cached_reports = ref<Map<number, ReportDefinition[]>>(new Map());
const reports_to_display = ref<ReportDefinition[]>([]);
const is_loading_reports = ref(false);
const load_reports_error = ref<string | null>(null);
const user_selected_filter_report_id = ref<number | "">(props.selected_filter_report_id);

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
    loadReportsAccordingToSelectedTracker();
});

onUnmounted((): void => {
    list_picker?.destroy();
});

watch(user_selected_lvl1_iteration_tracker_id, (): void => {
    if (!user_selected_lvl1_iteration_tracker_id.value) {
        user_selected_lvl2_iteration_tracker_id.value = "";
    }
});
watch(user_selected_tracker_ids, loadReportsAccordingToSelectedTracker);

const title_id = computed((): string => "title-" + props.widget_id);

const progress_of_id = computed((): string => "roadmap-tracker-" + props.widget_id);

const filter_report_id = computed((): string => "roadmap-filter-report-" + props.widget_id);

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

function loadReportsAccordingToSelectedTracker(): void {
    const selected_trackers = user_selected_tracker_ids.value;
    if (selected_trackers.length !== 1) {
        return;
    }

    const selected_tracker_id = selected_trackers[0];
    const reports = cached_reports.value.get(selected_tracker_id);
    if (reports !== undefined) {
        reports_to_display.value = reports;
        return;
    }

    is_loading_reports.value = true;
    reports_to_display.value = [];
    load_reports_error.value = null;

    getAllJSON<ReportDefinition[], ReportDefinition>(
        uri`/api/v1/trackers/${selected_tracker_id}/tracker_reports`,
        {
            params: { limit: 1000, offset: 0 },
        }
    )
        .map((reports: readonly ReportDefinition[]) =>
            reports.reduce((public_reports: ReportDefinition[], report) => {
                if (report.is_public) {
                    public_reports.push(report);
                }

                return public_reports;
            }, [])
        )
        .andThen((reports: ReportDefinition[]) => {
            is_loading_reports.value = false;

            return ok(reports);
        })
        .match(
            (reports: ReportDefinition[]) => {
                cached_reports.value.set(selected_tracker_id, reports);
                reports_to_display.value = reports;
            },
            () => {
                load_reports_error.value = $gettext(
                    "An error occurred while loading the reports for the filter"
                );
            }
        );
}
</script>

<style lang="scss">
@use "@tuleap/list-picker/style";

.roadmap-widget-configuration-separator {
    margin: var(--tlp-large-spacing) calc(-1 * var(--tlp-medium-spacing)) var(--tlp-medium-spacing);
    border-top-width: 1px;
}

.roadmap-widget-configuration-subtitle {
    color: var(--tlp-dark-color);
    font-size: 1rem;
}

.roadmap-widget-configuration-filter {
    display: inline-block;
}

.roadmap-widget-configuration-filter-loading {
    margin: 0 0 0 var(--tlp-small-spacing);
}
</style>
