# Clone Tuleap Sources

First things first, clone Tuleap sources.

``` bash
$ git clone https://tuleap.net/plugins/git/tuleap/tuleap/stable.git tuleap
```

**The folder you are cloning into must be named `tuleap`.**

In order to ease the setup, we are using an anonymous clone url. If you
already have credentials on our gerrit server you can fetch the [tuleap
project](https://gerrit.tuleap.net/admin/repos/tuleap). In any case, we
will configure the gerrit server as a git remote later.

[Next, install docker](./install-docker.md)
