# PluginAddMarkAfterEnter

This plugin allows to add a given mark on the content right before the cursor after the user has pressed the Enter key.

It holds a `Map<Regexp, MarkAfterEnterKeyBuilder>` to be able to create the marks.

When the user presses the [Enter] key, It will run all the RegExps in its map on the text content in the node before the cursor.
If a RegExp matches, it will then append the Mark built by the dedicated Mark builder to the text matching the RegExp.

## Why?

An [InputRule](https://github.com/ProseMirror/prosemirror-inputrules) is not designed to work with the [Enter] key.
It listens to the `handleTextInputText` hook and analyses what have been typed right before the cursor.
When the user presses the [Enter] key, `handleTextInputText` is not called: the input rule is never triggered.

## How to use?

Add a new entry in the Map built in [build-regexp-to-mark-builder-map](./build-regexp-to-mark-builder-map.ts).

An entry requires two things:
1. A `RegExp` to match the text parts on which to add the target Mark
2. A `MarkAfterEnterKeyBuilder` to be able to create the target mark on the text parts matching the RegExp if allowed.
