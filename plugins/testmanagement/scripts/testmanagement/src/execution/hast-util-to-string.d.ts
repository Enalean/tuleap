declare module "hast-util-to-string" {
    import { Node } from "unist";

    function toString(node: Node): string;

    export = toString;
}
