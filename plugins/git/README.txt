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

=== Known issues ===
In the version 1.16 and older of the plugin there was an issue that may keep some legacy.
Here is a desription of the legacy and a way to fix it.

Problem:
    * It was possible to create a repository called 'toto.git'
    * In DB the values were (repository_name = 'toto.git', repository_path = 'project/toto.git.git')
    * Into the filesystem the stored path was 'project/toto.git'
    * Problem 1: Impossible to clone using the proposed path (git clone ....toto.git.git)
    * Problem 2: Impossible to delete the repository
    * Users were able to clone the repo by modifying the path (git clone ....toto.git)

Proposed solution:
    * Don't allow anymore the creation of a repository with '.git'
    * When a legacy repo using gitolite and having '.git' at the end is reported by a user, site admin must:
        0. Ensure that a backup exists and is valid
        1. Verify if there is no other repository having the same name, example: If repo is called 'toto.git' verify if there is no repo called 'toto' either using gitshell or gitolite
        2. Change the name 'toto.git' to 'toto' and th path '<project>/toto.git.git' to '<project>/toto.git' only in the database
           by performing carefully something like this sql request:
            > UPDATE plugin_git 
              SET repository_name = 'toto', repository_path='<project>/toto.git'  
              WHERE repository_id = <?>;
        3. Delete the repository, if that's what user wanted to do
