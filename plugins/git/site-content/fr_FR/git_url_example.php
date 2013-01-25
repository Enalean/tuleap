    <h3>Comment utiliser un dépôt Git:</h3>
    <dl>
        <dt>Clonez le dépôt pour obtenir votre copie de travail locale:</dt>
        <dd>
            <pre>
git clone <span class="plugin_git_example_url"><?= $url ?></span> <?= $name ?> 
cd <?= $name ?>
            </pre>
        </dd>
        <dt>Ou ajoutez simplement ce dépôt distant à votre copie locale:</dt>
        <dd>
            <pre>
git remote add <?= $name ?> <span class="plugin_git_example_url"><?= $url ?></span>
git fetch <?= $name ?> 
git checkout -b my-local-tracking-branch <?= $name ?>/master
            </pre>
        </dd>
    </dl>
