FROM gerritcodereview/gerrit:2.16.3-centos7
COPY --chown=gerrit:gerrit *.config /
COPY run.sh /run.sh
USER root
CMD /run.sh
