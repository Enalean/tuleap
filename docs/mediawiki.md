# MediaWiki integration with Tuleap

The most recent integration of MediaWiki with Tuleap (called "MediaWiki Standalone") is an external site: MediaWiki runs separately from Tuleap. In fact, there are several MediaWiki instances, side-by-side with Tuleap.

Compared to previous iterations, the integration is "reversed": MediaWiki has an extension (a "Skin") for Tuleap. It includes the Tuleap Project Sidebar custom element and does some visual adjustments to keep the look-and-feel of Tuleap in the MediaWiki instances.

## How to update the Tuleap sidebar in MediaWiki Standalone

### Step 1: Tuleap side — Update and release the Tuleap Project Sidebar NPM package

Make the needed changes in the [@tuleap/project-sidebar][0] package folder.

Then, [tag a new version number and release the Tuleap project sidebar][1] on NPM. It will update the [@tuleap/project-sidebar package][12] on NPM.

### Step 2: MediaWiki side — Prepare the TuleapSkin repository

You will need a [MediaWiki Developer account][2]. Sign up for a user account.

Use that account to log into [Wikimedia's Gerrit instance][3]. Follow the [Gerrit Tutorial][4] to set up your SSH key and Gerrit settings. You can settle for following only the "Configure Git" and "Set Up SSH Keys in Gerrit" sections. If you are already used to Tuleap Gerrit, you don't need to set up the `git-review` tool. Make sure to at least install the Gerrit pre-commit hook, and configure the `user.name` and `user.email` for this git repository. See the [Troubleshooting][5] page if you have issues connecting to Wikimedia's Gerrit through SSH.

Then, clone the [mediawiki/skins/TuleapSkin][6] repository. This is the repository for the Tuleap "Skin" of MediaWiki, which applies the Tuleap look-and-feel.

`cd` to the newly cloned TuleapSkin directory.

Rename the remote to `gerrit` and set its URL to SSH:
```shell
git remote rename origin gerrit
git remote set-url gerrit 'ssh://<your-username>@gerrit.wikimedia.org:29418/mediawiki/skins/TuleapSkin'
```

The `master` branch targets the latest MediaWiki version (for example MediaWiki 1.40), which is not necessarily what you want. Fetch the branches targeting each major version of MediaWiki:
```shell
git fetch gerrit --verbose
```

### Step 3: MediaWiki side — Update the Tuleap Project Sidebar version

Switch to the branch targeting the current LTS version of MediaWiki, and for which you want to submit the change. For example for MediaWiki 1.39:
```shell
git switch REL1_39
```

Switch into a nix-shell with the necessary dev tools: `npm`, `php`, and `composer`
```shell
nix-shell --packages nodejs_20 php83 php83Packages.composer
```

Install the front-end and back-end dependencies:
```shell
npm install
composer install
```

Update the `@tuleap/project-sidebar` version. For example for version 2.7.0:
```shell
npm install @tuleap/project-sidebar@2.7.0
```
There is a post-install script that will copy the project sidebar's JavaScript and CSS stylesheet to the `resources/lib/` folder (🙄). Add them to the git staging area: `git add resources`.

Make the other needed changes to the repository. See the [MediaWiki coding conventions][7] for coding style. It is quite different from Tuleap's coding style. Notably, tabs are required for indentation instead of spaces. They also seem to require spaces in-between parentheses, for example: `( 1 )` instead of `(1)`. For the front-end code, you can run `npm run test` that seems to run linters on the code to check for errors with the coding style. For the back-end code, run `composer test` for linters.

Once you are ready to commit, see the [Pre-commit checklist][8], the [Commit message guidelines][9] and the [Getting reviews guide][10].

### Step 4: Tuleap side — Test your changes

In order to test your changes, you can rebuild and install the Tuleap Skin in your Tuleap dev instance.

First, copy your changed TuleapSkin repository to the [mediawiki-extensions-current-lts/skins][14] folder. It mimics what composer would do when cloning from GitHub. If you know how to link a local folder with Composer, do that instead.
```shell
# From tuleap root folder
rm -r plugins/mediawiki_standalone/additional-packages/mediawiki-extensions-current-lts/skins/TuleapSkin
cp -r <path-to-the-TuleapSkin-folder> plugins/mediawiki_standalone/additional-packages/mediawiki-extensions-current-lts/skins/
# Make sure the result is a skins/TuleapSkin/ folder.
```

Build the Tuleap mediawiki RPMs:
```shell
# From tuleap root folder
nix-build plugins/mediawiki_standalone/additional-packages/tuleap-mediawiki.nix
# Copy the RPM to Tuleap root folder, so we can install it in the web container
cp result/mediawiki-tuleap-flavor-current-lts-<version>.noarch.rpm .
```

Then, install the new RPM on your Tuleap dev instance:
```shell
make bash-web
$ rpm -ivh --nodeps --force mediawiki-tuleap-flavor-current-lts-<version>.noarch.rpm
$ systemctl restart mediawiki-tuleap-php-fpm.service
```

Then, browse one of your projects with MediaWiki Standalone service active. You should see the changes in the project sidebar.

### Step 5: MediaWiki side — Push your changes to be reviewed

Push your commit to Gerrit and target the release branch you are aiming for. For example for MediaWiki 1.39:
```shell
git push gerrit HEAD:refs/for/REL1_39
```

This will create a new change on MediaWiki's Gerrit.

Then the process is similar to Tuleap: wait for a review, react to the reviewer's feedback, until they merge your commit.

### Step 6: MediaWiki side — Back-port the change to the maintained branches

Once your change has been merged, you must cherry-pick it on top of the `master` branch and once again on top of the next Long-Term Support branch (for example, `REL1_43` for MediaWiki 1.43). Push the cherry-picked commits for review:

```shell
git switch -d gerrit/master
git cherry-pick <commit ref>
git push gerrit HEAD:refs/for/master
```

```shell
git switch -d gerrit/REL1_43
git cherry-pick <commit ref>
git push gerrit HEAD:refs/for/REL1_43
```

Then the process is similar to Tuleap: wait for reviews, react to the reviewer's feedback, until they merge your commits.

### Step 7: Tuleap side — Release the MediaWiki Standalone RPM packages

The Tuleap project sidebar is installed in MediaWiki Standalone via the `mediawiki/tuleap-skin` Composer package, which in fact points to the last tag of the TuleapSkin repository on GitHub. Once the commit is merged on MediaWiki side, it will be mirrored to GitHub.

Create a new patch for Tuleap to upgrade the [mediawiki-extensions-current-lts composer file][11] to the latest tag, and push it to be reviewed as usual. The reviewer can test it similarly to Step 4: rebuild the RPM with `nix-build`, copy it, install it in the container and restart the MediaWiki FPM service.

Once this last commit is merged, the Tuleap CI job to build the RPMs will pull the new tag with the up-to-date Tuleap Project Sidebar and TuleapSkin. It will then be released as part of Tuleap's usual RPM build. There is nothing more to do, the update is done!

## Links

- The [@tuleap/project-sidebar package][12] on NPM
- [Wikimedia's Gerrit instance][3]
- The [TuleapSkin repository][6] on Wikimedia's Gerrit
- The [Getting reviews guide][10] for Wikimedia's Gerrit. It is a good starting point to other information.
- The [How to make a MediaWiki Skin guide][13]. It describes the structure of the Skin and the special variables.

[0]: ../../lib/frontend/project-sidebar/README.md
[1]: release.md#release-a-js-library-developed-in-the-main-tuleap-repository
[2]: https://www.mediawiki.org/wiki/Developer_account
[3]: https://gerrit.wikimedia.org
[4]: https://www.mediawiki.org/wiki/Gerrit/Tutorial
[5]: https://www.mediawiki.org/wiki/Gerrit/Troubleshooting#Bad_server_host_key:_Invalid_key_length
[6]: https://gerrit.wikimedia.org/r/q/project:mediawiki/skins/TuleapSkin
[7]: https://www.mediawiki.org/wiki/Manual:Coding_conventions
[8]: https://www.mediawiki.org/wiki/Manual:Pre-commit_checklist
[9]: https://www.mediawiki.org/wiki/Gerrit/Commit_message_guidelines
[10]: https://www.mediawiki.org/wiki/Gerrit/Code_review/Getting_reviews
[11]: ../plugins/mediawiki_standalone/additional-packages/mediawiki-extensions-current-lts/composer.json
[12]: https://www.npmjs.com/package/@tuleap/project-sidebar
[13]: https://www.mediawiki.org/wiki/Manual:How_to_make_a_MediaWiki_skin
[14]: ../plugins/mediawiki_standalone/additional-packages/mediawiki-extensions-current-lts/skins/
