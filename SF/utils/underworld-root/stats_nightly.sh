#!/bin/sh

#cd utils/underworld-root/

## The order these scripts are run in is CRITICAL
## DO NOT change their order. Add before, or add after
##
./db_stats_prepare.pl $*
./db_stats_cvs_history.pl $*
./db_stats_projects_nightly.pl $*
##
## END order sensitive section
##

./db_stats_site_nightly.pl $*
