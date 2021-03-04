// Until this PR is not integrated, we cannot use @types/mermaid:
// https://github.com/DefinitelyTyped/DefinitelyTyped/pull/51627

declare module "mermaid" {
    type Theme = "default" | "forest" | "dark" | "neutral";

    interface FlowChartConfig {
        htmlLabels?: boolean;
    }

    interface Config {
        startOnLoad?: boolean;
        securityLevel?: string;
        theme?: Theme;
        flowchart?: FlowChartConfig;
        secure?: string[];
        fontFamily?: string;
    }

    const initialize: (config: Config) => void;
    const render: (
        id: string,
        txt: string,
        cb?: (svgCode: string, bindFunctions: (element: Element) => void) => void,
        container?: HTMLElement
    ) => string;
}
