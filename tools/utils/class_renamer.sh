#!/bin/bash

set -x

old_name=$1
new_name=$2

# Replace imports and class names
files=$(grep -rl  $old_name *)
gsed -i "s/$old_name/$new_name/" $files

# rename file
old_file_full_path=$(find . -name ${old_name}.class.php)
new_file_full_path=$(echo $old_file_full_path | sed "s/$old_name/$new_name/")
git mv $old_file_full_path $new_file_full_path

# rename test file
old_file_full_path=$(find . -name ${old_name}Test.php)
new_file_full_path=$(echo $old_file_full_path | sed "s/$old_name/$new_name/")
git mv $old_file_full_path $new_file_full_path
