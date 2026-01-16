<!--
  - Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
    <div class="assistant-ai-pane">
        <div class="tlp-pane-container assistant-ai-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title assistant-ai-title">
                    <a-i-icon class="tlp-pane-title-icon" />
                    {{ $gettext("AI query assistant") }}
                </h1>
            </div>
            <section class="tlp-pane-section assistant-ai-chatbox-container">
                <template v-for="(message, index) in messages" v-bind:key="index">
                    <div
                        v-if="message.role === USER_ROLE"
                        class="tlp-card tlp-card-inactive assistant-ai-user-prompt-question-card"
                    >
                        {{ message.content }}
                    </div>
                    <div class="assistant-ai-answer" v-else-if="message.role == ASSISTANT_ROLE">
                        <tlp-syntax-highlighting v-if="message.tql_query !== ''">
                            <pre
                                class="tql-code"
                            ><code class="language-tql">{{ message.tql_query }}</code><!--
                                --><button
                                    type="button"
                                    class="tlp-button-primary tlp-button-mini tlp-button-outline tql-apply-button"
                                    v-on:click="applyAssistantTQLQuery(message)"
                                >{{ $gettext('Apply') }}</button><!--
                            --></pre>
                        </tlp-syntax-highlighting>
                        <div v-dompurify-html="message.explanations"></div>
                    </div>
                </template>
                <div v-if="is_loading" class="assistant-ai-loader-container">
                    <i class="fa-solid fa-fw fa-circle fa-beat-fade" aria-hidden="true"></i>
                    <i
                        class="fa-solid fa-fw fa-circle fa-beat-fade assistant-ai-loader-second-dot"
                        aria-hidden="true"
                    ></i>
                    <i
                        class="fa-solid fa-fw fa-circle fa-beat-fade assistant-ai-loader-third-dot"
                        aria-hidden="true"
                    ></i>
                </div>
                <div class="tlp-alert-danger" v-if="error_message !== ''">
                    <p class="tlp-alert-title">{{ $gettext("Something unexpected occurred") }}</p>
                    {{ error_message }}
                </div>
            </section>
            <section class="tlp-pane-section">
                <form
                    class="assistant-ai-user-prompt-container"
                    v-on:submit.prevent="handlePromptSubmission"
                >
                    <div class="tlp-form-element assistant-ai-user-prompt-input-container">
                        <input
                            type="text"
                            class="tlp-input"
                            v-model="prompt_user_input"
                            required
                            maxlength="500"
                            v-bind:disabled="is_loading"
                            v-bind:placeholder="$gettext('Describe what you are looking for...')"
                        />
                        <p class="tlp-text-info">
                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                            {{
                                $gettext(
                                    "All exchanges are recorded and will be reviewed by the Tuleap Team to improve the query generation experience.",
                                )
                            }}
                        </p>
                    </div>
                    <button
                        type="submit"
                        class="tlp-button-primary"
                        v-bind:disabled="is_loading"
                        v-bind:title="$gettext('Submit')"
                    >
                        <i class="fa-solid fa-paper-plane" role="img"></i>
                    </button>
                </form>
            </section>
        </div>
    </div>
</template>
<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import AIIcon from "./AIIcon.vue";
import { ref } from "vue";
import { postJSON, uri } from "@tuleap/fetch-result";
import { EMITTER, WIDGET_ID } from "../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import { SEND_TQL_QUERY_FROM_CHATBOT_EVENT } from "../helpers/widget-events";

const { $gettext } = useGettext();

const prompt_user_input = ref<string>("");
const error_message = ref<string>("");
const is_loading = ref<boolean>(false);

const USER_ROLE = "user";
const ASSISTANT_ROLE = "assistant";

interface UserMessage {
    role: typeof USER_ROLE;
    content: string;
}

interface AssistantMessage {
    role: typeof ASSISTANT_ROLE;
    tql_query: string;
    explanations: string;
    title: string;
}
type CrossTrackerEndpointAssistantMessage = Omit<AssistantMessage, "role"> & {
    thread_id: string;
};

const messages = ref<Array<Readonly<UserMessage | AssistantMessage>>>([]);

const widget_id = strictInject(WIDGET_ID);
const emitter = strictInject(EMITTER);

const thread_id = ref<null | string>(null);

async function handlePromptSubmission(): Promise<void> {
    const submitted_prompt = prompt_user_input.value;
    prompt_user_input.value = "";
    error_message.value = "";
    messages.value.push({ role: USER_ROLE, content: submitted_prompt });

    is_loading.value = true;

    const response = await postJSON<CrossTrackerEndpointAssistantMessage>(
        uri`/api/crosstracker_assistant/${widget_id}/helper`,
        {
            message: submitted_prompt,
            thread_id: thread_id.value,
        },
    );

    is_loading.value = false;

    response.match(
        (resp) => {
            thread_id.value = resp.thread_id;
            messages.value.push({
                role: ASSISTANT_ROLE,
                ...resp,
            });
        },
        (e) => {
            error_message.value = e.toString();
            prompt_user_input.value = submitted_prompt;
            messages.value.pop();
        },
    );
}

function applyAssistantTQLQuery(message: AssistantMessage): void {
    emitter.emit(SEND_TQL_QUERY_FROM_CHATBOT_EVENT, {
        title: message.title,
        tql_query: message.tql_query,
    });
}
</script>
<style scoped lang="scss">
.assistant-ai-pane {
    display: flex;
    width: 50%;
    margin: 0 var(--tlp-medium-spacing) var(--tlp-medium-spacing);
    border-left: 1px solid var(--tlp-border-color);
}

.tlp-pane-section {
    padding-right: 0;
    border-bottom: 0;
}

.assistant-ai-container {
    display: block;
}

.assistant-ai-title {
    display: flex;
    align-items: center;
}

.assistant-ai-chatbox-container {
    display: flex;
    flex: 1 0 auto;
    flex-direction: column;
    justify-content: flex-end;

    &:empty {
        padding: 0;
    }
}

.assistant-ai-user-prompt-container {
    display: flex;
    gap: 10px;
}

.assistant-ai-user-prompt-input-container {
    width: 100%;
}

.assistant-ai-answer,
.assistant-ai-user-prompt-question-card {
    margin: 0 0 var(--tlp-medium-spacing) auto;

    &:last-child {
        margin: 0;
    }
}

.assistant-ai-user-prompt-question-card {
    max-width: fit-content;
    border: 0;
    box-shadow: none;
}

.tql-code {
    position: relative;
    margin: 0 0 var(--tlp-small-spacing);

    // 26px is height of tlp-button-mini (24px) + 2px for safety
    padding: 5px 4px 26px calc(var(--tlp-medium-spacing) / 2);
}

.tql-apply-button {
    position: absolute;
    right: 4px;
    bottom: 4px;
}

.assistant-ai-loader-container {
    display: flex;
    color: var(--tlp-dimmed-color);
    font-size: 0.5rem;
    gap: 4px;
}

.assistant-ai-loader-second-dot {
    --fa-animation-delay: 100ms;
}

.assistant-ai-loader-third-dot {
    --fa-animation-delay: 200ms;
}
</style>
