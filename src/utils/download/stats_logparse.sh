#!/bin/sh

## and then build the agregates by project
## for all times
./stats_agr_filerelease.pl $*

## after which, we update the agregates
## by project for the day before
./stats_nightly_filerelease.pl $*


