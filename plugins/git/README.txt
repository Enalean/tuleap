!!! Gitolite support

* Update git from epel (1.7.x), compatible with existing git integration.
* Install gitolite from epel.

!! Setup gitolite

Reference: http://sitaramc.github.com/gitolite/doc/1-INSTALL.html#_package_method

* Generate a SSH key for codendiadm: ssh-keygen -q -t rsa -f $HOME/.ssh/id_rsa_gitolite -N ""
* cp $HOME/.ssh/id_rsa_gitolite.pub /var/tmp/codendi_cache
* su - gitolite
* gl-setup /var/tmp/codendi_cache/id_rsa_gitolite.pub
