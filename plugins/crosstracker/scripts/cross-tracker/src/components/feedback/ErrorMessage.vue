<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div class="tlp-alert-danger cross-tracker-report-error" v-if="error_message.isValue()">
        {{ error_message.unwrapOr("") }}
        <pre
            v-if="code_to_show.isValue()"
        ><code class="code-snippet">{{ code_to_show.unwrapOr("") }}</code></pre>
    </div>
</template>
<script setup lang="ts">
import { computed } from "vue";
import { Option } from "@tuleap/option";
import type { Fault } from "@tuleap/fault";
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

const props = defineProps<{
    fault: Option<Fault>;
    tql_query: string;
}>();

const error_message = computed<Option<string>>(() =>
    props.fault.map((fault) => {
        if ("isArtifactsRetrieval" in fault && fault.isArtifactsRetrieval() === true) {
            return $gettext(
                "Error while fetching the artifacts matching the query: %{error}",
                { error: String(fault) },
                true,
            );
        }
        if ("isReportRetrieval" in fault && fault.isReportRetrieval() === true) {
            return $gettext(
                "Error while fetching the report: %{error}",
                { error: String(fault) },
                true,
            );
        }
        if ("isSaveReport" in fault && fault.isSaveReport() === true) {
            return $gettext(
                "Error while saving the report: %{error}",
                { error: String(fault) },
                true,
            );
        }
        if ("isXLSXExport" in fault && fault.isXLSXExport() === true) {
            return $gettext(
                "Error while exporting the report as .xlsx: %{error}",
                { error: String(fault) },
                true,
            );
        }
        return $gettext("An error occurred: %{error}", { error: String(fault) }, true);
    }),
);

interface SyntaxErrorDetails extends Record<string, unknown> {
    readonly line: number;
    readonly column: number;
}

const isSyntaxErrorDetails = (
    record: Record<string, unknown> | string | boolean,
): record is SyntaxErrorDetails =>
    typeof record === "object" && "line" in record && "column" in record;

const code_to_show = computed(
    (): Option<string> =>
        props.fault.andThen((fault) => {
            if (
                ("isArtifactsRetrieval" in fault &&
                    fault.isArtifactsRetrieval() &&
                    "getDetails" in fault) === false
            ) {
                return Option.nothing();
            }
            const details = fault.getDetails();
            if (!isSyntaxErrorDetails(details)) {
                return Option.nothing();
            }
            if (details.line <= 0) {
                return Option.nothing();
            }
            const query = props.tql_query;
            const lines = query.split("\n");
            if (lines.length < details.line) {
                return Option.nothing();
            }
            const line = lines[details.line - 1];
            if (line.length < details.column) {
                return Option.nothing();
            }
            const spaces = " ".repeat(details.column - 1);
            return Option.fromValue(`${line}\n${spaces}^`);
        }),
);
</script>

<style scoped lang="scss">
.code-snippet {
    background: none;
}

.cross-tracker-report-error {
    margin: var(--tlp-medium-spacing) var(--tlp-medium-spacing) 0;
}
</style>
