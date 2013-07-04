#!/usr/bin/perl

use strict;
use warnings;

exec "sudo -u %app_user% %app_path%/src/utils/php-launcher.sh %app_path%/plugins/git/hooks/post-receive.php ".join(" ", @ARGV);
