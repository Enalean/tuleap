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
/etc/http/conf/httpd.conf just after main virtualhost definition (ie. before 
svn and home pages)

-> you will need to adapt the authentication. By default it's mysql based but 
   you might want to use ldap or perl depending of your setup.

-> restart apache (service httpd restart)

Test
----
After restart, you should be able to clone/push:
git clone http://git.example.com/p/projectname/reponame.git

