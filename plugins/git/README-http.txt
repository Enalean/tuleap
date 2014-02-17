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
Copy the snippet in plugins/git/etc/httpd/git.conf.dist into 
/etc/httpd/conf/ssl.conf just after main virtualhost definition

-> you will need to adapt the authentication. By default it's mysql based but 
   you might want to use ldap or perl depending of your setup.

-> restart apache (service httpd restart)

Gitolite path
-------------

This procedure is adapted to gitolite install in /usr/com/gitolite.
For more recent version (when install dir is /var/lib/gitolite), you need to
adapt a few things:
In apache config:
SetEnv GITOLITE_HTTP_HOME /var/lib/gitolite
SetEnv GIT_HTTP_BACKEND /usr/libexec/git-core/git-http-backend

Tuleap configuration
--------------------
Add the following line to /etc/codendi/plugins/git/config.inc

    $git_http_url = "https://git.example.com/p";

Test
----
After restart, you should be able to clone/push:
git clone http://git.example.com/p/projectname/reponame.git

