# Security

## Reporting a security issue in Tuleap

All security bugs in Tuleap should be reported by emailing security@tuleap.org.

A member of our security team will your vulnerability report within 3 business days, and you will receive a response
indicating the next steps in handling your report.

Vulnerabilities in third-party applications should be reported to their respective maintainers. The Tuleap security team
is not responsible for the security of these applications but will attempt to contact the third-party maintainer if an
issue is brought to his attention.

The provided email address to send vulnerability reports supports hop-by-hop encryption. If you feel the report
needs end-to-end encryption please reach out to us, we will try to find a solution accommodating everyone involved.

Any efforts made for improving the security of the Tuleap software or its users will be greatly appreciated by the
Tuleap community. If you want your disclosure will be publicly acknowledged in the public report. Please refrain from
requesting compensation for reporting vulnerabilities. At this time the Tuleap project does not deliver bounties.

Issues not affecting the security of the Tuleap software but of one of the services managed for the Tuleap community
(such as issues affecting the tuleap.org website) can be reported under the same guidelines but do not warrant a public
acknowledgment. SPF, DKIM, or DMARC issues in one of the services managed for the Tuleap community must not be reported.

## Coordinated Disclosure Guidelines

The Tuleap community would be grateful if security researchers comply with the following guidelines while researching
and reporting vulnerabilities:
 * Do not test for vulnerabilities on instances you do not own. Tuleap is an open-source software, you can install your
 own copy or use our [Docker image](https://hub.docker.com/r/tuleap/tuleap-community-edition) to quickly get a playground.
 * Confirm the vulnerability exists in the most recent stable or development version.
 * Allow the security team enough time to correct the reported vulnerability before publicly identifying or disclosing
it.

Our security team follows the following guidelines:
 * Vulnerability reports can take some time to be resolved but every effort will be made to handle a bug in as timely a
manner as possible.
 * Advisories for the reported vulnerabilities are made public in the
   [Tuleap bug report tracker](https://tuleap.net/plugins/tracker/?tracker=140). You can find more information on how we
   handle vulnerabilities in [our vulnerability response guide](./doc/vulnerability-management/vulnerability-response.md).
 * Security researchers and their findings are respected and will be publicly acknowledged if they wish.
 * No legal threats nor punitive actions against security researchers for reporting vulnerabilities will be made.
