# pawa-wp-mcp Design Spec

**Date:** 2026-05-19  
**Status:** Approved

## Overview

`pawa-wp-mcp` is a locally-running MCP server (TypeScript/Node.js) that exposes WordPress site management as native tools for Claude Code. It manages 2–10 WP sites hosted on shared servers with no SSH or WP-CLI access — only REST API and FTP. A companion Claude Code skill tells Claude when and how to use each tool, including how to interpret Playwright screenshots for design analysis.

---

## Architecture

```
Claude Code
     │  MCP protocol
     ▼
pawa-wp-mcp  (local MCP server, Node.js/TypeScript)
     ├── Site Registry     sites.json (gitignored)
     ├── REST Client       WP REST API via Application Passwords
     ├── FTP Client        basic-ftp (passive mode)
     └── Playwright        headless browser for inspect/audit
```

The server runs as a persistent local process, registered in `~/.claude/mcp.json`. Claude calls tools directly — no Bash intermediary.

---

## Site Registry

Sites are defined in `sites.json` at the project root. The file is gitignored; `sites.example.json` is committed in its place.

```json
{
  "sites": {
    "mysite": {
      "url": "https://mysite.com",
      "rest_api_key": "wp_xxx",
      "rest_api_secret": "xxx",
      "ftp_host": "ftp.mysite.com",
      "ftp_user": "user",
      "ftp_pass": "pass",
      "ftp_root": "/public_html"
    }
  }
}
```

- Site keys are short aliases used in every tool call (`site: "mysite"`)
- WP REST auth uses **Application Passwords** (built into WP 5.6+, no plugin required)
- FTP uses `basic-ftp` in passive mode for shared hosting compatibility

---

## MCP Tools

All tools accept `site` (alias string) as their first parameter.

### Content

| Tool | Description |
|---|---|
| `wp_get_posts` | List posts/pages with filters: status, type, limit |
| `wp_get_post` | Get single post by ID including full content |
| `wp_create_post` | Create a post or page |
| `wp_update_post` | Update title, content, status, or meta |
| `wp_delete_post` | Trash or force-delete a post |
| `wp_get_media` | List media library items |

### Site Health

| Tool | Description |
|---|---|
| `wp_get_plugins` | List plugins with update availability |
| `wp_get_theme` | Active theme and available updates |
| `wp_get_site_info` | WP version, PHP version, site title, tagline |
| `wp_get_users` | List users with roles |

### File Operations (FTP)

| Tool | Description |
|---|---|
| `ftp_list` | List directory contents |
| `ftp_read` | Read a remote text file (config, template, etc.) |
| `ftp_upload` | Upload a local file to a remote path |
| `ftp_delete` | Delete a remote file |

### Inspect & Audit (Playwright)

| Tool | Description |
|---|---|
| `wp_inspect` | Screenshot a URL; returns base64 image Claude can see |
| `wp_audit` | Full page audit: screenshot + console errors + links returning 4xx/5xx + performance timing |
| `wp_compare` | Screenshot two URLs side-by-side (e.g. staging vs production) |

---

## Claude Code Skill

The skill is registered as a plugin at `~/.claude-personal/plugins/` and loaded into every Claude Code session. It is intentionally thin — behavioral guidance only, no implementation logic.

### Tool Awareness
Tells Claude the MCP server is registered as `pawa-wp-mcp` and describes the tool groups.

### Behavioral Rules
- Always use the site alias (`site: "mysite"`), never a raw URL
- Before answering any visual or design question, call `wp_inspect` to get a live screenshot
- For "something looks broken" reports, call `wp_audit` first — check console errors before theorizing
- When checking updates across all sites, fan out `wp_get_plugins` in parallel
- Never modify content or upload files without confirming the target site and action with the user

### Design Recommendation Guidance
When a screenshot is returned:
- Identify layout, typography, spacing, and contrast issues
- Suggest specific CSS/theme fixes, not vague advice
- Use `wp_compare` when a staging environment exists to diff against production

---

## Project Structure

```
pawa-wp-mcp/
├── src/
│   ├── index.ts          # MCP server entry point
│   ├── registry.ts       # Site registry loader
│   ├── tools/
│   │   ├── content.ts    # wp_get_posts, wp_create_post, etc.
│   │   ├── health.ts     # wp_get_plugins, wp_get_site_info, etc.
│   │   ├── ftp.ts        # ftp_list, ftp_read, ftp_upload, ftp_delete
│   │   └── inspect.ts    # wp_inspect, wp_audit, wp_compare
│   └── clients/
│       ├── wp-rest.ts    # Authenticated WP REST API client
│       ├── ftp.ts        # FTP client wrapper (basic-ftp)
│       └── browser.ts    # Playwright browser singleton
├── sites.json            # gitignored
├── sites.example.json    # committed
├── package.json
└── tsconfig.json
```

---

## Key Dependencies

| Package | Purpose |
|---|---|
| `@modelcontextprotocol/sdk` | MCP server framework |
| `basic-ftp` | FTP client (passive mode) |
| `playwright` | Headless browser for inspect/audit |
| `zod` | Tool parameter validation |

---

## Security Notes

- `sites.json` is gitignored — credentials never committed
- FTP passwords and API keys are loaded at runtime only
- No credentials are logged or returned in tool responses
- File deletion and content modification tools require explicit `site` confirmation in every call
