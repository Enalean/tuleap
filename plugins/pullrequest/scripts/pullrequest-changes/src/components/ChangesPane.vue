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
                    <template v-if="!is_loading_files">
                        <files-selector
                            v-if="selected_file && files.length > 0"
                            v-bind:files="files"
                            v-bind:selected_file="selected_file"
                            v-on:file-selected="displaySelectedFile"
                        />
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
                    <app-skeleton v-else />
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
import { extractPullRequestIdFromRouteParams } from "../router/pull-request-id-extractor";
import { extractFilePathFromRouteParams } from "../router/pull-request-file-name-extractor";
import { getFiles, type PullRequestFile } from "../api/rest-querier";
import { PULL_REQUEST_ID_KEY } from "../constants";
import FilesSelector from "./files-selector/FilesSelector.vue";
import ChangesTabs from "./ChangesTabs.vue";

import "@tuleap/plugin-pullrequest-title";
import AppSkeleton from "./AppSkeleton.vue";

const { $gettext, interpolate } = useGettext();
const route = useRoute();
const router = useRouter();

const pull_request_id = extractPullRequestIdFromRouteParams(route.params);
const current_file_path = extractFilePathFromRouteParams(route.params);
const is_loading_files = ref(true);
const loading_error = ref<Fault | null>(null);
const files = ref<readonly PullRequestFile[]>([]);
const selected_file = ref<PullRequestFile | undefined>();

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

getFiles(pull_request_id).match(
    (files_collection) => {
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

        is_loading_files.value = false;
    },
    (fault) => {
        loading_error.value = fault;
        is_loading_files.value = false;
    },
);
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
</style>
