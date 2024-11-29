import DOMPurify from "dompurify";
import type { DirectiveBinding, DirectiveHook, ObjectDirective } from "vue";

export interface MinimalDOMPurifyConfig {
    ADD_ATTR?: string[] | undefined;
    ADD_DATA_URI_TAGS?: string[] | undefined;
    ADD_TAGS?: string[] | undefined;
    ALLOW_DATA_ATTR?: boolean | undefined;
    ALLOWED_ATTR?: string[] | undefined;
    ALLOWED_TAGS?: string[] | undefined;
    FORBID_ATTR?: string[] | undefined;
    FORBID_CONTENTS?: string[] | undefined;
    FORBID_TAGS?: string[] | undefined;
    ALLOWED_URI_REGEXP?: RegExp | undefined;
    ALLOW_UNKNOWN_PROTOCOLS?: boolean | undefined;
    USE_PROFILES?:
        | false
        | {
              mathMl?: boolean | undefined;
              svg?: boolean | undefined;
              svgFilters?: boolean | undefined;
              html?: boolean | undefined;
          }
        | undefined;
    CUSTOM_ELEMENT_HANDLING?: {
        tagNameCheck?: RegExp | ((tagName: string) => boolean) | null | undefined;
        attributeNameCheck?: RegExp | ((lcName: string) => boolean) | null | undefined;
        allowCustomizedBuiltInElements?: boolean | undefined;
    };
}

export interface DirectiveConfig {
    default?: MinimalDOMPurifyConfig | undefined;
}

export function buildDirective(config: DirectiveConfig = {}): ObjectDirective<HTMLElement> {
    const updateComponent: DirectiveHook = function (
        el: HTMLElement,
        binding: DirectiveBinding<HTMLElement>,
    ): void {
        const current_value = binding.value;
        if (binding.oldValue === current_value) {
            return;
        }
        const current_value_string = `${current_value}`;
        const defaultConfig = config.default ?? {};
        el.innerHTML = DOMPurify.sanitize(current_value_string, defaultConfig);
    };

    return {
        inserted: updateComponent,
        update: updateComponent,
        unbind: (el: HTMLElement): void => {
            el.innerHTML = "";
        },
    };
}
