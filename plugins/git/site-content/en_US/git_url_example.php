    <h3>How to use a Git repository:</h3>
    <dl>
        <dt>Clone the repository in order to get your working copy:</dt>
        <dd>
            <pre>
git clone <span class="plugin_git_example_url"><?= $url ?></span> <?= $name ?> 
cd <?= $name ?>
            </pre>
        </dd>
        <dt>Or just add this repository as a remote to an existing local repository:</dt>
        <dd>
            <pre>
git remote add <?= $name ?> <span class="plugin_git_example_url"><?= $url ?></span>
git fetch <?= $name ?> 
git checkout -b my-local-tracking-branch <?= $name ?>/master
            </pre>
        </dd>
    </dl>

