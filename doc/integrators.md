# Integrators

This section is only relevant to Tuleap integrators that are responsible
to review and merge contributions into master.

## Environment setup

You need to be ale to sign the commits/tags/merges in stable repository.

The commits are expected to be signed with a SSH key with a FIDO security key.

If it is not already the case, generate a SSH key associated to your security key:

```bash
ssh-keygen -t ecdsa-sk -f ~/.ssh/id_ecdsa_sk
```

Then configure git to use this key:
```bash
git config gpg.format ssh
git config user.signingKey ~/.ssh/id_ecdsa_sk
```

Add yourself in the allowed signers file `tools/utils/signing-keys/allowed-integrators` (follow the existing entries to add your public key) and in integrators list in `README.mkd`.
Push these modifications under review and wait for the `+2` from the integrators.

If you need to check signature of existing commits (e.g `git log --show-signature`) then you need to instruct git to use our allowed signers file:
```bash
git config gpg.ssh.allowedSignersFile tools/utils/signing-keys/allowed-integrators
```

## Integration of contributions

-   Make sure that the contribution is ready to merge: `+2` from
    integrators and `+2` from integration continue

-   Make sure that the commit message of the contribution is referencing
    a public reference (request or story artifact). If not: `-1`.

-   Re-sync your branch with the latest master updates

    ``` bash
    $ git fetch stable && git checkout stable/master
    ```

-   Merge the patch from gerrit (see the \"download\" section in gerrit)
    it should be something of the sort:

    ``` bash
    $ git fetch ssh://username@gerrit.tuleap.net:29418/tuleap refs/changes/52/52/8 && \
      git merge --no-ff --no-commit --log FETCH_HEAD
    ```

    **Note:** It is the responsibility of the contributor to resolve
    conflicts. Integrators should ask to the contributor to rebase her
    changes in case of conflicts.

-   Edit VERSION number:

    ``` bash
    $ tools/utils/version_numbers/generate.sh
    ```

    **Important:** Do **not** `git add` the updated version file.

-   Commit (signed) **only** the merge (**do not touch the commit
    message**).

    ``` bash
    $  `git commit -v -S`
    ```

-   Commit (signed) the new version files

    ``` bash
    $ git commit -av -S -m "This is Tuleap $(cat VERSION)"
    ```

-   Push your merge to stable:

    ``` bash
    $ git push stable HEAD:master
    ```

-   Update Gerrit master:

    ``` bash
    $ git push username@gerrit:tuleap stable/master:refs/heads/master
    # OR, if you have gerrit as a remote
    $ git push gerrit HEAD:master
    ```

-   Update the corresponding artifact(s) with the following information:

    -   Follow-up comment message:
        `gerrit #2548 integrated into Tuleap 12.2.99.12` (adjust `2548`
        and `12.2.99.12` to your situation)
    -   If the contribution closes the artifact (mentioned in the
        commit message or to be checked with the contributor), then you
        can close it:
        -   Set the status to `Closed`
        -   Add artifact link to current release artifact id with nature
            `Fixed in`
