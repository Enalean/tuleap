/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { TemplateResult } from "lit-html";
import { html, render } from "lit-html";
import DOMPurify from "dompurify";
import { postJSON, uri } from "@tuleap/fetch-result";
import { parse } from "marked";
import type { Emitter } from "mitt";
import type { Events } from "./helpers/widget-events";
import { SEND_TQL_QUERY_FROM_CHATBOT_EVENT } from "./helpers/widget-events";
import { selectOrThrow } from "@tuleap/dom";
import type { VueGettextProvider } from "./helpers/vue-gettext-provider";

export type WidgetAIPromptInWidget = {
    create(): void;
};

type Message = {
    role: string;
    content: string;
};

const USER_ROLE = "user";
const ASSISTANT_ROLE = "assistant";

export const WidgetAIPromptInWidget = (
    root_element: HTMLElement,
    emitter: Emitter<Events>,
    widget_id: number,
    gettext_provider: VueGettextProvider,
): WidgetAIPromptInWidget => {
    let content = "";
    const messages: Array<Message> = [];

    const handleInput = (event: Event): void => {
        if (!(event.target instanceof HTMLInputElement)) {
            return;
        }
        content = event.target.value;
    };
    const handleSubmit = async (): Promise<void> => {
        const prompt_section = document.getElementById("prompt-result-section");
        const question = document.createElement("div");
        question.innerHTML = `<div class="assistant-ai-user-prompt-question-card tlp-card tlp-card-inactive">${DOMPurify.sanitize(content)}</div>`;

        prompt_section?.insertAdjacentElement("beforeend", question);

        const loader_element = loaderElement();
        prompt_section?.insertAdjacentElement("beforeend", loader_element);

        const ai_response_element = document.createElement("div");

        messages.push({
            role: USER_ROLE,
            content,
        });

        const response = await postJSON<{ response: string }>(
            uri`/api/crosstracker_assistant/${widget_id}/helper`,
            {
                messages,
            },
        );
        response.match(
            (resp) => {
                const ai_response_fragment: DocumentFragment = DOMPurify.sanitize(
                    `${parse(resp.response)} <br/>`,
                    {
                        RETURN_DOM_FRAGMENT: true,
                    },
                );
                messages.push({
                    role: ASSISTANT_ROLE,
                    content: resp.response,
                });

                ai_response_element.append(ai_response_fragment);

                const all_tql_markup_elements = getAllCodeMarkupNodes(ai_response_element);

                all_tql_markup_elements?.forEach((tql_markup_element) => {
                    const code_markup_container =
                        wrapCodeElementToHaveActionButtonsAtCodeElementHover(tql_markup_element);

                    const given_tql_query =
                        tql_markup_element.textContent === null
                            ? ""
                            : tql_markup_element.textContent;
                    code_markup_container?.insertAdjacentElement(
                        "beforeend",
                        codeActionComponent(given_tql_query.trimEnd()),
                    );
                });
                loader_element.remove();
            },
            (e) => {
                ai_response_element.innerHTML = DOMPurify.sanitize(e.toString());
                loader_element.remove();
            },
        );
        resetUserPromptInput();
        prompt_section?.insertAdjacentElement("beforeend", ai_response_element);
    };

    function resetUserPromptInput(): void {
        const input_element: HTMLInputElement = selectOrThrow(
            root_element,
            "#prompt-input",
            HTMLInputElement,
        );
        input_element.value = "";
        content = "";
    }

    function codeActionComponent(tql_query: string): HTMLElement {
        const code_action_element = document.createElement("div");
        code_action_element.classList.add("code-action-component-display");
        code_action_element.innerHTML = `
    <button type="button" class="tlp-button-primary tlp-button-mini tlp-button-outline">Apply</button>`;
        code_action_element.addEventListener("click", function () {
            emitter.emit(SEND_TQL_QUERY_FROM_CHATBOT_EVENT, { tql_query });
        });
        return code_action_element;
    }

    // Mistral can return SQL language instead of TQL language
    function getAllCodeMarkupNodes(ai_response_template: HTMLElement): NodeList {
        let all_tql_markup_elements = ai_response_template?.querySelectorAll(
            "code[class=language-sql]",
        );
        if (all_tql_markup_elements?.length === 0) {
            all_tql_markup_elements = ai_response_template?.querySelectorAll(
                "code[class=language-tql]",
            );
        }
        return all_tql_markup_elements;
    }

    function loaderElement(): HTMLElement {
        const loader_element = document.createElement("div");
        loader_element.classList.add("assistant-ai-loader-container");
        loader_element.innerHTML = `<i class="fa-solid fa-fw fa-circle fa-beat-fade" aria-hidden="true"></i>
            <i class="fa-solid fa-fw fa-circle fa-beat-fade assistant-ai-loader-second-dot" aria-hidden="true"></i>
            <i class="fa-solid fa-fw fa-circle fa-beat-fade assistant-ai-loader-third-dot" aria-hidden="true"></i>`;
        return loader_element;
    }

    function wrapCodeElementToHaveActionButtonsAtCodeElementHover(
        tql_markup_element: Node,
    ): HTMLElement {
        const tql_markup_element_parent = tql_markup_element.parentNode;
        if (tql_markup_element_parent === null) {
            throw Error(`Could not find parent node for ${tql_markup_element.nodeName}`);
        }

        const code_markup_container = document.createElement("div");
        code_markup_container.classList.add("markup-code-container");

        tql_markup_element_parent?.parentNode?.insertBefore(
            code_markup_container,
            tql_markup_element_parent,
        );
        code_markup_container.appendChild(tql_markup_element_parent);
        return code_markup_container;
    }

    function handleInputKeyEvent(event: Event): void {
        if (
            event instanceof KeyboardEvent &&
            event.key === "Enter" &&
            event.target instanceof HTMLInputElement
        ) {
            handleSubmit();
            event.target.value = "";
            content = "";
        }
    }

    function chatBaseTemplate(): TemplateResult {
        return html`
            <div class="tlp-pane-container assistant-ai-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title assistant-ai-title">
                        <svg
                            height="20"
                            viewBox="0 0 24 20"
                            xmlns="http://www.w3.org/2000/svg"
                            class="tlp-pane-title-icon"
                        >
                            <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M12.5123 6.43476C11.4283 7.92409 10.7645 9.15871 10.3164 10.94C10.2643 11.1482 10.216 11.3565 10.1732 11.5647C9.05761 10.6852 8.30457 9.84854 7.79883 9.10665C7.8416 8.90026 7.89366 8.69572 7.94758 8.48749C8.20788 7.51504 8.48492 6.94235 8.94233 6.07961C8.9888 5.99593 9.03157 5.91413 9.0762 5.83417C9.13941 5.72261 9.20449 5.61105 9.26956 5.50506C9.32162 5.4214 9.37183 5.34144 9.42389 5.26335C9.60052 4.99189 9.78647 4.74087 9.97426 4.51775C10.1286 4.33739 10.3424 4.25 10.56 4.25C10.5878 4.25 10.6176 4.25372 10.6455 4.25558C10.6659 4.25558 10.6845 4.26116 10.705 4.26488C10.731 4.26674 10.7533 4.27603 10.7794 4.28161C10.8017 4.28719 10.8258 4.29648 10.8482 4.30764C10.8965 4.32437 10.943 4.35041 10.9876 4.3783C11.0266 4.40433 11.0676 4.43594 11.1047 4.47312C11.1271 4.49544 11.1475 4.51961 11.168 4.54192C11.2702 4.67022 11.3855 4.81525 11.5045 4.97515C11.5473 5.03279 11.59 5.08857 11.6328 5.14993C11.7555 5.31542 11.8838 5.49205 12.0158 5.678C12.0642 5.74679 12.1107 5.81744 12.159 5.88995C12.278 6.0666 12.3951 6.24881 12.5123 6.43476Z"
                                fill="#FF6E30"
                            ></path>
                            <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M10.4431 19.2415C6.70394 18.825 4.77207 15.7589 4.35557 13.6616C4.04692 12.0997 4.15848 9.66582 5.02308 8.02958C5.10303 7.88083 5.22947 7.78973 5.36892 7.75254C5.49721 7.71907 5.63666 7.73208 5.75752 7.78973C5.88582 7.84923 5.99366 7.96079 6.04573 8.1207C6.18331 8.55207 6.41015 9.08569 6.78574 9.69C6.9847 10.0061 7.22455 10.3408 7.51274 10.6922C8.08915 11.395 8.86636 12.1536 9.90759 12.929C9.89085 13.0517 9.87412 13.1744 9.86111 13.2971C9.82763 13.5891 9.80347 13.8847 9.79046 14.1785C9.71049 15.731 9.87598 17.3115 10.2869 18.8529C10.3241 18.9905 10.378 19.1188 10.4431 19.2378V19.2415Z"
                                fill="#FF6E30"
                            />
                            <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M18.4242 12.5143C18.4094 12.6221 18.3908 12.7318 18.3703 12.8359L18.361 12.888C18.3443 12.9642 18.3294 13.0423 18.309 13.1186C18.2811 13.2413 18.2513 13.364 18.2197 13.483C18.216 13.4886 18.216 13.4942 18.2141 13.4997C18.1881 13.6001 18.1602 13.6968 18.1286 13.7935C18.0635 13.9999 17.991 14.2044 17.9111 14.4034C17.8832 14.4778 17.8516 14.5484 17.82 14.6209C17.7605 14.7641 17.6935 14.9036 17.6266 15.0412C17.5894 15.1174 17.5504 15.1955 17.5076 15.2717C17.4481 15.3833 17.3849 15.4948 17.3198 15.6064C17.277 15.6771 17.2343 15.7496 17.1878 15.8184C17.1227 15.9206 17.0558 16.021 16.9851 16.1214C16.9368 16.1921 16.8847 16.2646 16.8345 16.3334C16.775 16.4171 16.7118 16.497 16.6467 16.5751C16.5835 16.6551 16.5184 16.735 16.4496 16.8113C16.3845 16.8875 16.3158 16.9656 16.247 17.04C16.1875 17.105 16.1279 17.1683 16.0647 17.2278C16.0071 17.2854 15.9532 17.3412 15.8955 17.3932C15.89 17.3988 15.8844 17.4044 15.8788 17.41C15.8769 17.4137 15.8732 17.4156 15.8695 17.4193C15.8119 17.4732 15.7561 17.5253 15.6985 17.5736C15.6408 17.6275 15.585 17.6759 15.5274 17.7242C15.5125 17.7354 15.4995 17.7465 15.4846 17.7577C15.4196 17.8153 15.3526 17.8655 15.2857 17.9176C15.1908 17.9919 15.0942 18.0626 14.9938 18.1314C14.9826 18.1407 14.9714 18.1481 14.9603 18.1574C14.8766 18.2151 14.7948 18.2708 14.7093 18.3229C14.6888 18.3378 14.6702 18.3508 14.6498 18.3638C14.5977 18.3917 14.5494 18.4233 14.4992 18.4549C14.4471 18.4828 14.3988 18.5126 14.3504 18.5367C14.2668 18.5832 14.1868 18.626 14.105 18.665C14.025 18.7078 13.9414 18.745 13.8595 18.7822C13.8168 18.7989 13.7759 18.8194 13.7331 18.8361C13.6922 18.8566 13.6495 18.8733 13.6067 18.8882C13.272 19.022 12.9336 19.1243 12.5915 19.1931C12.5022 19.2098 12.4148 19.2247 12.3256 19.2396C12.2865 19.2452 12.2456 19.2489 12.2066 19.2489C12.1601 19.2489 12.1173 19.2452 12.0727 19.2377C12.0523 19.234 12.03 19.2284 12.0076 19.2228C11.9872 19.2173 11.9649 19.2117 11.9444 19.2024C11.924 19.1968 11.9017 19.1875 11.8812 19.1801C11.8533 19.1689 11.8236 19.1541 11.7957 19.1336C11.7845 19.1299 11.7734 19.1243 11.7641 19.1169C11.738 19.102 11.712 19.0852 11.6897 19.0648C11.673 19.0499 11.6562 19.0332 11.6413 19.0183C11.619 19.0016 11.5986 18.9774 11.5781 18.9551C11.5577 18.9328 11.5409 18.9068 11.5242 18.8844C11.4982 18.8473 11.4759 18.8082 11.4591 18.7655C11.4387 18.7227 11.4219 18.6799 11.4108 18.6334C11.3717 18.4847 11.3346 18.3359 11.2992 18.1853C11.2713 18.0682 11.2453 17.9511 11.223 17.832C11.2063 17.7465 11.1895 17.661 11.1709 17.5717C11.1375 17.3895 11.1077 17.2036 11.0798 17.0176C11.0743 16.9953 11.0705 16.9749 11.0687 16.9526C11.065 16.9358 11.0631 16.9154 11.0631 16.8986C11.0519 16.8336 11.0426 16.7666 11.0371 16.7015C11.0315 16.6848 11.0315 16.6644 11.0278 16.6476C11.0166 16.5565 11.0055 16.4617 10.9961 16.3706C10.9906 16.3297 10.9869 16.2906 10.985 16.2516C10.9757 16.1512 10.9645 16.0527 10.959 15.9522C10.959 15.9299 10.9553 15.9095 10.9553 15.889C10.9534 15.863 10.9497 15.837 10.9497 15.8128V15.8091C10.9404 15.6715 10.9329 15.5357 10.9292 15.3981C10.9236 15.2364 10.9199 15.069 10.9199 14.9036V14.8887C10.9199 14.8199 10.9199 14.7455 10.9236 14.6767C10.9236 14.6023 10.9274 14.5335 10.9292 14.4629C10.9255 14.4369 10.9292 14.4108 10.9292 14.3867C10.9404 14.104 10.9608 13.8195 10.9887 13.5332C10.9924 13.5128 10.9943 13.4923 10.998 13.4737C11.0017 13.4328 11.0073 13.3938 11.0092 13.3566C11.0129 13.3175 11.0185 13.2803 11.024 13.2394C11.0333 13.1595 11.0445 13.0795 11.0557 13.0033C11.0594 12.9828 11.0612 12.9605 11.065 12.9401C11.0798 12.8322 11.0966 12.7225 11.117 12.6147C11.1207 12.5868 11.1263 12.5608 11.1319 12.5329C11.1523 12.4194 11.1709 12.3079 11.1951 12.1963C11.2211 12.0699 11.2472 11.9453 11.2751 11.8189C11.2806 11.7984 11.2844 11.778 11.2899 11.7594C11.3104 11.685 11.3271 11.6088 11.3476 11.5307C11.3792 11.4117 11.4108 11.2908 11.4443 11.1718C11.4759 11.064 11.5075 10.958 11.5409 10.8502C11.5577 10.7925 11.5744 10.7386 11.593 10.6847C11.6097 10.627 11.6302 10.5712 11.6506 10.5155C11.6785 10.4318 11.7083 10.35 11.7362 10.2663C11.7641 10.1919 11.7938 10.1157 11.8217 10.0376C11.8533 9.95767 11.8868 9.87399 11.9184 9.7959V9.79218C11.924 9.77544 11.9333 9.75871 11.9407 9.74012C11.9686 9.66574 12.0039 9.59137 12.0355 9.517C12.082 9.40916 12.1304 9.30317 12.1806 9.19719C12.201 9.15628 12.2196 9.11351 12.2401 9.07074C12.2605 9.03171 12.2773 8.98708 12.2996 8.94431C12.3795 8.77883 12.465 8.61335 12.5562 8.44786C12.5989 8.36419 12.6473 8.28238 12.6937 8.1987C12.703 8.18383 12.7086 8.17268 12.7161 8.15967C12.7216 8.1485 12.7272 8.13921 12.7328 8.12805C12.7793 8.05183 12.8276 7.96815 12.876 7.89192C12.9187 7.82125 12.9615 7.74874 13.008 7.67809C13.0507 7.613 13.0916 7.54607 13.1344 7.481C13.1865 7.40663 13.2348 7.33224 13.285 7.25787C13.2887 7.25416 13.2887 7.2523 13.2906 7.24858C13.3594 7.14817 13.4282 7.04962 13.4988 6.95107C13.6104 6.79489 13.7275 6.64243 13.8465 6.4881C13.906 6.41372 13.9655 6.33935 14.0269 6.26498C14.0845 6.19431 14.144 6.12181 14.2073 6.05116C14.2705 5.97491 14.3355 5.89683 14.4043 5.82244C14.5326 5.67742 14.6665 5.53053 14.806 5.38921C14.8115 5.38363 14.8208 5.37434 14.8264 5.36876C14.8413 5.35204 14.8543 5.34087 14.8692 5.326C14.8748 5.3167 14.8803 5.31113 14.8896 5.30555C14.8989 5.29626 14.9045 5.2888 14.9119 5.28323C14.9324 5.26278 14.9528 5.24419 14.9715 5.22001C15.0309 5.1568 15.0904 5.0973 15.1574 5.03408C15.2541 4.93925 15.3545 4.84628 15.4549 4.75146C15.5348 4.6808 15.6241 4.62502 15.7152 4.58783C15.7431 4.57854 15.7747 4.5711 15.8044 4.5618H15.81C15.81 4.5618 15.8193 4.55808 15.8249 4.55808C15.836 4.55251 15.8472 4.54879 15.8584 4.54879C15.8844 4.54321 15.9104 4.53949 15.9327 4.53763C15.9439 4.53577 15.9587 4.53205 15.9699 4.53577C16.0536 4.53205 16.1391 4.54135 16.2191 4.5618C16.2525 4.5711 16.2879 4.58225 16.3213 4.59527C16.3474 4.60643 16.3752 4.61758 16.4013 4.63246C16.4385 4.65291 16.4757 4.67522 16.5091 4.70125C16.537 4.72357 16.5668 4.7496 16.5928 4.77563C16.6467 4.82769 16.6932 4.88719 16.7304 4.95599C17.9371 7.10169 18.8203 9.84425 18.4317 12.505L18.4242 12.5143Z"
                                fill="#FF6E30"
                            />
                            <path d="M17.5332 0.25V2.91656V0.25Z" fill="#EE9D00" />
                            <path
                                d="M17.5332 0.25V2.91656"
                                stroke="#EE9D00"
                                stroke-width="0.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <path d="M18.8658 1.58301H16.1992H18.8658Z" fill="#EE9D00" />
                            <path
                                d="M18.8658 1.58301H16.1992"
                                stroke="#EE9D00"
                                stroke-width="0.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M2.00259 3.41274C2.01116 3.36688 2.0355 3.32545 2.07139 3.29564C2.10729 3.26583 2.15248 3.24951 2.19914 3.24951C2.2458 3.24951 2.29099 3.26583 2.32688 3.29564C2.36278 3.32545 2.38712 3.36688 2.39568 3.41274L2.60582 4.52403C2.62075 4.60304 2.65914 4.67571 2.716 4.73257C2.77285 4.78942 2.84553 4.82782 2.92453 4.84274L4.03582 5.05289C4.08169 5.06145 4.12312 5.08579 4.15293 5.12169C4.18274 5.15758 4.19906 5.20277 4.19906 5.24943C4.19906 5.29609 4.18274 5.34128 4.15293 5.37718C4.12312 5.41307 4.08169 5.43741 4.03582 5.44598L2.92453 5.65612C2.84553 5.67104 2.77285 5.70944 2.716 5.76629C2.65914 5.82315 2.62075 5.89582 2.60582 5.97483L2.39568 7.08612C2.38712 7.13198 2.36278 7.17341 2.32688 7.20322C2.29099 7.23303 2.2458 7.24935 2.19914 7.24935C2.15248 7.24935 2.10729 7.23303 2.07139 7.20322C2.0355 7.17341 2.01116 7.13198 2.00259 7.08612L1.79245 5.97483C1.77753 5.89582 1.73913 5.82315 1.68228 5.76629C1.62542 5.70944 1.55275 5.67104 1.47374 5.65612L0.362452 5.44598C0.316585 5.43741 0.275159 5.41307 0.245348 5.37718C0.215537 5.34128 0.199219 5.29609 0.199219 5.24943C0.199219 5.20277 0.215537 5.15758 0.245348 5.12169C0.275159 5.08579 0.316585 5.06145 0.362452 5.05289L1.47374 4.84274C1.55275 4.82782 1.62542 4.78942 1.68228 4.73257C1.73913 4.67571 1.77753 4.60304 1.79245 4.52403L2.00259 3.41274Z"
                                fill="#EE9D00"
                                stroke="#EE9D00"
                                stroke-width="0.4"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <path
                                d="M21.0026 10.4127C21.0112 10.3669 21.0355 10.3255 21.0714 10.2956C21.1073 10.2658 21.1525 10.2495 21.1991 10.2495C21.2458 10.2495 21.291 10.2658 21.3269 10.2956C21.3628 10.3255 21.3871 10.3669 21.3957 10.4127L21.6058 11.524C21.6207 11.603 21.6591 11.6757 21.716 11.7326C21.7729 11.7894 21.8455 11.8278 21.9245 11.8427L23.0358 12.0529C23.0817 12.0615 23.1231 12.0858 23.1529 12.1217C23.1827 12.1576 23.1991 12.2028 23.1991 12.2494C23.1991 12.2961 23.1827 12.3413 23.1529 12.3772C23.1231 12.4131 23.0817 12.4374 23.0358 12.446L21.9245 12.6561C21.8455 12.671 21.7729 12.7094 21.716 12.7663C21.6591 12.8231 21.6207 12.8958 21.6058 12.9748L21.3957 14.0861C21.3871 14.132 21.3628 14.1734 21.3269 14.2032C21.291 14.233 21.2458 14.2493 21.1991 14.2493C21.1525 14.2493 21.1073 14.233 21.0714 14.2032C21.0355 14.1734 21.0112 14.132 21.0026 14.0861L20.7925 12.9748C20.7775 12.8958 20.7391 12.8231 20.6823 12.7663C20.6254 12.7094 20.5527 12.671 20.4737 12.6561L19.3625 12.446C19.3166 12.4374 19.2752 12.4131 19.2453 12.3772C19.2155 12.3413 19.1992 12.2961 19.1992 12.2494C19.1992 12.2028 19.2155 12.1576 19.2453 12.1217C19.2752 12.0858 19.3166 12.0615 19.3625 12.0529L20.4737 11.8427C20.5527 11.8278 20.6254 11.7894 20.6823 11.7326C20.7391 11.6757 20.7775 11.603 20.7925 11.524L21.0026 10.4127Z"
                                fill="#EE9D00"
                                stroke="#EE9D00"
                                stroke-width="0.4"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        ${gettext_provider.$gettext("Ai query assistant")}
                    </h1>
                </div>
                <section
                    id="prompt-result-section"
                    class="tlp-pane-section assistant-ai-chatbox-container"
                ></section>
                <section class="tlp-pane-section assistant-ai-user-prompt-container">
                    <input
                        type="text"
                        id="prompt-input"
                        class="tlp-textarea"
                        @input="${handleInput}"
                        @keydown="${handleInputKeyEvent}"
                        placeholder="${gettext_provider.$gettext(
                            "Describe what you are looking for...",
                        )}"
                    />
                    <button
                        type="button"
                        class="tlp-button-primary"
                        @click="${handleSubmit}"
                        title="Submit"
                    >
                        <i class="fa-solid fa-paper-plane" role="img"></i>
                    </button>
                </section>
            </div>
        `;
    }

    const create = (): void => {
        render(chatBaseTemplate(), root_element);
    };

    return {
        create,
    };
};
