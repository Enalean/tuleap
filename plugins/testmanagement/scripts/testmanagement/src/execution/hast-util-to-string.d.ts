declare module "hast-util-to-string" {
    import type { Node } from "unist";

    function toString(node: Node): string;

    export = toString;
}
