#!/bin/sh

script=`basename $0`

echo "--- Beginning of $script ---"

cd /home/httpd/SF/utils/underworld-root/

# LJ - Many comments added
# If arguments are omitted the scripts covers
# statistics for the day before

# START with specific scripts 

# Compute nb of projects, users, downloads, sessions
# on a daily basis. Must be run after midnight to compute
# statistics for the day before
./db_stats_site_nightly.pl $*

# Compute the number of reference to the CodeX site
# through the CodeX logo displayed on other Web pages
# by day and by project (group). Must be run after midnight to compute
# statistics for the day before
./db_site_stats.pl $*

# Compute all sorts of project metric (number of forum
# messages, tasks, bugs, cvs commit, patches, file release
# file download, # of developers,...
# No time argument for this one. Current numbers are
# computed
./db_project_metric.pl

# Compute statitics on survey response (count, average,..)
# as well as total number of msg per forum
./db_rating_stats.pl $*

# Compute the trove counters (number of projects
# per trove. recursive counting for all subtrees)
./db_trove_treesums.pl $*

# Compute the top_group table whre all projects
# are stored with all sorts of ranking. Redirect
# text output to a file in the dumpt directory
# LJ note: I was unable to find where and when this
# output file is used !!
./db_top_groups_calc.pl $* > ~dummy/dumps/db_top_groups_calc_output


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

# Feed the database with Subversion statistics extracted
# from daily Subversion log files
#
./db_stats_svn_history.pl $*

./db_stats_projects_nightly.pl $*

##
## END order sensitive section
##


#################
# DO SOME CLEAN UP
#################

# Mark jobs older than 2 weeks as closed 
# We do not use job offering for the moment
# on Codex so do not clean up anything
#
#./db_jobs_close.pl

#Clean up projects that have status at INCOMPLETE
# that are older than one hour, user registration PENDING
# older than a week and user session older than a week
# 
./db_project_cleanup.pl

echo "--- End of $script ---"

exit 0
