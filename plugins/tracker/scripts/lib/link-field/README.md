# @tuleap/plugin-tracker-link-field

This library provides a custom element to display and change artifact links from/to an artifact. The custom element's tag name is `tuleap-artifact-modal-link-field`. It also defines other custom elements, but they are not meant to be used outside this library.

The link field element has three dependencies that must be passed as properties:
1. `controller`: a `LinkFieldController`. Use `LinkFieldCreator.createLinkFieldController()` to create it.
2. `autocompleter`: a `ArtifactLinkSelectorAutoCompleterType`. Use `LinkFieldCreator.createLinkSelectorAutoCompleter()` to create it.
3. `creatorController`: a `ArtifactCreatorController`. Use `LinkFieldCreator.createArtifactCreatorController()` to create it.

The link field element emits a `change` event when anything is changed: when a new link is added, a link is marked for removal, a link type is changed, etc. You can listen to this event to put a warning when the user is leaving the page, for example, to warn them that there are unsaved changes.

The links are stored in `LinksStore`, `NewLinksStore` and `LinksMarkedForRemovalStore`. All links in `LinksStore` and `NewLinksStore` that are NOT marked for removal in `LinksMarkedForRemovalStore` are valid links to be saved. You can create a valid JSON payload from this when saving the artifact.
