<?php

require_once 'pre.php';

$project = ProjectManager::instance()->getProjectByUnixName($argv[1]);
if ($project && !$project->isError()) {
    $frs_ghost = new FRSGhostFiles($project);
    $frs_ghost->iterateOnFiles();
} else {
    echo "*** ERROR: invalid project name: $argv[1]\n";
}

class FRSGhostFiles {

    private $row_cache      = array();
    private $filepath_cache = array();
    private $project;

    public function __construct(Project $project) {
        $this->project = $project;
        $this->frs_path = ForgeConfig::get('ftp_frs_dir_prefix').'/'.$project->getUnixName(false);
    }

    public function iterateOnFiles() {
        foreach ($this->getFiles() as $row) {
            if (! is_file($this->frs_path.'/'.$row['filepath'])) {
                $this->findMissingFile($row);
            }
        }
    }

    private function findMissingFile(array $row) {
        echo "{$row['filepath']} is missing\n";
        if (strpos($row['filepath'], '/') !== false) {
            list ($directory, $filename) = explode('/', $row['filepath']);
            $this->findExactFilenameMatch($filename, $row);
        } else {
            echo "*** Invalid DB entry: ".implode(', ', $row).PHP_EOL;
        }
    }

    private function getFiles() {
        if (! $this->row_cache) {
            $this->cacheFiles();
        }
        return $this->row_cache;
    }

    private function cacheFiles() {
        $res = $this->getProjectFiles($this->project);
        while ($row = db_fetch_array($res)) {
            $this->filepath_cache[$row['filepath']] = $row['file_id'];
            if ($row['status'] == 'A') {
                $this->row_cache[] = $row;
            }
        }
    }

    private function isNotAlreadyReferencedInDb($old_path) {
        return ! isset($this->filepath_cache[$old_path]);
    }

    private function findExactFilenameMatch($filename, array $row) {
        $directory_iterator = new DirectoryIterator($this->frs_path);
        $found = false;
        foreach ($directory_iterator as $dirinfo) {
            if (! $dirinfo->isDot()) {
                $found = $this->doesFileMatchExactName($dirinfo->getFilename(), $filename, $row);
            }
        }
        if (! $found) {
            $this->findApproximateMatch($filename, $row);
        }
    }

    private function doesFileMatchExactName($directory_path, $filename, array $row) {
        $old_path      = $directory_path.'/'.$filename;
        $old_path_full = $this->frs_path.'/'.$old_path;
        if (is_file($old_path_full)) {
            if (md5_file($old_path_full) == $row['computed_md5']) {
                if ($this->isNotAlreadyReferencedInDb($old_path)) {
                    echo "* Found exact match: $old_path\n";
                    $this->updateFileReference($this->frs_path, $old_path, $row['filepath']);
                    return true;
                } else {
                    echo "* Found $old_path but is already referenced in FRS with {$this->filepath_cache[$old_path]}\n";
                }
            } else {
                echo "* Found $old_path but md5 doesn match {$row['computed_md5']}\n";
            }
        }
        return false;
    }

    private function findApproximateMatch($filename, array $row) {
        $last_underscore = strrpos($filename, '_');
        if ($last_underscore === false) {
            echo "*** ERROR: No _ in $filename\n";
            return;
        } else {
            $reference_timestamp = intval(substr($filename, $last_underscore+1));
            $basename = substr($filename, 0, $last_underscore);
        }

        $directory_iterator = new DirectoryIterator($this->frs_path);
        $found = false;
        $candidates = array();
        foreach ($directory_iterator as $dirinfo) {
            if (! $dirinfo->isDot()) {
                $file_iterator = new DirectoryIterator($this->frs_path.'/'.$dirinfo->getFilename());
                foreach ($file_iterator as $fileinfo) {
                    $candidate_name = $dirinfo->getFilename().'/'.$fileinfo->getFilename();
                    if ($this->isNotAlreadyReferencedInDb($candidate_name)) {
                        if ($fileinfo->isFile() && strpos($fileinfo->getFilename(), $basename) === 0) {
                            $candidate_timestamp = intval(substr($fileinfo->getFilename(), strlen($basename)+1));

                            $timediff = abs($reference_timestamp - $candidate_timestamp);

                            $candidate_md5 = md5_file($this->frs_path.'/'.$candidate_name);
                            if ($row['computed_md5'] != '' && $candidate_md5 == $row['computed_md5']) {
                                $candidates[] = array(
                                    'name'      => $candidate_name,
                                    'timestamp' => $candidate_timestamp,
                                    'timediff'  => $timediff,
                                    'path'      => $this->frs_path,
                                    'row'       => $row
                                );
                                $found = true;
                            } else {
                                echo "* Found a candiate {$candidate_name} with {$timediff} seconds diff (md5 doesn't match {$candidate_md5} vs. DB: {$row['computed_md5']} )\n";
                                $found = true;
                            }
                        }
                    }
                }
            }
        }
        if ($found) {
            return $this->processCandidates($candidates);
        } else {
            echo "* Your file is in another castle\n";
        }
    }

    private function processCandidates(array $candidates) {
        if (count($candidates) == 1) {
            $candidate = array_shift($candidates);
            return $this->processCandidate($candidate);
        } elseif (count($candidates) > 1) {
            $closest_post_date = PHP_INT_MAX;
            $closest_candidate = null;
            foreach ($candidates as $candidate) {
                $post_date_diff = abs($candidate['timestamp'] - $candidate['row']['post_date']);
                echo "* Found a candidate {$candidate['name']} with {$candidate['timediff']}s diff with release_time and {$post_date_diff}s diff with post_date  (md5 match)\n";
                if ($post_date_diff < $closest_post_date) {
                    $closest_candidate = $candidate;
                    $closest_post_date = $post_date_diff;
                }
            }
            return $this->processCandidate($closest_candidate);
        }
    }

    private function processCandidate(array $candidate) {
        echo "* Found a candidate {$candidate['name']} with {$candidate['timediff']} seconds diff (md5 match)\n";
        return $this->updateFileReference($candidate['path'], $candidate['name'], $candidate['row']['filepath']);
    }

    private function updateFileReference($base_dir, $candidate_path, $db_path) {
        $release_path  = dirname($db_path);
        $dir_full_path = $base_dir.'/'.$release_path;
        if (! is_dir($dir_full_path)) {
            mkdir($dir_full_path, 0755);
            chgrp($dir_full_path, ForgeConfig::get('sys_http_user'));
        }
        $rename = rename($base_dir.'/'.$candidate_path, $base_dir.'/'.$db_path);
        if ($rename) {
            echo "* FIXED PATH: $candidate_path => $db_path\n";
        } else {
            echo "* UNABLE TO FIX PATH: $candidate_path => $db_path\n";
        }
        return $rename;
    }

    private function getProjectFiles(Project $project) {
        $group_id = $project->getID();
        return db_query("select file_id, status, filename, filepath, release_time, post_date, frs_file.release_id, frs_release.package_id, computed_md5
                  from frs_file
                  JOIN frs_release ON (frs_release.release_id = frs_file.release_id)
                  JOIN frs_package ON (frs_package.package_id = frs_release.package_id)
                  where group_id = $group_id");
    }
}