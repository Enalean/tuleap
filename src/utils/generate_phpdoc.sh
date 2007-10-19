#!/bin/sh

# Default tools
CURRENTDIR=`pwd`
GREP="/bin/grep"
SED="/bin/sed"

if [ -z "$CODEX_LOCAL_INC" ]; then
   CODEX_LOCAL_INC=/etc/codex/conf/local.inc
fi

if [ ! -f "$CODEX_LOCAL_INC" ]; then
    echo "***ERROR: $CODEX_LOCAL_INC not found."
    exit 1;
fi

# honor BASEDOCDIR if defined
if [ -z "$BASEDOCDIR" ]; then
    CODEX_DOCUMENTATION_PREFIX=`${GREP} '^\$codex_documentation_prefix' $CODEX_LOCAL_INC | ${SED} -e 's/\$codex_documentation_prefix\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`
    BASEDOCDIR=$CODEX_DOCUMENTATION_PREFIX
fi

# honor BASESRCDIR if defined
if [ -z "$BASESRCDIR" ]; then
    CODEX_DIR=`${GREP} '^\$codex_dir' $CODEX_LOCAL_INC | ${SED} -e 's/\$codex_dir\s*=\s*\(.*\);\(.*\)/\1/' | tr -d '"' | tr -d "'"`
    BASESRCDIR=$CODEX_DIR
fi

if [ -z "$PHPDOC" ]; then
    echo "***ERROR: Please set PHPDOC variable to /path/to/phpdoc/phpdoc"
    exit 1
fi

programmer_guide_dir=${BASEDOCDIR}/programmer_guide

phpdoc_output=${programmer_guide_dir}/phpdoc

${BASESRCDIR}/src/utils/php-launcher -d output_buffering=1 ${PHPDOC} \
    --quiet on \
    --defaultpackagename "CodeX" \
    --title "CodeX Framework Documentation" \
    --target ${phpdoc_output} \
    --examplesdir ${programmer_guide_dir}/examples \
    --directory ${BASESRCDIR}/src/common/filter \
    --filename ${BASESRCDIR}/src/common/include/HTTPRequest.class.php,${BASESRCDIR}/src/common/include/CodeX_Request.class.php,${BASESRCDIR}/src/common/include/SOAPRequest.class.php

### Set good mime-type.
#
#find ${phpdoc_output} -name "*.html" -exec svn propset --quiet svn:mime-type text/html {} \;
#find ${phpdoc_output} -name "*.css" -exec svn propset --quiet  svn:mime-type text/css {} \;
