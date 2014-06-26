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

=== Access with HTTP/S ===

How to enable git for http/https
--------------------------------

Requirement: you will need a dedicated name and IP address to deliver git over *http(s).
If your server is 'example.com', git will be delivered on
'http://git.example.com'

Sudo configuration
------------------
Copy the snippet in etc/sudoers.d/gitolite-http in central sudo configuration
(use visudo).

Apache configuration
--------------------

Copy and adapt (dbauthuser) appropriate apache config (depending on your OS version)
from plugins/git/etc/httpd/git.conf.rhelX.dist to /etc/httpd/conf.d/tuleap-plugins/git-http.conf

-> note that, by default this will grant access to git repositories in both
   HTTP and HTTPS. If you only want HTTPS, you shall copy the content of the snippet
   into /etc/httpd/conf/ssl.conf virtual host

-> you will need to adapt the authentication. By default it's mysql based but
   you might want to use ldap or perl depending of your setup.

-> restart apache (service httpd restart)

Tuleap configuration
--------------------

Update 'git_http_url' in /etc/tuleap/plugins/git/config.inc

Test
----
After restart, you should be able to clone/push:
git clone https://example.com/git/projectname/reponame.git
