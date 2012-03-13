<?php

/**
 *
 */
class b201102091302_deploy_post_receive_to_existing_repositories extends ForgeUpgrade_Bucket {

    public function description() {
        return <<<EOT
Add Codendi post-receive hook to all existing git repositories
EOT;
    }

    public function preUp() {
        $processUser = posix_getpwuid(posix_geteuid());
        $username = $processUser['name'];
        if ($username != 'root') {
            throw new Exception('Must be root to run this upgrade');
        }
    }

    public function up() {
        $dir = new DirectoryIterator('/var/lib/tuleap/gitroot');
        foreach ($dir as $project) {
            if (!$project->isDot() && $project->isDir()) {
                $prjIter = new DirectoryIterator($project->getPathname());
                foreach ($prjIter as $repo) {
                    $hooksDir = $repo->getPathname().DIRECTORY_SEPARATOR.'hooks';
                    if (!$repo->isDot() && is_dir($hooksDir)) {
                        $groupName = basename($project->getPathname());
                        $hook = $hooksDir.DIRECTORY_SEPARATOR.'post-receive';

                        $this->log->info("Deploy $hook");
                        unlink($hook);
                        file_put_contents($hook, $this->getHook());
                        chmod($hook, 0755);
                        chown($hook, 'codendiadm');
                        chgrp($hook, $groupName);
                    }
                }
            }
        }
    }

    protected function getHook() {
        return <<<EOT
#!/bin/sh
#
# An example hook script for the post-receive event
#
# This script is run after receive-pack has accepted a pack and the
# repository has been updated.  It is passed arguments in through stdin
# in the form
#  <oldrev> <newrev> <refname>
# For example:
#  aa453216d1b3e49e7f6f98441fa56946ddcd6a20 68f7abf4e6f922807889f52bc043ecd31b79f814 refs/heads/master
#
# see contrib/hooks/ for an sample, or uncomment the next line (on debian)
#


#. /usr/share/doc/git-core/contrib/hooks/post-receive-email
# !!! Codendi Specific !!! DO NOT REMOVE (NEEDED CODENDI MARKER)
. /usr/share/tuleap/plugins/git/hooks/post-receive 2>/dev/null
# END OF NEEDED CODENDI BLOCK

EOT;
    }
}

?>
