<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    <div class="tlp-framed">
        <div class="tlp-pane">
            <div class="tlp-pane-container">
                <tuleap-pull-request-title v-bind:pull_request_id="pull_request_id" />
                <changes-tabs />
                <section class="tlp-pane-section">
                    <template v-if="!is_loading">
                        <div class="selectors" v-if="selected_file && files.length > 0">
                            <files-selector
                                v-bind:files="files"
                                v-bind:selected_file="selected_file"
                                v-on:file-selected="displaySelectedFile"
                            />
                            <file-diff-type-selector
                                v-bind:current_diff_mode="current_diff_mode"
                                v-on:diff-mode-changed="toggleDiffMode"
                            />
                        </div>
                        <div
                            v-else-if="files.length === 0 && !loading_error"
                            class="tlp-alert-warning"
                            data-test="no-changes-warning"
                        >
                            {{
                                $gettext(
                                    "It seems that the pull request does not have any changes.",
                                )
                            }}
                        </div>
                        <div
                            v-if="loading_error !== null"
                            class="tlp-alert-danger"
                            data-test="error-message"
                        >
                            {{ error_message }}
                        </div>
                    </template>
                    <selectors-skeletons v-else />
                </section>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { provide, computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useGettext } from "vue3-gettext";
import type { Fault } from "@tuleap/fault";
import { strictInject } from "@tuleap/vue-strict-inject";
import { extractPullRequestIdFromRouteParams } from "../router/pull-request-id-extractor";
import { extractFilePathFromRouteParams } from "../router/pull-request-file-name-extractor";
import {
    getFiles,
    getUserPreferenceForDiffDisplayMode,
    type PullRequestFile,
} from "../api/rest-querier";
import { CURRENT_USER_ID_KEY, PULL_REQUEST_ID_KEY } from "../constants";
import type { PullRequestDiffMode } from "./file-diffs/diff-modes";
import { SIDE_BY_SIDE_DIFF } from "./file-diffs/diff-modes";
import FilesSelector from "./files-selector/FilesSelector.vue";
import ChangesTabs from "./ChangesTabs.vue";
import SelectorsSkeletons from "./SelectorsSkeletons.vue";
import FileDiffTypeSelector from "./file-diffs/FileDiffTypeSelector.vue";

import "@tuleap/plugin-pullrequest-title";

const { $gettext, interpolate } = useGettext();
const route = useRoute();
const router = useRouter();

const pull_request_id = extractPullRequestIdFromRouteParams(route.params);
const current_file_path = extractFilePathFromRouteParams(route.params);
const is_loading = ref(true);
const loading_error = ref<Fault | null>(null);
const files = ref<readonly PullRequestFile[]>([]);
const selected_file = ref<PullRequestFile | undefined>();
const current_diff_mode = ref<PullRequestDiffMode>(SIDE_BY_SIDE_DIFF);

const user_id = strictInject(CURRENT_USER_ID_KEY);

provide(PULL_REQUEST_ID_KEY, pull_request_id);

const error_message = computed((): string => {
    if (loading_error.value === null) {
        return "";
    }

    return interpolate($gettext("An error occurred: %{fault}"), { fault: loading_error.value });
});

const displaySelectedFile = (file: PullRequestFile): void => {
    router.replace({ params: { file_path: file.path } });

    selected_file.value = file;
};

const toggleDiffMode = (mode: PullRequestDiffMode): void => {
    current_diff_mode.value = mode;
};

const handleFault = (fault: Fault): void => {
    loading_error.value = fault;
    is_loading.value = false;
};

Promise.all([
    getUserPreferenceForDiffDisplayMode(user_id).match((mode) => {
        current_diff_mode.value = mode;
    }, handleFault),
    getFiles(pull_request_id).match((files_collection) => {
        files.value = files_collection;

        if (files_collection.length > 0) {
            selected_file.value = current_file_path.match(
                (file_path: string) => {
                    const current_file = files_collection.find((file) => {
                        return file.path === file_path;
                    });

                    return current_file ?? files_collection[0];
                },
                () => {
                    return files_collection[0];
                },
            );
        }
    }, handleFault),
]).finally(() => {
    is_loading.value = false;
});
</script>

<style scoped lang="scss">
.tlp-framed,
.tlp-pane-container {
    display: flex;
    flex: 1;
    flex-direction: column;
}

.tlp-pane {
    flex: 1;
    margin: 0;
}

.tlp-pane-section {
    display: flex;
}

.tlp-alert-danger,
.tlp-alert-warning {
    flex: 1;
}

.selectors {
    display: flex;
    flex: 1;
    gap: var(--tlp-small-spacing);
}
</style>
