If you installed this plugin from the package management system (RPM) all
dependencies are already installed, you just need to activate the plugin
to start using it.

Git plugin has 2 access methods for user
* The historical one, based on git-shell. End users use their "Tuleap login"
  to login, commit & update. They can use either password or ssh key access
  method

* Gitolite, currently in "Lab mode". Project admins who want to activate it
  has to go in "My personal page > Preferences > Tuleap Lab" and to activate
  the lab mode. Once activated, in git new repositories can be created with
  "fine grain permissions capabilities".
  To access those repositories, end users HAVE TO use 'gitolite' ssh user and
  to deploy their ssh key.


=== Under the hood ===

Git plugins depends of both gitolite 1.5.9 (that depends of git >= 1.7)
Both packages and their dependencies are available in epel repository

More gitolite info can be found on gitolite web site:
Reference: http://sitaramc.github.com/gitolite/doc/1-INSTALL.html#_package_method
