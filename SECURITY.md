# Security Policy

This repository is a community continuation of PocketMine-MP. It inherits a large
amount of upstream code, but it is **not** an official PocketMine-MP release and is
maintained independently. Please report issues to this project, not to the upstream
team, unless the issue also affects an unmodified upstream release and you have
already confirmed that with us.

## Supported versions

Security fixes are provided for the current successor line:

| Version | Bedrock version | Supported |
|---|---|---|
| `5.51.x` successor builds | 1.26.60 / protocol 2225 | Yes |
| Older upstream PocketMine-MP releases | various | No |

If you are unsure whether your build is current, check `src/VersionInfo.php` and the
release notes under `changelogs/`.

## Reporting a vulnerability

**Do not open a public issue, pull request or discussion for a security problem.**
The issue tracker is public, and a public report can put live servers at risk.

### Preferred: private GitHub advisory

1. Open the **Security** tab of this repository.
2. Choose **Report a vulnerability**.
3. Describe the issue privately. This creates a draft security advisory visible
   only to the maintainers and you.

### Alternative: private maintainer contact

If you cannot use GitHub advisories, contact a maintainer privately using the
contact information published in the repository profile or release notes. Do not
send exploit details to a public channel.

Please include:

- the affected version or commit;
- the Bedrock client version and protocol;
- a detailed description of the vulnerability and its impact;
- reproduction steps, configuration and, where possible, a proof of concept;
- whether the issue also reproduces on an unmodified upstream PocketMine-MP build;
- your name or handle if you want to be credited in an advisory.

## Scope

In scope:

- server core, networking, login/authentication and resource-pack handling;
- plugin loading, permissions and the plugin API boundary;
- world formats, chunk serialization and NBT handling;
- generated registries and Bedrock data integration;
- denial of service, crash and remote code execution issues in the server.

Out of scope:

- vulnerabilities in third-party plugins;
- vulnerabilities in Mojang's Bedrock Dedicated Server or game client;
- social engineering, spam and issues that require an already-compromised host;
- reports produced only by generic scanners without a demonstrated impact.

## Disclosure and timelines

- We aim to acknowledge a complete report within a few days.
- Fix timelines depend on severity and complexity; we cannot promise a general ETA.
- Please give us a reasonable window to investigate and ship a fix before public
  disclosure. We will coordinate a disclosure date with you.
- If the issue also affects upstream PocketMine-MP, we may coordinate with the
  upstream team. Upstream is not obliged to fix inherited code, and this project
  may ship a fix independently.

## Testing rules

Only test against servers and clients you own or have explicit permission to test.
Do not use exploits against production servers, and do not access data that does
not belong to you.

## Bug bounty

There is no bug bounty. Security reports are handled on a best-effort basis by
volunteer maintainers.
