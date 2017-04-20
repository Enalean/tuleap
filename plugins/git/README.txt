If you installed this plugin from the package management system (RPM) all
dependencies are already installed, you just need to activate the plugin
to start using it.

=== Deploiement on CentOS 5 ===
You need to add the following instructions at the end of your sudoers configuration (/etc/sudoers):

    Defaults:gitolite !requiretty
    Defaults:gitolite !env_reset
    gitolite ALL= (codendiadm) SETENV: NOPASSWD: /usr/share/codendi/src/utils/php-launcher.sh /usr/share/codendi/plugins/git/hooks/post-receive.php*

=== User friendly URLs ===

Since Tuleap 6.11, user friendly URLs have been implemented to browse Git repositories
using the pattern: http://<your_domain>/plugins/git/<project_name>/<repo_path>

If your instance was installed before version 6.11, you must do these two
modifications in order to get them activated:

* Add to the /etc/codendi/plugins/git/etc/config.inc file:

    $git_use_friendly_urls = 1;


* Modify your /etc/httpd/conf.d/codendi_aliases.conf file and replace:

    <DirectoryMatch "/usr/share/codendi/plugins/([^/]*)/www/">
        Options MultiViews
        AllowOverride None
        Order allow,deny
        Allow from all
    </DirectoryMatch>

by

    <Directory "/usr/share/codendi/plugins/*/www/">
        Options MultiViews FollowSymlinks
        AllowOverride All
        Order allow,deny
        Allow from all
    </Directory>

in the "plugins" section.

* Restart your httpd service with `service httpd restart`

=== Gerrit ===

You can connect Tuleap to Gerrit servers.

This is still in beta, procedure is available in the wiki:
https://tuleap.net/wiki/index.php?pagename=Gerrit%2FConnectGerritToTuleap&group_id=101


Gerrit web site: http://code.google.com/p/gerrit

=== Under the hood ===

Git plugins depends on gitolite 1.5.9 or 2.3.1 (that depends of git >= 1.7)

More gitolite info can be found on gitolite web site:
Reference: http://sitaramc.github.com/gitolite/doc/1-INSTALL.html#_package_method
