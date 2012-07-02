<div class="card-actions">
    <div class="dropdown" id="card-<?= $this->getId() ?>-actions-menu">
        <a href="<?= $this->getUrl() ?>"
           data-target="#card-<?= $this->getId() ?>-actions-menu"
           class="dropdown-toggle"
           data-toggle="dropdown"><?= $this->getXRef() ?> <span class="caret"></span></a>
        <ul class="dropdown-menu">
            <li>
                <a href="<?= $this->getEditUrl() ?>&<?= $this->planning_redirect_parameter ?>"><?= $this->getEditLabel() ?></a>
            </li>
            <li class="divider"></li>
            <? foreach($this->allowedChildrenTypes() as $tracker): ?>
                <li>
                    <a href="/plugins/tracker/?tracker=<?= $this->getId() ?>&func=new-artifact-link&id=<?= $this->getArtifactId() ?>&immediate=1&<?= $this->planning_redirect_parameter ?>"><?= $tracker->getAddNewLabel() ?></a>
                </li>
            <? endforeach; ?>
        </ul>
    </div>
</div>
