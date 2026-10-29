# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Security Architecture

`OpenCart-AI` enforces a zero-trust model between AI clients and the production OpenCart database:

1. **Bearer Token Authentication:** Every MCP request is authenticated via cryptographically secure 256-bit Bearer tokens.
2. **IP Whitelisting:** Administrators can restrict MCP endpoint access to specific CIDR blocks or fixed static IP addresses.
3. **PII Masking by Default:** Customer personal details (first name, last name, phone, email, shipping addresses) are masked (e.g., `J*** D** <j***@example.com>`) preventing private data leaks to LLMs.
4. **Mutation Guard:** Destructive/modifying operations (updating prices, changing order statuses, adjusting inventory) are blocked by default unless explicitly toggled in the Admin Control Panel.
5. **Read-Only SQL Transaction Guard:** Where supported, queries are executed with read-only transaction isolation.

## Reporting a Vulnerability

If you discover any security issue, please do **NOT** open a public issue.
Instead, report it directly via GitHub Security Advisory or email the maintainer at [github.com/Anton-Barinov](https://github.com/Anton-Barinov).
