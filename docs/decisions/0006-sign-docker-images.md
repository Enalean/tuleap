# Docker images signatures / Verify Docker images authenticity

* Status: accepted
* Deciders: Thomas GERBET
* Date: 2021-07-22

Technical Story: [Sign Docker images using Cosign](https://tuleap.net/plugins/tracker/?aid=22240)

## Context and Problem Statement

Having a way to verify the authenticity of the software artifacts you are using is an essential part of the software
supply chain security.

Today, the Tuleap RPM packages are signed to allow the users to verify their authenticity. To put it another way the goal
is to give some guarantees to the users that the packages have been built under the control of the Tuleap team and not by
a potentially malicious third party.

There is however no way to directly ensure the authenticity of the [Docker images](0004-tuleap-community-edition-docker-image.md).

Offering a way to ensure an artifact is "trustworthy" is considered a
[best practice](https://github.com/cncf/tag-security/blob/a9554db25c32adc640e4e99f151a9385026a0c1f/supply-chain-security/supply-chain-security-paper/CNCF_SSCP_v1.pdf)
and Docker images are an important part of the artifacts we distribute.

## Considered Options

* Use [Docker Content Trust / Notary](https://docs.docker.com/engine/security/trust/)
* Use [Cosign](https://github.com/sigstore/cosign)
* Wait for [Notary 2](https://github.com/notaryproject/nv2)

## Decision Outcome

Chosen option: "Use Cosign", because it is the only option available today that can be deployed easily. It also does not
prevent us to consider another option in the future if needed.

## Pros and Cons of the Options

### Use [Docker Content Trust / Notary](https://docs.docker.com/engine/security/trust/)

* Good, because it is the historic option to sign artifact in Docker registries
* Bad, because it is not so easy for us as publishers to put in place (key management is not trivial and there is no
  direct integration with a key management system like HashiCorp Vault)
* Bad, because it is also not so easy for users to consume the signatures
* Bad, because it will force us to deploy and maintain a Docker Notary service for the Tuleap Enterprise images

### Use [Cosign](https://github.com/sigstore/cosign)

* Good, because the integration with our existing key management system is built-in
* Good, because it is easy for users to manually verify the signatures (and it integrates well with automated mechanisms
  [0](https://docs.google.com/document/d/1d2Qm47wjjoyGDT8v3_ijB1Q4mGYV5cncAQoQniiR414)
  [1](https://github.com/developer-guy/container-image-sign-and-verify-with-cosign-and-opa))
* Good, because it is integrated (or will be) with the rest of the [sigstore](https://sigstore.dev/) initiative most
  notably with [Rekor](https://github.com/sigstore/rekor) the transparency log
* Bad, because it is still quite new, and we cannot determine if and how well it will be adopted by the community

### Wait for [Notary 2](https://github.com/notaryproject/nv2)

* Good, because it is expected to resolve some pain points encountered with Notary v1
* Bad, because not available today
