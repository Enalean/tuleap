import type { VueConstructor, PluginObject } from "vue";
import type { DirectiveConfig, MinimalDOMPurifyConfig } from "./dompurify-html";
import { buildDirective } from "./dompurify-html";
export type { DirectiveConfig, MinimalDOMPurifyConfig };

const vueDompurifyHTMLPlugin: PluginObject<DirectiveConfig> = {
    install(app: VueConstructor, config: DirectiveConfig = {}): void {
        app.directive("dompurify-html", buildDirective(config));
    },
};
export default vueDompurifyHTMLPlugin;
