<VirtualHost 192.168.122.10:80>
        ServerName git.shunt.cro.enalean.com
        SetEnv GIT_PROJECT_ROOT /var/lib/codendi/gitolite/repositories
        SetEnv GIT_HTTP_EXPORT_ALL
        SetEnv GITOLITE_HTTP_HOME /usr/com/gitolite
        SetEnv GIT_HTTP_BACKEND /usr/bin/git-http-backend
	ScriptAlias /git/ /var/www/bin/gitolite-suexec-wrapper.sh/

         <Location />
                AuthType Basic
                AuthName "Private Git Access"
                Require valid-user
                AuthUserFile /tmp/passfile
         </Location>
</VirtualHost>

sudo:
# Gitolite smart http
Defaults:codendiadm !requiretty
Defaults:codendiadm !env_reset
codendiadm ALL= (gitolite) SETENV: NOPASSWD: /usr/bin/gl-auth-command

-bash-3.2$ cat /var/www/bin/gitolite-suexec-wrapper.sh 
#!/bin/bash
#
# Suexec wrapper for gitolite-shell
#

export GIT_PROJECT_ROOT="/var/lib/codendi/gitolite/repositories"
export GITOLITE_HTTP_HOME="/usr/com/gitolite"

exec sudo -E -u gitolite /usr/bin/gl-auth-command
-----------------------------------------------------------------------

