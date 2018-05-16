#!/bin/sh

script=`basename $0`

echo "--- Beginning of $script ---"

# LJ - Many comments added
# If arguments are omitted the scripts covers
# statistics for the day before

# START with specific scripts

# Compute all sorts of project metric (number of forum
# messages, tasks, bugs, cvs commit, patches, file release
# file download, # of developers,...
# No time argument for this one. Current numbers are
# computed
./db_project_metric.pl

# Compute the top_group table whre all projects
# are stored with all sorts of ranking. Redirect
# text output to a file in the dumpt directory
# LJ note: I was unable to find where and when this
# output file is used !!
# $dump_dir is defined in compute_all_daily_stats.sh
./db_top_groups_calc.pl $* > $dump_dir/db_top_groups_calc_output


# NOW RUN THIS SECTION
# - db_stats_projects_nightly.pl needs db_project_metric.pl 
# to run first
#
## The order these scripts are run in is CRITICAL
## DO NOT change their order. Add before, or add after
##
./db_stats_prepare.pl $*

# Feed the database with CVS statistics extracted
# from daily CVS log files
#
./db_stats_cvs_history.pl $*

./db_stats_projects_nightly.pl $*

##
## END order sensitive section
##


#################
# DO SOME CLEAN UP
#################

#Clean up projects that have status at INCOMPLETE
# that are older than one hour, user registration PENDING
# older than a week and user session older than a week
# 
./db_project_cleanup.pl

echo "--- End of $script ---"

exit 0
