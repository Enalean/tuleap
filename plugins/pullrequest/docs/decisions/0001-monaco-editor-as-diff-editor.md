# Monaco-editor as diff editor

* Status: rejected
* Deciders: Thomas GORKA, Clarisse DESCHAMPS
* Date: 2023-07-25

## Context and Problem Statement

Diff editor in Pullrequest is made with [CodeMirror][0].
[A new version of CodeMirror][1] has been released but requires major changes in the source code to upgrade the version.
CodeMirror is a too low-level library, so to implement an editor with the library, you have to start from scratch and implement everything.
CodeMirror cannot display file diffs natively, it was necessary to create two editors, side by side, containing both versions of a file, and to synchronize them.


## Decision Drivers

* The editor should display file diff out of the box.
* The editor should have at least the same functional coverage as CodeMirror.


## Considered Options

* Use [monaco-editor][2]
* Keep CodeMirror

## Decision Outcome

Chosen option: We keep CodeMirror because we found too many red flags spiking the monaco-editor integration into the pull-requests, such as major bugs or missing features that introduce regressions and comforting us to keep using CodeMirror.

## Pros and Cons of the Options

### Monaco-editor

* Good, because the file diff is much more precise and provides a clear view of changes made to files.
* Good, because most features are simple options that can be activated or deactivated.
* Bad, because parts of the code are bugged and not fixed by the developers.
* Bad, because it is hard to know which features and bug fixes will be worked on, and when they will be available in monaco-editor.
* Bad, because features in high demand take more than 5 years to appear, e.g. [collapsible common lines][3].
* Bad, because new features they develop break others without noticing.
* Bad, because the lib has an heavy byte weight (~5Mo).
* Bad, because monaco-editor uses web-workers, and it makes the build tricky.

### CodeMirror

* Good, because the code is already written, and it is ours.
* Good, because it is relatively light compared to monaco-editor.
* Bad, because it is a lot of work to migrate it to CodeMirror 6.
* Bad, because it is hard to develop advanced features with it.


[0]: https://codemirror.net/
[1]: https://codemirror.net/docs/migration/
[2]: https://microsoft.github.io/monaco-editor/
[3]: https://github.com/microsoft/vscode/issues/3562
