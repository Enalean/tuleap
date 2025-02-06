# pre-receive-tuleap-git-repo-validation

This is the code of the [pre-receive hook](https://docs.tuleap.org/user-guide/code-versioning/git.html#pre-receive-hook)
used in [Tuleap official source repositories](https://tuleap.net/plugins/git/tuleap/tuleap/stable).

## Validations

The hook currently validates the following items:
* tags are formatted using [one of the formats described in the release documentation](../../../docs/release.md)
* tags are signed with an SSH signature from [a known integrator](./src/allowed-integrators)
* commits are signed (or integrated by a signed merge commit) with an SSH signature from [a known integrator](./src/allowed-integrators)
