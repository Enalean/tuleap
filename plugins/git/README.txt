If you installed this plugin from the package management system (RPM) all
dependencies are already installed, you just need to activate the plugin
to start using it.

=== User friendly URLs ===

Since Tuleap 6.11, user friendly URLs have been implemented to browse Git repositories
using the pattern: http://<your_domain>/plugins/git/<project_name>/<repo_path>

If your instance was installed before version 6.11, you must do these two
modifications in order to get them activated:

* Add to the /etc/codendi/plugins/git/etc/config.inc file:

    $git_use_friendly_urls = 1;

=== Gerrit ===

You can connect Tuleap to Gerrit servers.

This is still in beta, procedure is available in the wiki:
http://tuleap-documentation.readthedocs.io/en/latest/developer-guide/gerrit.html


Gerrit web site: https://www.gerritcodereview.com

=== Under the hood ===

Git plugins depends on gitolite 2.3.1 or gitolite3 (that depends of git >= 1.7)

More gitolite info can be found on gitolite web site:
Reference: http://gitolite.com/gitolite/install/index.html
