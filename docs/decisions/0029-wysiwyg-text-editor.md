# Choice of WYSIWYG text editor

* Status: accepted
* Deciders: Marie-Ange GARNIER
* Date: 2024-07-22

Epic: [epic #37946 Requirement Management - part 2][0]

## Context

Requirement management comes with some challenges in terms of text edition. Artidoc has been started with [CKEditor][5], but we know for sure we can't use it in long term view (at least table and real-time are not community plugins).

Doing the implementation shows us a pain point: we do not want an editor with a read and a write mode. We must be able to update our artifact directly without toggling edition.

## Decision Drivers

The requirements for the new editor are:

* support of preformatted text and inline code
* toolbar
* formatting (bold, italic, lists, quote, links, code blocks, inline, superscript...)
* headings (titles, subtitles, etc.)
* must not interfere with browsers' native capabilities like spell check
* support of collaborative edition in real-time
* keyboard shortcuts (like `Ctrl+b` for bold or `*` to create a list)
* images (copy/paste and drag-and-drop)
* anchor/refs ⚠️ warnings have been raised about Tuleap references, see [request #38646 xref edition in TTM comment does not work][1]
* support of emojis
* select text and have the ability to attach a comment to it
* diff support
* tables (including copy/paste)
* Markdown
* HTML paste
* code must be open-source (because Tuleap itself is open-source)

## Considered Options

A few months before we had selected several editors that can cover our needs (if you want to see the details of what was supported for each editor, please see the [artidoc][7])

* [Milkdown][8]
* [Quill][9]
* [ProseMirror][10]
* [Jodit][11]
* [Froala][12]
* [Etherpad][13]
* [Plate][14]
* [TinyMCE][15]
* [Tiptap][16]

We do have a need of high customization, and we know for sure that we'll need to write several plugins in order to fit our needs.
As of today, no editor comes with plugins covering all of our needs.

## Options disqualified by Decision Drivers

* Jodit: no real-time support.
* Froala: not open-source.
* Etherpad: it's a standalone app, we cannot integrate it into Tuleap.
* Plate: brings dependencies to [React][6], real-time demo is not complete.
* TinyMCE & Tiptap: features we need are not open-source.

The three viable editors are Milkdown, Quill and ProseMirror.

## Decision Outcome

Due to our customization and real-time needs we choose to use ProseMirror.

Note: Quill could have been a good choice too, but I did not have the time to test fully editor customization,
I chose to trust [npm-compare][17] who said:
> How to Choose: quill vs prosemirror-view
>
> quill: Choose Quill if you prefer a user-friendly and feature-rich rich text editor with a simple API and a WYSIWYG editing experience. Quill provides a range of built-in modules for handling text formatting, image embedding, and rich media content.
>
> prosemirror-view: Choose ProseMirror View if you require a highly customizable and performant rich text editor with support for collaborative editing and structured document formats. ProseMirror offers a powerful schema system for defining document structures and allows for fine-grained control over the editing experience.

It seems to me that the highly customizable part of ProseMirror was the main thing we want with our editor.

## Pros and Cons of the Options

### [Milkdown][8]

* Good, easy to start, we'll have a result quickly.
* Good, it comes with reliable examples and documentation.
* Good, toolbar is highly customizable.
* Bad, because it does not cover everything we need, we'll need to write several plugins. We can add a plugin to Milkdown, but the overlay of Milkdown will make it more complex to write and maintain the plugin code.

[Link to the Proof-of-concept][2]

### [Quill][9]

* Good, easy to start, we'll have a result quickly.
* Good, toolbar is easy to configure.
* Bad, we cannot rely on community plugin, every plugin I dug into was deprecated (emoji, drag and drop, tables,…).
* Bad, we'll need to implement everything which is not provided by the core.

[Link to the Proof-of-concept][3]

### [ProseMirror][10]

* Good, modern editors like Milkdown or Tiptap are an overlay on top of ProseMirror, we should be able to have the same feature coverage as them.
* Good, enables us to customize everything, so we'll be able to concentrate fully on the user experience we want to provide to end users.
* Good, does not come with CSS, we can focus on how we want to render it.
* Good, ProseMirror node storage is "a DOM", it will allow us a large amount of customization.
* Bad, we'll need to implement everything which is not provided by the core.

[Link to the Proof-of-concept][4]

## Links

* [CKEditor v4][5], our previous "What You See Is What You Get" text editor.
* [npm-compare article][17] comparison of rich text editors between prosemirror-view vs quill vs draft-js vs slate vs tinymce

[0]: https://tuleap.net/plugins/tracker/?aid=37946
[1]: https://tuleap.net/plugins/tracker/?aid=38646
[2]: https://gerrit.tuleap.net/c/tuleap/+/31402
[3]: https://gerrit.tuleap.net/c/tuleap/+/31440
[4]: https://gerrit.tuleap.net/c/tuleap/+/31467
[5]: https://ckeditor.com/docs/ckeditor4/latest/index.html
[6]: https://react.dev/
[7]: https://tuleap.net/artidoc/1628
[8]: https://github.com/Milkdown/milkdown
[9]: https://github.com/slab/quill
[10]: https://github.com/ProseMirror/prosemirror
[11]: https://github.com/xdan/jodit
[12]: https://github.com/froala
[13]: https://github.com/ether/etherpad-lite
[14]: https://github.com/udecode/plate
[15]: https://github.com/tinymce/tinymce
[16]: https://github.com/ueberdosis/tiptap
[17]: https://npm-compare.com/draft-js,prosemirror-view,quill,slate,tinymce
