<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Git\Branch\BranchName;
use Tuleap\Http\Client\OutboundHTTPRequestProxy;

/**
 * Wrap access to git commands
 */
class Git_Exec //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const string GIT_TULEAP_PATH = '/usr/lib/tuleap/git';

    public const string TRANSPORT_EXT  = 'ext';
    public const string TRANSPORT_FILE = 'file';

    private $work_tree;
    private $git_dir;
    private $git_cmd;
    private $allowedTransports = [];

    /**
     * @param String $work_tree The git repository path where we should operate
     */
    final public function __construct($work_tree, $git_dir = null)
    {
        if (! $git_dir) {
            $this->setWorkTree($work_tree);
        } else {
            $this->setWorkTreeAndGitDir($work_tree, $git_dir);
        }
        $this->git_cmd = self::getGitCommand();
    }

    /**
     * @param String $work_tree
     */
    public function setWorkTree($work_tree)
    {
        $this->work_tree = $work_tree;
        $this->git_dir   = $this->work_tree . '/.git';
    }

    /**
     * @param String $work_tree
     * @param String $git_dir
     */
    public function setWorkTreeAndGitDir($work_tree, $git_dir)
    {
        $this->work_tree = $work_tree;
        $this->git_dir   = $git_dir;
    }

    public static function buildFromRepository(GitRepository $repository)
    {
        return new static($repository->getFullPath(), $repository->getFullPath());
    }

    public static function isGitTuleapInstalled(): bool
    {
        return is_file(self::GIT_TULEAP_PATH . '/bin/git');
    }

    /**
     * Whitelist usage of 'ext' protocol
     *
     * Starting git 2.12 exotic protocols are disabled by default by git client. 'ext' (used by gerrit to manage the
     * choice of ssh keys @see Git_RemoteServer_GerritServer::getCloneSSHUrl) should be explicitely whitelisted then.
     *
     * @see https://github.com/git/git/commit/f1762d772e9b415a3163abf5f217fc3b71a3b40e
     */
    public function allowUsageOfExtProtocol()
    {
        $this->allowedTransports[] = self::TRANSPORT_EXT;
    }

    public function allowUsageOfFileProtocol()
    {
        $this->allowedTransports[] = self::TRANSPORT_FILE;
    }

    public function init()
    {
        $this->gitCmd('init');
        // Not all version of Git knows init.defaultBranch setting, let's force it
        $this->setDefaultBranch(BranchName::defaultBranchName()->name);
    }

    public function setLocalCommiter($name, $email)
    {
        $this->gitCmd('config --add user.name ' . escapeshellarg($name));
        $this->gitCmd('config --add user.email ' . escapeshellarg($email));
    }

    /**
     * @return array{email: string, name: string}
     * @throws Git_Command_Exception
     */
    public function getAuthorInformation(string $commit_reference): array
    {
        $commit_content = $this->catFile($commit_reference);
        if (preg_match('/^author (?<name>.*) <(?<email>.*)> \d+.*$/m', $commit_content, $matches) === 1) {
            return $matches;
        }

        return ['name' => '', 'email' => ''];
    }

    public function remoteAdd($remote)
    {
        $this->gitCmd('remote add origin -- ' . escapeshellarg($remote));
    }

    public function pullBranch($remote, $branch)
    {
        $this->gitCmd('pull --quiet -- ' . escapeshellarg($remote) . ' ' . escapeshellarg($branch));
    }

    public function checkoutToFetchHead(): void
    {
        $this->gitCmd('checkout --quiet FETCH_HEAD');
    }

    public function configFile($file, $config)
    {
        $this->gitCmd('config -f ' . escapeshellarg($file) . ' ' . $config);
    }

    /**
     * git help mv
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     * @throw Git_Command_Exception
     */
    public function mv($from, $to)
    {
        $to_name = basename($to);
        $to_path = realpath(dirname($to)) . '/' . $to_name;
        $cmd     = 'mv -- ' . escapeshellarg(realpath($from)) . ' ' . escapeshellarg($to_path);
        return $this->gitCmd($cmd);
    }

    /**
     * git help add
     *
     * @param string $file
     *
     * @return bool
     * @throw Git_Command_Exception
     */
    public function add($file)
    {
        $cmd = 'add -- ' . escapeshellarg(realpath($file));
        return $this->gitCmd($cmd);
    }

    /**
     * git help rm
     *
     * @param string $file
     *
     * @return bool
     * @throw Git_Command_Exception
     */
    public function rm($file)
    {
        if ($this->canRemove($file)) {
            $cmd = 'rm -- ' . escapeshellarg(realpath($file));
            return $this->gitCmd($cmd);
        }
        return true;
    }

    public function recursiveRm($file)
    {
        if ($this->canRemove($file)) {
            $cmd = 'rm -r -- ' . escapeshellarg(realpath($file));
            return $this->gitCmd($cmd);
        }
        return true;
    }

    private function canRemove($file)
    {
        $output = [];
        $this->gitCmdWithOutput('status --porcelain -- ' . escapeshellarg(realpath($file)), $output);
        return count($output) == 0;
    }

    /**
     * List all commits between the two revisions (from the newest to the oldest)
     *
     * @param String $oldrev
     * @param String $newrev
     *
     * @return array of String sha1 of each commit
     */
    public function revList($oldrev, $newrev)
    {
        $output = [];
        $this->gitCmdWithOutput('rev-list ' . escapeshellarg($oldrev) . '..' . escapeshellarg($newrev), $output);
        return $output;
    }

    /**
     * Will list the revision list from the oldest to the newest
     *
     * @throws Git_Command_Exception
     * @return string[]
     */
    public function revListInChronologicalOrder(string $oldrev, string $newrev): array
    {
        $output = [];
        $this->gitCmdWithOutput('rev-list --reverse ' . escapeshellarg($oldrev) . '..' . escapeshellarg($newrev), $output);
        return $output;
    }

    /**
     * List all commits in the new branch that doesnt already belong to the repository
     *
     * @param String $refname Branch name
     * @param String $newrev  Commit sha1
     *
     * @return array of String sha1 of each commit
     */
    public function revListSinceStart($refname, $newrev)
    {
        $output         = [];
        $other_branches = implode(' ', array_map('escapeshellarg', $this->getOtherBranches($refname)));
        $this->gitCmdWithOutput('rev-parse --not ' . $other_branches . ' | ' . $this->buildGitCommandForWorkTree() . ' rev-list --reverse --stdin ' . escapeshellarg($newrev), $output);
        return $output;
    }

    private function getOtherBranches($refname)
    {
        $branches = $this->getAllBranches();
        foreach ($branches as $key => $branch) {
            if ($branch == $refname) {
                unset($branches[$key]);
            }
        }
        return $branches;
    }

    private function getAllBranches(): array
    {
        $output = [];
        $this->gitCmdWithOutput("for-each-ref --format='%(refname)' refs/heads", $output);
        return $output;
    }

    /**
     * @psalm-return list<string>
     */
    public function getAllBranchesSortedByCreationDate(): array
    {
        $output = [];
        $this->gitCmdWithOutput("for-each-ref --sort=-creatordate --format='%(refname:short)' refs/heads", $output);
        return $output;
    }

    public function getDefaultBranch(): ?string
    {
        return $this->getSymbolicRef('HEAD');
    }

    private function getSymbolicRef(string $name): ?string
    {
        $output = [];
        try {
            $this->gitCmdWithOutput('symbolic-ref --short -- ' . escapeshellarg($name), $output);
        } catch (Git_Command_Exception $e) {
            return null;
        }
        return implode('', $output);
    }

    public function setDefaultBranch(string $branch_name): void
    {
        $this->setSymbolicRef('HEAD', 'refs/heads/' . $branch_name);
    }

    private function setSymbolicRef(string $name, string $reference): void
    {
        $this->gitCmd(sprintf('symbolic-ref -- %s %s', escapeshellarg($name), escapeshellarg($reference)));
    }

    public function getAllTagsSortedByCreationDate(): array
    {
        $output = [];
        $this->gitCmdWithOutput("for-each-ref --sort=-creatordate --format='%(refname:short)' refs/tags", $output);
        return $output;
    }

    /**
     * @throws Git_Command_Exception
     */
    public function getCommitMessage(string $ref): string
    {
        $commit_content = $this->catFile($ref);
        if (preg_match('/(?<=\n\n)(?<message>[\s\S]*)/', $commit_content, $matches) === 1) {
            return $matches['message'];
        }

        return '';
    }

    /**
     * Returns content of an object
     *
     * @throws Git_Command_Exception
     */
    public function catFile(string $rev): string
    {
        $output = [];
        $this->gitCmdWithOutput('cat-file -p -- ' . escapeshellarg($rev), $output);
        return implode(PHP_EOL, $output);
    }

    /**
     * Returns the object type (commit, tag, etc);
     *
     * @throws Git_Command_UnknownObjectTypeException
     * @throws Git_Command_Exception
     */
    public function getObjectType(string $rev): string
    {
        $output = [];
        $this->gitCmdWithOutput('cat-file -t -- ' . escapeshellarg($rev), $output);
        if (count($output) == 1) {
            return $output[0];
        }
        throw new Git_Command_UnknownObjectTypeException();
    }

    public function doesObjectExists($rev)
    {
        $output = [];
        try {
            $this->gitCmdWithOutput('cat-file -e -- ' . escapeshellarg($rev), $output);
        } catch (Git_Command_Exception $exception) {
            return false;
        }
        return true;
    }

    /**
     * @return int
     */
    public function getLargestObjectSize()
    {
        $output = [];
        $this->gitCmdWithOutput(
            'cat-file --batch-check=\'%(objectsize)\' --batch-all-objects | sort -rn 2> /dev/null | head -1',
            $output
        );

        if (empty($output)) {
            return 0;
        }
        return (int) $output[0];
    }

    /**
     * git help commit
     *
     * Commit only if there is something to commit
     *
     * @param string $message
     *
     * @return bool
     * @throw Git_Command_Exception
     */
    public function commit($message)
    {
        if ($this->isThereAnythingToCommit()) {
            $cmd = 'commit -m ' . escapeshellarg($message);
            return $this->gitCmd($cmd);
        }
        return true;
    }

    /**
     * git help push
     *
     * @return bool
     * @throw Git_Command_Exception
     */
    public function push($origin = 'origin master')
    {
        $cmd = 'push --porcelain -- ' . $origin;
        return $this->gitCmd($cmd);
    }

    /**
     * @throw Git_Command_Exception
     */
    public function pushForce(string $origin = 'origin master'): bool
    {
        return $this->gitCmd('push --force --porcelain -- ' . $origin);
    }

    /**
     * @throws Git_Command_Exception
     */
    public function exportBranchesAndTags($destination_url)
    {
        $destination_url = escapeshellarg($destination_url);

        $push_heads = "push -- $destination_url refs/heads/*:refs/heads/*";
        $push_tags  = "push -- $destination_url refs/tags/*:refs/tags/*";

        $this->gitCmd($push_heads);
        $this->gitCmd($push_tags);
    }

    /**
     * Return true if working directory is clean (nothing to commit)
     *
     * @return bool
     * @throw Git_Command_Exception
     */
    public function isThereAnythingToCommit()
    {
        $output = [];
        $this->gitCmdWithOutput('status --porcelain', $output);
        foreach ($output as $status_line) {
            if (preg_match('/^[ADMR]/', $status_line)) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @return string The git repository path where we operate
     */
    public function getPath()
    {
        return $this->work_tree;
    }

    public static function getGitCommand(): string
    {
        if (self::isGitTuleapInstalled()) {
            return self::GIT_TULEAP_PATH . '/bin/git';
        }

        return 'git';
    }

    public function getGitDir()
    {
        return $this->git_dir;
    }

    /**
     * @throws Git_Command_Exception
     */
    protected function gitCmd($cmd)
    {
        $output = [];
        return $this->gitCmdWithOutput($cmd, $output);
    }

    /**
     * @return bool
     * @throws Git_Command_Exception
     */
    protected function gitCmdWithOutput($cmd, &$output)
    {
        return $this->execInPath($cmd, $output);
    }

    /**
     * @param $cmd
     * @param $output
     * @return bool
     * @throws Git_Command_Exception
     */
    protected function execInPath($cmd, &$output)
    {
        $git  = $this->buildGitCommandForWorkTree();
        $git .= ' ' . $cmd;
        try {
            $command = new System_Command();
            $output  = $command->exec($git);
            return true;
        } catch (System_Command_CommandException $exception) {
            throw new Git_Command_Exception($exception->getCommand(), $exception->getOutput(), $exception->getReturnValue());
        }
    }

    private function getAllowedProtocolEnvVariable(): string
    {
        if (count($this->allowedTransports) > 0) {
            return 'GIT_ALLOW_PROTOCOL=' . implode(':', $this->allowedTransports) . ' ';
        }
        return '';
    }

    private function buildGitCommandForWorkTree(): string
    {
        return $this->getAllowedProtocolEnvVariable() . $this->git_cmd .
               ' -c init.defaultBranch=' . escapeshellarg(BranchName::defaultBranchName()->name) .
               ' -c http.proxy=' . escapeshellarg(OutboundHTTPRequestProxy::getProxy()) .
               ' --work-tree=' . escapeshellarg($this->work_tree) .
               ' --git-dir=' . escapeshellarg($this->git_dir);
    }

    /**
     * @throws Git_Command_Exception
     */
    public function updateRef(string $reference, string $new_value): bool
    {
        $cmd = 'update-ref -- ' . escapeshellarg($reference) . ' ' . escapeshellarg($new_value);

        return $this->gitCmd($cmd);
    }
}
