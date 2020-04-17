declare module "unist-util-remove" {
    import { Node } from "unist";

    interface Options {
        readonly cascade?: boolean;
    }

    function remove(ast: Node, test: (node: Node) => boolean): void;
    function remove(ast: Node, opts: Options, test: (node: Node) => boolean): void;

    export = remove;
}
