# pawa-wp-mcp Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a local TypeScript MCP server (`pawa-wp-mcp`) that exposes 15 WordPress management tools to Claude Code — covering content CRUD, site health, FTP file ops, and Playwright inspect/audit — plus a companion Claude Code skill.

**Architecture:** A Node.js MCP server using `@modelcontextprotocol/sdk` over stdio transport. Tools are grouped by domain (content, health, ftp, inspect) into separate modules, each exporting tool definitions and handlers. Three client singletons (WP REST, FTP, Playwright) are instantiated per-call using site config from a `sites.json` registry.

**Tech Stack:** Node.js 20+, TypeScript 5, tsx (dev runner), `@modelcontextprotocol/sdk`, `basic-ftp`, `playwright`, `zod`, `zod-to-json-schema`, vitest

---

## File Map

```
pawa-wp-mcp/
├── src/
│   ├── index.ts                  # MCP server entry, tool registration, stdio transport
│   ├── registry.ts               # Load sites.json, resolve site by alias
│   ├── clients/
│   │   ├── wp-rest.ts            # WP REST API client (Application Password auth)
│   │   ├── ftp.ts                # basic-ftp wrapper
│   │   └── browser.ts            # Playwright singleton (screenshot, audit, compare)
│   └── tools/
│       ├── content.ts            # wp_get_posts, wp_get_post, wp_create_post, wp_update_post, wp_delete_post, wp_get_media
│       ├── health.ts             # wp_get_plugins, wp_get_theme, wp_get_site_info, wp_get_users
│       ├── ftp.ts                # ftp_list, ftp_read, ftp_upload, ftp_delete
│       └── inspect.ts            # wp_inspect, wp_audit, wp_compare
├── tests/
│   ├── registry.test.ts
│   ├── clients/
│   │   ├── wp-rest.test.ts
│   │   └── ftp.test.ts
│   └── tools/
│       ├── content.test.ts
│       ├── health.test.ts
│       ├── ftp-tools.test.ts
│       └── inspect.test.ts
├── skills/
│   └── wp-manager.md             # Claude Code skill (install to ~/.claude-personal/plugins/pawa-wp/skills/)
├── sites.json                    # gitignored — real credentials
├── sites.example.json            # committed — shape reference
├── .gitignore
├── package.json
├── tsconfig.json
├── vitest.config.ts
└── README.md
```

---

## Task 1: Project Scaffold

**Files:**
- Create: `pawa-wp-mcp/package.json`
- Create: `pawa-wp-mcp/tsconfig.json`
- Create: `pawa-wp-mcp/vitest.config.ts`
- Create: `pawa-wp-mcp/.gitignore`
- Create: `pawa-wp-mcp/sites.example.json`

- [ ] **Step 1: Create the project directory and package.json**

```bash
mkdir -p /home/pixel/projects/personal/wp/pawa-wp-mcp
cd /home/pixel/projects/personal/wp/pawa-wp-mcp
```

Create `package.json`:
```json
{
  "name": "pawa-wp-mcp",
  "version": "1.0.0",
  "type": "module",
  "scripts": {
    "start": "tsx src/index.ts",
    "test": "vitest run",
    "test:watch": "vitest"
  },
  "dependencies": {
    "@modelcontextprotocol/sdk": "^1.0.0",
    "basic-ftp": "^5.0.5",
    "playwright": "^1.48.0",
    "zod": "^3.23.0",
    "zod-to-json-schema": "^3.23.0"
  },
  "devDependencies": {
    "@types/node": "^22.0.0",
    "tsx": "^4.19.0",
    "typescript": "^5.6.0",
    "vitest": "^2.1.0"
  }
}
```

- [ ] **Step 2: Create tsconfig.json**

```json
{
  "compilerOptions": {
    "target": "ES2022",
    "module": "ESNext",
    "moduleResolution": "bundler",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "outDir": "dist"
  },
  "include": ["src/**/*", "tests/**/*"]
}
```

- [ ] **Step 3: Create vitest.config.ts**

```typescript
import { defineConfig } from "vitest/config";
export default defineConfig({ test: { environment: "node" } });
```

- [ ] **Step 4: Create .gitignore**

```
node_modules/
dist/
sites.json
.playwright/
```

- [ ] **Step 5: Create sites.example.json**

```json
{
  "sites": {
    "mysite": {
      "url": "https://mysite.com",
      "rest_api_key": "wp_your_application_password_username",
      "rest_api_secret": "xxxx xxxx xxxx xxxx xxxx xxxx",
      "ftp_host": "ftp.mysite.com",
      "ftp_user": "ftpuser",
      "ftp_pass": "ftppassword",
      "ftp_root": "/public_html"
    }
  }
}
```

- [ ] **Step 6: Install dependencies**

```bash
npm install
npx playwright install chromium
```

Expected: `node_modules/` created, chromium browser downloaded.

- [ ] **Step 7: Create src/ and tests/ directories**

```bash
mkdir -p src/clients src/tools tests/clients tests/tools skills
```

- [ ] **Step 8: Commit**

```bash
git init
git add package.json tsconfig.json vitest.config.ts .gitignore sites.example.json
git commit -m "feat: scaffold pawa-wp-mcp project"
```

---

## Task 2: Site Registry

**Files:**
- Create: `tests/registry.test.ts`
- Create: `src/registry.ts`

- [ ] **Step 1: Write the failing test**

Create `tests/registry.test.ts`:
```typescript
import { describe, it, expect, beforeEach, afterEach } from "vitest";
import { writeFileSync, unlinkSync } from "fs";
import { createRegistry } from "../src/registry";

const FIXTURE = "/tmp/pawa-wp-test-sites.json";
const SITES_DATA = {
  sites: {
    mysite: {
      url: "https://mysite.com",
      rest_api_key: "key",
      rest_api_secret: "secret",
      ftp_host: "ftp.mysite.com",
      ftp_user: "user",
      ftp_pass: "pass",
      ftp_root: "/public_html",
    },
  },
};

beforeEach(() => writeFileSync(FIXTURE, JSON.stringify(SITES_DATA)));
afterEach(() => unlinkSync(FIXTURE));

describe("createRegistry", () => {
  it("returns site config for a valid alias", () => {
    const reg = createRegistry(FIXTURE);
    expect(reg.getSite("mysite").url).toBe("https://mysite.com");
  });

  it("throws a descriptive error for an unknown alias", () => {
    const reg = createRegistry(FIXTURE);
    expect(() => reg.getSite("unknown")).toThrow('Unknown site: "unknown". Available: mysite');
  });

  it("lists all site aliases", () => {
    const reg = createRegistry(FIXTURE);
    expect(reg.listSites()).toEqual(["mysite"]);
  });
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
npm test -- tests/registry.test.ts
```

Expected: FAIL — `Cannot find module '../src/registry'`

- [ ] **Step 3: Implement src/registry.ts**

```typescript
import { readFileSync } from "fs";

export interface SiteConfig {
  url: string;
  rest_api_key: string;
  rest_api_secret: string;
  ftp_host: string;
  ftp_user: string;
  ftp_pass: string;
  ftp_root: string;
}

export interface Registry {
  getSite(alias: string): SiteConfig;
  listSites(): string[];
}

export function createRegistry(sitesJsonPath: string): Registry {
  const { sites } = JSON.parse(readFileSync(sitesJsonPath, "utf-8")) as {
    sites: Record<string, SiteConfig>;
  };
  return {
    getSite(alias) {
      const site = sites[alias];
      if (!site) {
        throw new Error(
          `Unknown site: "${alias}". Available: ${Object.keys(sites).join(", ")}`
        );
      }
      return site;
    },
    listSites: () => Object.keys(sites),
  };
}
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
npm test -- tests/registry.test.ts
```

Expected: 3 tests PASS

- [ ] **Step 5: Commit**

```bash
git add src/registry.ts tests/registry.test.ts
git commit -m "feat: site registry with alias lookup"
```

---

## Task 3: WP REST API Client

**Files:**
- Create: `tests/clients/wp-rest.test.ts`
- Create: `src/clients/wp-rest.ts`

- [ ] **Step 1: Write the failing test**

Create `tests/clients/wp-rest.test.ts`:
```typescript
import { describe, it, expect, vi, beforeEach } from "vitest";
import { WpRestClient } from "../../src/clients/wp-rest";
import type { SiteConfig } from "../../src/registry";

const mockFetch = vi.fn();
vi.stubGlobal("fetch", mockFetch);

const site: SiteConfig = {
  url: "https://mysite.com",
  rest_api_key: "user",
  rest_api_secret: "pass",
  ftp_host: "ftp.mysite.com",
  ftp_user: "u",
  ftp_pass: "p",
  ftp_root: "/public_html",
};

function mockOk(body: unknown) {
  mockFetch.mockResolvedValueOnce({
    ok: true,
    json: async () => body,
    text: async () => JSON.stringify(body),
  });
}

function mockFail(status: number) {
  mockFetch.mockResolvedValueOnce({ ok: false, status, text: async () => "Forbidden" });
}

beforeEach(() => mockFetch.mockClear());

describe("WpRestClient", () => {
  it("getPosts sends authenticated request to /wp/v2/posts", async () => {
    mockOk([{ id: 1, title: { rendered: "Hello" }, status: "publish", type: "post", link: "https://mysite.com/hello", date: "2024-01-01", content: { rendered: "" } }]);
    const client = new WpRestClient(site);
    const posts = await client.getPosts();
    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining("/wp/v2/posts"),
      expect.objectContaining({ headers: expect.objectContaining({ Authorization: expect.stringContaining("Basic ") }) })
    );
    expect(posts[0].id).toBe(1);
  });

  it("getPosts routes to /wp/v2/pages when type is page", async () => {
    mockOk([]);
    await new WpRestClient(site).getPosts({ type: "page" });
    expect(mockFetch).toHaveBeenCalledWith(expect.stringContaining("/wp/v2/pages"), expect.anything());
  });

  it("getPost fetches single post by id", async () => {
    mockOk({ id: 5, title: { rendered: "Post 5" }, content: { rendered: "<p>Hi</p>" }, status: "publish", type: "post", link: "https://mysite.com/5", date: "2024-01-01" });
    const post = await new WpRestClient(site).getPost(5);
    expect(post.id).toBe(5);
    expect(mockFetch).toHaveBeenCalledWith(expect.stringContaining("/posts/5"), expect.anything());
  });

  it("createPost sends POST with body", async () => {
    mockOk({ id: 10, title: { rendered: "New" }, status: "draft", type: "post", link: "https://mysite.com/new", date: "2024-01-01", content: { rendered: "" } });
    const post = await new WpRestClient(site).createPost({ title: "New", content: "body" });
    expect(mockFetch).toHaveBeenCalledWith(
      expect.stringContaining("/posts"),
      expect.objectContaining({ method: "POST" })
    );
    expect(post.id).toBe(10);
  });

  it("updatePost sends POST to /posts/:id", async () => {
    mockOk({ id: 3, title: { rendered: "Updated" }, status: "publish", type: "post", link: "https://mysite.com/3", date: "2024-01-01", content: { rendered: "" } });
    await new WpRestClient(site).updatePost(3, { title: "Updated" });
    expect(mockFetch).toHaveBeenCalledWith(expect.stringContaining("/posts/3"), expect.objectContaining({ method: "POST" }));
  });

  it("deletePost sends DELETE to /posts/:id", async () => {
    mockOk({});
    await new WpRestClient(site).deletePost(7);
    expect(mockFetch).toHaveBeenCalledWith(expect.stringContaining("/posts/7"), expect.objectContaining({ method: "DELETE" }));
  });

  it("getPlugins fetches /wp/v2/plugins", async () => {
    mockOk([{ plugin: "akismet/akismet", name: "Akismet", status: "active", version: "5.0", update: null }]);
    const plugins = await new WpRestClient(site).getPlugins();
    expect(plugins[0].name).toBe("Akismet");
    expect(mockFetch).toHaveBeenCalledWith(expect.stringContaining("/plugins"), expect.anything());
  });

  it("getTheme returns the first active theme", async () => {
    mockOk([{ stylesheet: "twentytwentyfour", name: { rendered: "Twenty Twenty-Four" }, version: "1.0", status: "active" }]);
    const theme = await new WpRestClient(site).getTheme();
    expect(theme.stylesheet).toBe("twentytwentyfour");
  });

  it("getTheme throws when no active theme is returned", async () => {
    mockOk([]);
    await expect(new WpRestClient(site).getTheme()).rejects.toThrow("No active theme found");
  });

  it("getSiteInfo fetches the root wp-json endpoint", async () => {
    mockOk({ name: "My Site", description: "A blog", url: "https://mysite.com" });
    const info = await new WpRestClient(site).getSiteInfo();
    expect(info.name).toBe("My Site");
    expect(mockFetch).toHaveBeenCalledWith(expect.stringContaining("/wp-json"), expect.anything());
  });

  it("getUsers fetches /wp/v2/users", async () => {
    mockOk([{ id: 1, name: "Admin", email: "admin@mysite.com", roles: ["administrator"] }]);
    const users = await new WpRestClient(site).getUsers();
    expect(users[0].roles).toContain("administrator");
  });

  it("throws with status code on non-ok response", async () => {
    mockFail(403);
    await expect(new WpRestClient(site).getPosts()).rejects.toThrow("403");
  });
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
npm test -- tests/clients/wp-rest.test.ts
```

Expected: FAIL — `Cannot find module '../../src/clients/wp-rest'`

- [ ] **Step 3: Implement src/clients/wp-rest.ts**

```typescript
import type { SiteConfig } from "../registry.js";

export interface WpPost {
  id: number;
  title: { rendered: string };
  content: { rendered: string };
  status: string;
  type: string;
  link: string;
  date: string;
}

export interface WpPlugin {
  plugin: string;
  name: string;
  status: "active" | "inactive";
  version: string;
  update: { version?: string } | null;
}

export interface WpUser {
  id: number;
  name: string;
  email: string;
  roles: string[];
}

export interface WpTheme {
  stylesheet: string;
  name: { rendered: string };
  version: string;
  status: string;
}

export interface WpMediaItem {
  id: number;
  title: { rendered: string };
  source_url: string;
  mime_type: string;
  date: string;
}

export class WpRestClient {
  private readonly v2: string;
  private readonly rootUrl: string;
  private readonly authHeader: string;

  constructor(site: SiteConfig) {
    const base = site.url.replace(/\/$/, "");
    this.rootUrl = `${base}/wp-json`;
    this.v2 = `${base}/wp-json/wp/v2`;
    this.authHeader = `Basic ${Buffer.from(
      `${site.rest_api_key}:${site.rest_api_secret}`
    ).toString("base64")}`;
  }

  private async req<T>(url: string, init: RequestInit = {}): Promise<T> {
    const res = await fetch(url, {
      ...init,
      headers: {
        Authorization: this.authHeader,
        "Content-Type": "application/json",
        ...(init.headers as Record<string, string>),
      },
    });
    if (!res.ok) {
      throw new Error(
        `WP REST ${init.method ?? "GET"} ${url} → ${res.status}: ${await res.text()}`
      );
    }
    return res.json() as Promise<T>;
  }

  private ep(type: "post" | "page"): string {
    return type === "page" ? "pages" : "posts";
  }

  async getPosts(
    params: { type?: "post" | "page"; status?: string; per_page?: number } = {}
  ): Promise<WpPost[]> {
    const { type = "post", status = "publish", per_page = 10 } = params;
    return this.req<WpPost[]>(
      `${this.v2}/${this.ep(type)}?status=${status}&per_page=${per_page}`
    );
  }

  async getPost(id: number, type: "post" | "page" = "post"): Promise<WpPost> {
    return this.req<WpPost>(`${this.v2}/${this.ep(type)}/${id}`);
  }

  async createPost(data: {
    title: string;
    content: string;
    status?: string;
    type?: "post" | "page";
  }): Promise<WpPost> {
    const { type = "post", ...body } = data;
    return this.req<WpPost>(`${this.v2}/${this.ep(type)}`, {
      method: "POST",
      body: JSON.stringify(body),
    });
  }

  async updatePost(
    id: number,
    data: { title?: string; content?: string; status?: string },
    type: "post" | "page" = "post"
  ): Promise<WpPost> {
    return this.req<WpPost>(`${this.v2}/${this.ep(type)}/${id}`, {
      method: "POST",
      body: JSON.stringify(data),
    });
  }

  async deletePost(
    id: number,
    force = false,
    type: "post" | "page" = "post"
  ): Promise<void> {
    await this.req(`${this.v2}/${this.ep(type)}/${id}?force=${force}`, {
      method: "DELETE",
    });
  }

  async getMedia(per_page = 10): Promise<WpMediaItem[]> {
    return this.req<WpMediaItem[]>(`${this.v2}/media?per_page=${per_page}`);
  }

  async getPlugins(): Promise<WpPlugin[]> {
    return this.req<WpPlugin[]>(`${this.v2}/plugins`);
  }

  async getTheme(): Promise<WpTheme> {
    const themes = await this.req<WpTheme[]>(`${this.v2}/themes?status=active`);
    if (!themes[0]) throw new Error("No active theme found");
    return themes[0];
  }

  async getSiteInfo(): Promise<{ name: string; description: string; url: string }> {
    return this.req<{ name: string; description: string; url: string }>(this.rootUrl);
  }

  async getUsers(per_page = 20): Promise<WpUser[]> {
    return this.req<WpUser[]>(`${this.v2}/users?per_page=${per_page}`);
  }
}
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
npm test -- tests/clients/wp-rest.test.ts
```

Expected: 12 tests PASS

- [ ] **Step 5: Commit**

```bash
git add src/clients/wp-rest.ts tests/clients/wp-rest.test.ts
git commit -m "feat: WP REST API client with Application Password auth"
```

---

## Task 4: FTP Client

**Files:**
- Create: `tests/clients/ftp.test.ts`
- Create: `src/clients/ftp.ts`

- [ ] **Step 1: Write the failing test**

Create `tests/clients/ftp.test.ts`:
```typescript
import { describe, it, expect, vi, beforeEach } from "vitest";
import type { SiteConfig } from "../../src/registry";

const mockAccess = vi.fn().mockResolvedValue(undefined);
const mockList = vi.fn().mockResolvedValue([]);
const mockDownloadTo = vi.fn().mockResolvedValue(undefined);
const mockUploadFrom = vi.fn().mockResolvedValue(undefined);
const mockRemove = vi.fn().mockResolvedValue(undefined);
const mockClose = vi.fn();

vi.mock("basic-ftp", () => ({
  Client: vi.fn().mockImplementation(() => ({
    access: mockAccess,
    list: mockList,
    downloadTo: mockDownloadTo,
    uploadFrom: mockUploadFrom,
    remove: mockRemove,
    close: mockClose,
  })),
}));

// import AFTER mock is registered
const { FtpClient } = await import("../../src/clients/ftp");

const site: SiteConfig = {
  url: "https://mysite.com",
  rest_api_key: "k",
  rest_api_secret: "s",
  ftp_host: "ftp.mysite.com",
  ftp_user: "user",
  ftp_pass: "pass",
  ftp_root: "/public_html",
};

beforeEach(() => {
  vi.clearAllMocks();
  mockList.mockResolvedValue([]);
  mockDownloadTo.mockResolvedValue(undefined);
  mockUploadFrom.mockResolvedValue(undefined);
  mockRemove.mockResolvedValue(undefined);
});

describe("FtpClient", () => {
  it("list connects with site credentials and lists the resolved path", async () => {
    mockList.mockResolvedValueOnce([
      { name: "wp-config.php", size: 3000, isDirectory: false, modifiedAt: new Date("2024-01-01") },
    ]);
    const client = new FtpClient(site);
    const items = await client.list("/");
    expect(mockAccess).toHaveBeenCalledWith(
      expect.objectContaining({ host: "ftp.mysite.com", user: "user", password: "pass" })
    );
    expect(mockList).toHaveBeenCalledWith("/public_html/");
    expect(items[0]).toMatchObject({ name: "wp-config.php", type: "file", size: 3000 });
  });

  it("list maps directories correctly", async () => {
    mockList.mockResolvedValueOnce([
      { name: "wp-content", size: 0, isDirectory: true, modifiedAt: new Date() },
    ]);
    const items = await new FtpClient(site).list("/");
    expect(items[0].type).toBe("directory");
  });

  it("read returns file content as string", async () => {
    mockDownloadTo.mockImplementationOnce(async (stream: NodeJS.WritableStream) => {
      stream.write(Buffer.from("<?php // config"));
      stream.end();
    });
    const content = await new FtpClient(site).read("/wp-config.php");
    expect(content).toBe("<?php // config");
    expect(mockDownloadTo).toHaveBeenCalledWith(expect.anything(), "/public_html/wp-config.php");
  });

  it("upload calls uploadFrom with resolved remote path", async () => {
    await new FtpClient(site).upload("/local/file.txt", "/theme/file.txt");
    expect(mockUploadFrom).toHaveBeenCalledWith("/local/file.txt", "/public_html/theme/file.txt");
  });

  it("delete calls remove with resolved remote path", async () => {
    await new FtpClient(site).delete("/old-file.php");
    expect(mockRemove).toHaveBeenCalledWith("/public_html/old-file.php");
  });

  it("always closes the FTP connection after each operation", async () => {
    mockList.mockResolvedValueOnce([]);
    await new FtpClient(site).list("/");
    expect(mockClose).toHaveBeenCalledOnce();
  });
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
npm test -- tests/clients/ftp.test.ts
```

Expected: FAIL — `Cannot find module '../../src/clients/ftp'`

- [ ] **Step 3: Implement src/clients/ftp.ts**

```typescript
import { Client } from "basic-ftp";
import type { SiteConfig } from "../registry.js";

export interface FtpFileItem {
  name: string;
  size: number;
  type: "file" | "directory";
  date: Date;
}

export class FtpClient {
  constructor(private readonly config: SiteConfig) {}

  private remotePath(path: string): string {
    return this.config.ftp_root.replace(/\/$/, "") + "/" + path.replace(/^\//, "");
  }

  private async withClient<T>(fn: (c: Client) => Promise<T>): Promise<T> {
    const client = new Client();
    try {
      await client.access({
        host: this.config.ftp_host,
        user: this.config.ftp_user,
        password: this.config.ftp_pass,
        secure: false,
      });
      return await fn(client);
    } finally {
      client.close();
    }
  }

  async list(path: string): Promise<FtpFileItem[]> {
    return this.withClient(async (c) => {
      const items = await c.list(this.remotePath(path));
      return items.map((i) => ({
        name: i.name,
        size: i.size,
        type: i.isDirectory ? ("directory" as const) : ("file" as const),
        date: i.modifiedAt ?? new Date(),
      }));
    });
  }

  async read(path: string): Promise<string> {
    return this.withClient(async (c) => {
      const chunks: Buffer[] = [];
      const { Writable } = await import("stream");
      const stream = new Writable({
        write(chunk, _, cb) {
          chunks.push(Buffer.from(chunk as Buffer));
          cb();
        },
      });
      await c.downloadTo(stream, this.remotePath(path));
      return Buffer.concat(chunks).toString("utf-8");
    });
  }

  async upload(localPath: string, remotePath: string): Promise<void> {
    return this.withClient((c) => c.uploadFrom(localPath, this.remotePath(remotePath)));
  }

  async delete(path: string): Promise<void> {
    return this.withClient((c) => c.remove(this.remotePath(path)));
  }
}
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
npm test -- tests/clients/ftp.test.ts
```

Expected: 6 tests PASS

- [ ] **Step 5: Commit**

```bash
git add src/clients/ftp.ts tests/clients/ftp.test.ts
git commit -m "feat: FTP client wrapper using basic-ftp"
```

---

## Task 5: Playwright Browser Client

**Files:**
- Create: `src/clients/browser.ts`

No unit tests here — Playwright requires a real browser. We validate it works in Task 9 (inspect tool integration test).

- [ ] **Step 1: Implement src/clients/browser.ts**

```typescript
import { type Browser, chromium } from "playwright";

let browserInstance: Browser | null = null;

async function getBrowser(): Promise<Browser> {
  if (!browserInstance) {
    browserInstance = await chromium.launch({ headless: true });
  }
  return browserInstance;
}

export async function closeBrowser(): Promise<void> {
  if (browserInstance) {
    await browserInstance.close();
    browserInstance = null;
  }
}

export async function takeScreenshot(url: string): Promise<string> {
  const page = await (await getBrowser()).newPage();
  try {
    await page.goto(url, { waitUntil: "networkidle" });
    return (await page.screenshot({ fullPage: true })).toString("base64");
  } finally {
    await page.close();
  }
}

export interface AuditResult {
  screenshot: string;
  consoleErrors: string[];
  brokenLinks: { url: string; status: number }[];
  performanceTiming: { loadTime: number; domContentLoaded: number };
}

export async function auditPage(url: string): Promise<AuditResult> {
  const page = await (await getBrowser()).newPage();
  const consoleErrors: string[] = [];
  const brokenLinks: { url: string; status: number }[] = [];

  page.on("console", (m) => {
    if (m.type() === "error") consoleErrors.push(m.text());
  });
  page.on("response", (r) => {
    if (r.status() >= 400) brokenLinks.push({ url: r.url(), status: r.status() });
  });

  try {
    const t0 = Date.now();
    await page.goto(url, { waitUntil: "networkidle" });
    const loadTime = Date.now() - t0;
    const domContentLoaded: number = await page.evaluate(() => {
      const [nav] = performance.getEntriesByType(
        "navigation"
      ) as PerformanceNavigationTiming[];
      return Math.round(nav.domContentLoadedEventEnd - nav.startTime);
    });
    const screenshot = (await page.screenshot({ fullPage: true })).toString("base64");
    return { screenshot, consoleErrors, brokenLinks, performanceTiming: { loadTime, domContentLoaded } };
  } finally {
    await page.close();
  }
}

export async function comparePages(
  urlA: string,
  urlB: string
): Promise<{ a: string; b: string }> {
  const [a, b] = await Promise.all([takeScreenshot(urlA), takeScreenshot(urlB)]);
  return { a, b };
}
```

- [ ] **Step 2: Smoke test the browser client**

```bash
node --import tsx/esm -e "
import { takeScreenshot, closeBrowser } from './src/clients/browser.ts';
const b64 = await takeScreenshot('https://example.com');
console.log('Screenshot bytes:', Buffer.from(b64, 'base64').length);
await closeBrowser();
"
```

Expected: `Screenshot bytes: <number above 10000>` — confirms Playwright launches and captures pages.

- [ ] **Step 3: Commit**

```bash
git add src/clients/browser.ts
git commit -m "feat: Playwright browser client for screenshot and audit"
```

---

## Task 6: Content Tools

**Files:**
- Create: `tests/tools/content.test.ts`
- Create: `src/tools/content.ts`

- [ ] **Step 1: Write the failing test**

Create `tests/tools/content.test.ts`:
```typescript
import { describe, it, expect, vi, beforeEach } from "vitest";
import type { Registry, SiteConfig } from "../../src/registry";

const siteConfig: SiteConfig = {
  url: "https://mysite.com", rest_api_key: "k", rest_api_secret: "s",
  ftp_host: "ftp.mysite.com", ftp_user: "u", ftp_pass: "p", ftp_root: "/public_html",
};

const mockGetPosts = vi.fn();
const mockGetPost = vi.fn();
const mockCreatePost = vi.fn();
const mockUpdatePost = vi.fn();
const mockDeletePost = vi.fn();
const mockGetMedia = vi.fn();

vi.mock("../../src/clients/wp-rest.js", () => ({
  WpRestClient: vi.fn().mockImplementation(() => ({
    getPosts: mockGetPosts,
    getPost: mockGetPost,
    createPost: mockCreatePost,
    updatePost: mockUpdatePost,
    deletePost: mockDeletePost,
    getMedia: mockGetMedia,
  })),
}));

const { contentToolHandlers } = await import("../../src/tools/content");

const registry: Registry = {
  getSite: vi.fn().mockReturnValue(siteConfig),
  listSites: vi.fn().mockReturnValue(["mysite"]),
};

beforeEach(() => vi.clearAllMocks());

describe("wp_get_posts", () => {
  it("returns formatted post list", async () => {
    mockGetPosts.mockResolvedValueOnce([
      { id: 1, title: { rendered: "Hello" }, status: "publish", date: "2024-01-01", link: "https://mysite.com/hello", content: { rendered: "" }, type: "post" },
    ]);
    const handlers = contentToolHandlers(registry);
    const result = await handlers.wp_get_posts({ site: "mysite" });
    expect(result.isError).toBeUndefined();
    const parsed = JSON.parse((result.content[0] as { type: "text"; text: string }).text);
    expect(parsed[0].id).toBe(1);
    expect(parsed[0].title).toBe("Hello");
  });

  it("returns error content on REST failure", async () => {
    mockGetPosts.mockRejectedValueOnce(new Error("401: Unauthorized"));
    const result = await contentToolHandlers(registry).wp_get_posts({ site: "mysite" });
    expect(result.isError).toBe(true);
    expect((result.content[0] as { type: "text"; text: string }).text).toContain("401");
  });

  it("returns error on invalid schema (missing site)", async () => {
    const result = await contentToolHandlers(registry).wp_get_posts({});
    expect(result.isError).toBe(true);
  });
});

describe("wp_get_post", () => {
  it("returns single post with content", async () => {
    mockGetPost.mockResolvedValueOnce({ id: 5, title: { rendered: "Post 5" }, content: { rendered: "<p>Hi</p>" }, status: "publish", date: "2024-01-01", link: "https://mysite.com/5", type: "post" });
    const result = await contentToolHandlers(registry).wp_get_post({ site: "mysite", id: 5 });
    const parsed = JSON.parse((result.content[0] as { type: "text"; text: string }).text);
    expect(parsed.content).toBe("<p>Hi</p>");
  });
});

describe("wp_create_post", () => {
  it("returns created post summary", async () => {
    mockCreatePost.mockResolvedValueOnce({ id: 10, title: { rendered: "New Post" }, status: "draft", link: "https://mysite.com/new" });
    const result = await contentToolHandlers(registry).wp_create_post({ site: "mysite", title: "New Post", content: "body" });
    expect((result.content[0] as { type: "text"; text: string }).text).toContain("#10");
  });
});

describe("wp_update_post", () => {
  it("returns updated post summary", async () => {
    mockUpdatePost.mockResolvedValueOnce({ id: 3, title: { rendered: "Updated" }, status: "publish" });
    const result = await contentToolHandlers(registry).wp_update_post({ site: "mysite", id: 3, title: "Updated" });
    expect((result.content[0] as { type: "text"; text: string }).text).toContain("#3");
  });
});

describe("wp_delete_post", () => {
  it("reports trash when force is false", async () => {
    mockDeletePost.mockResolvedValueOnce(undefined);
    const result = await contentToolHandlers(registry).wp_delete_post({ site: "mysite", id: 7 });
    expect((result.content[0] as { type: "text"; text: string }).text).toContain("trash");
  });

  it("reports permanent delete when force is true", async () => {
    mockDeletePost.mockResolvedValueOnce(undefined);
    const result = await contentToolHandlers(registry).wp_delete_post({ site: "mysite", id: 7, force: true });
    expect((result.content[0] as { type: "text"; text: string }).text).toContain("permanently deleted");
  });
});

describe("wp_get_media", () => {
  it("returns media list", async () => {
    mockGetMedia.mockResolvedValueOnce([
      { id: 1, title: { rendered: "Banner" }, source_url: "https://mysite.com/banner.jpg", mime_type: "image/jpeg", date: "2024-01-01" },
    ]);
    const result = await contentToolHandlers(registry).wp_get_media({ site: "mysite" });
    const parsed = JSON.parse((result.content[0] as { type: "text"; text: string }).text);
    expect(parsed[0].url).toBe("https://mysite.com/banner.jpg");
  });
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
npm test -- tests/tools/content.test.ts
```

Expected: FAIL — `Cannot find module '../../src/tools/content'`

- [ ] **Step 3: Implement src/tools/content.ts**

```typescript
import { z } from "zod";
import { zodToJsonSchema } from "zod-to-json-schema";
import type { Registry } from "../registry.js";
import { WpRestClient } from "../clients/wp-rest.js";

const SiteParam = z.string().min(1).describe("Site alias from sites.json");
const PostType = z.enum(["post", "page"]).default("post");

const GetPostsSchema = z.object({
  site: SiteParam,
  type: PostType,
  status: z.enum(["publish", "draft", "pending", "private"]).default("publish"),
  limit: z.number().int().min(1).max(100).default(10),
});
const GetPostSchema = z.object({ site: SiteParam, id: z.number().int().positive(), type: PostType });
const CreatePostSchema = z.object({
  site: SiteParam, title: z.string().min(1), content: z.string(),
  status: z.enum(["publish", "draft", "pending", "private"]).default("draft"), type: PostType,
});
const UpdatePostSchema = z.object({
  site: SiteParam, id: z.number().int().positive(), type: PostType,
  title: z.string().optional(), content: z.string().optional(),
  status: z.enum(["publish", "draft", "pending", "private"]).optional(),
});
const DeletePostSchema = z.object({
  site: SiteParam, id: z.number().int().positive(), type: PostType, force: z.boolean().default(false),
});
const GetMediaSchema = z.object({ site: SiteParam, limit: z.number().int().min(1).max(100).default(10) });

function toSchema(s: z.ZodType): Record<string, unknown> {
  const schema = zodToJsonSchema(s) as Record<string, unknown>;
  delete schema.$schema;
  return schema;
}

type TextContent = { type: "text"; text: string };
type ToolResponse = { content: TextContent[]; isError?: boolean };
const ok = (text: string): ToolResponse => ({ content: [{ type: "text", text }] });
const fail = (e: unknown): ToolResponse => ({
  content: [{ type: "text", text: `Error: ${e instanceof Error ? e.message : String(e)}` }],
  isError: true,
});

export function contentToolDefinitions() {
  return [
    { name: "wp_get_posts", description: "List posts or pages from a WordPress site", inputSchema: toSchema(GetPostsSchema) },
    { name: "wp_get_post", description: "Get a single post or page by ID including full content", inputSchema: toSchema(GetPostSchema) },
    { name: "wp_create_post", description: "Create a new post or page on a WordPress site", inputSchema: toSchema(CreatePostSchema) },
    { name: "wp_update_post", description: "Update an existing post or page", inputSchema: toSchema(UpdatePostSchema) },
    { name: "wp_delete_post", description: "Delete a post or page (trash or force-delete)", inputSchema: toSchema(DeletePostSchema) },
    { name: "wp_get_media", description: "List media library items from a WordPress site", inputSchema: toSchema(GetMediaSchema) },
  ];
}

export function contentToolHandlers(
  registry: Registry
): Record<string, (args: unknown) => Promise<ToolResponse>> {
  return {
    wp_get_posts: async (args) => {
      try {
        const { site, type, status, limit } = GetPostsSchema.parse(args);
        const posts = await new WpRestClient(registry.getSite(site)).getPosts({ type, status, per_page: limit });
        return ok(JSON.stringify(posts.map((p) => ({ id: p.id, title: p.title.rendered, status: p.status, date: p.date, link: p.link })), null, 2));
      } catch (e) { return fail(e); }
    },
    wp_get_post: async (args) => {
      try {
        const { site, id, type } = GetPostSchema.parse(args);
        const p = await new WpRestClient(registry.getSite(site)).getPost(id, type);
        return ok(JSON.stringify({ id: p.id, title: p.title.rendered, content: p.content.rendered, status: p.status, date: p.date, link: p.link }, null, 2));
      } catch (e) { return fail(e); }
    },
    wp_create_post: async (args) => {
      try {
        const { site, title, content, status, type } = CreatePostSchema.parse(args);
        const p = await new WpRestClient(registry.getSite(site)).createPost({ title, content, status, type });
        return ok(`Created ${type} #${p.id}: "${p.title.rendered}" (${p.status})\n${p.link}`);
      } catch (e) { return fail(e); }
    },
    wp_update_post: async (args) => {
      try {
        const { site, id, type, ...updates } = UpdatePostSchema.parse(args);
        const p = await new WpRestClient(registry.getSite(site)).updatePost(id, updates, type);
        return ok(`Updated ${type} #${p.id}: "${p.title.rendered}" (${p.status})`);
      } catch (e) { return fail(e); }
    },
    wp_delete_post: async (args) => {
      try {
        const { site, id, force, type } = DeletePostSchema.parse(args);
        await new WpRestClient(registry.getSite(site)).deletePost(id, force, type);
        return ok(`${type} #${id} ${force ? "permanently deleted" : "moved to trash"}`);
      } catch (e) { return fail(e); }
    },
    wp_get_media: async (args) => {
      try {
        const { site, limit } = GetMediaSchema.parse(args);
        const items = await new WpRestClient(registry.getSite(site)).getMedia(limit);
        return ok(JSON.stringify(items.map((m) => ({ id: m.id, title: m.title.rendered, url: m.source_url, type: m.mime_type, date: m.date })), null, 2));
      } catch (e) { return fail(e); }
    },
  };
}
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
npm test -- tests/tools/content.test.ts
```

Expected: 9 tests PASS

- [ ] **Step 5: Commit**

```bash
git add src/tools/content.ts tests/tools/content.test.ts
git commit -m "feat: content tools (get/create/update/delete posts, media)"
```

---

## Task 7: Health Tools

**Files:**
- Create: `tests/tools/health.test.ts`
- Create: `src/tools/health.ts`

- [ ] **Step 1: Write the failing test**

Create `tests/tools/health.test.ts`:
```typescript
import { describe, it, expect, vi, beforeEach } from "vitest";
import type { Registry, SiteConfig } from "../../src/registry";

const siteConfig: SiteConfig = {
  url: "https://mysite.com", rest_api_key: "k", rest_api_secret: "s",
  ftp_host: "ftp.mysite.com", ftp_user: "u", ftp_pass: "p", ftp_root: "/public_html",
};

const mockGetPlugins = vi.fn();
const mockGetTheme = vi.fn();
const mockGetSiteInfo = vi.fn();
const mockGetUsers = vi.fn();

vi.mock("../../src/clients/wp-rest.js", () => ({
  WpRestClient: vi.fn().mockImplementation(() => ({
    getPlugins: mockGetPlugins,
    getTheme: mockGetTheme,
    getSiteInfo: mockGetSiteInfo,
    getUsers: mockGetUsers,
  })),
}));

const { healthToolHandlers } = await import("../../src/tools/health");

const registry: Registry = {
  getSite: vi.fn().mockReturnValue(siteConfig),
  listSites: vi.fn().mockReturnValue(["mysite"]),
};

beforeEach(() => vi.clearAllMocks());

describe("wp_get_plugins", () => {
  it("returns plugin list with update info", async () => {
    mockGetPlugins.mockResolvedValueOnce([
      { plugin: "akismet/akismet", name: "Akismet", status: "active", version: "5.0", update: { version: "5.1" } },
      { plugin: "hello-dolly/hello", name: "Hello Dolly", status: "inactive", version: "1.7", update: null },
    ]);
    const result = await healthToolHandlers(registry).wp_get_plugins({ site: "mysite" });
    const parsed = JSON.parse((result.content[0] as { type: "text"; text: string }).text);
    expect(parsed[0].update).toBe("5.1");
    expect(parsed[1].update).toBeNull();
  });
});

describe("wp_get_theme", () => {
  it("returns active theme info", async () => {
    mockGetTheme.mockResolvedValueOnce({ stylesheet: "twentytwentyfour", name: { rendered: "Twenty Twenty-Four" }, version: "1.2", status: "active" });
    const result = await healthToolHandlers(registry).wp_get_theme({ site: "mysite" });
    const parsed = JSON.parse((result.content[0] as { type: "text"; text: string }).text);
    expect(parsed.name).toBe("Twenty Twenty-Four");
    expect(parsed.version).toBe("1.2");
  });
});

describe("wp_get_site_info", () => {
  it("returns site name, description and url", async () => {
    mockGetSiteInfo.mockResolvedValueOnce({ name: "My Site", description: "A great blog", url: "https://mysite.com" });
    const result = await healthToolHandlers(registry).wp_get_site_info({ site: "mysite" });
    const parsed = JSON.parse((result.content[0] as { type: "text"; text: string }).text);
    expect(parsed.name).toBe("My Site");
  });
});

describe("wp_get_users", () => {
  it("returns user list with roles", async () => {
    mockGetUsers.mockResolvedValueOnce([
      { id: 1, name: "Admin", email: "admin@mysite.com", roles: ["administrator"] },
    ]);
    const result = await healthToolHandlers(registry).wp_get_users({ site: "mysite" });
    const parsed = JSON.parse((result.content[0] as { type: "text"; text: string }).text);
    expect(parsed[0].roles).toContain("administrator");
  });
});

describe("error handling", () => {
  it("returns isError true on REST failure in wp_get_plugins", async () => {
    mockGetPlugins.mockRejectedValueOnce(new Error("403: Forbidden"));
    const result = await healthToolHandlers(registry).wp_get_plugins({ site: "mysite" });
    expect(result.isError).toBe(true);
    expect((result.content[0] as { type: "text"; text: string }).text).toContain("403");
  });
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
npm test -- tests/tools/health.test.ts
```

Expected: FAIL — `Cannot find module '../../src/tools/health'`

- [ ] **Step 3: Implement src/tools/health.ts**

```typescript
import { z } from "zod";
import { zodToJsonSchema } from "zod-to-json-schema";
import type { Registry } from "../registry.js";
import { WpRestClient } from "../clients/wp-rest.js";

const SiteOnly = z.object({ site: z.string().min(1).describe("Site alias from sites.json") });

function toSchema(s: z.ZodType): Record<string, unknown> {
  const schema = zodToJsonSchema(s) as Record<string, unknown>;
  delete schema.$schema;
  return schema;
}

type TextContent = { type: "text"; text: string };
type ToolResponse = { content: TextContent[]; isError?: boolean };
const ok = (text: string): ToolResponse => ({ content: [{ type: "text", text }] });
const fail = (e: unknown): ToolResponse => ({
  content: [{ type: "text", text: `Error: ${e instanceof Error ? e.message : String(e)}` }],
  isError: true,
});

export function healthToolDefinitions() {
  return [
    { name: "wp_get_plugins", description: "List plugins on a WordPress site with update availability", inputSchema: toSchema(SiteOnly) },
    { name: "wp_get_theme", description: "Get the active theme name and version", inputSchema: toSchema(SiteOnly) },
    { name: "wp_get_site_info", description: "Get site name, description, and URL", inputSchema: toSchema(SiteOnly) },
    { name: "wp_get_users", description: "List users with their roles", inputSchema: toSchema(SiteOnly) },
  ];
}

export function healthToolHandlers(
  registry: Registry
): Record<string, (args: unknown) => Promise<ToolResponse>> {
  return {
    wp_get_plugins: async (args) => {
      try {
        const { site } = SiteOnly.parse(args);
        const plugins = await new WpRestClient(registry.getSite(site)).getPlugins();
        return ok(JSON.stringify(plugins.map((p) => ({ name: p.name, version: p.version, status: p.status, update: p.update?.version ?? null })), null, 2));
      } catch (e) { return fail(e); }
    },
    wp_get_theme: async (args) => {
      try {
        const { site } = SiteOnly.parse(args);
        const theme = await new WpRestClient(registry.getSite(site)).getTheme();
        return ok(JSON.stringify({ name: theme.name.rendered, version: theme.version, stylesheet: theme.stylesheet }, null, 2));
      } catch (e) { return fail(e); }
    },
    wp_get_site_info: async (args) => {
      try {
        const { site } = SiteOnly.parse(args);
        const info = await new WpRestClient(registry.getSite(site)).getSiteInfo();
        return ok(JSON.stringify(info, null, 2));
      } catch (e) { return fail(e); }
    },
    wp_get_users: async (args) => {
      try {
        const { site } = SiteOnly.parse(args);
        const users = await new WpRestClient(registry.getSite(site)).getUsers();
        return ok(JSON.stringify(users.map((u) => ({ id: u.id, name: u.name, email: u.email, roles: u.roles })), null, 2));
      } catch (e) { return fail(e); }
    },
  };
}
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
npm test -- tests/tools/health.test.ts
```

Expected: 5 tests PASS

- [ ] **Step 5: Commit**

```bash
git add src/tools/health.ts tests/tools/health.test.ts
git commit -m "feat: health tools (plugins, theme, site info, users)"
```

---

## Task 8: FTP Tools

**Files:**
- Create: `tests/tools/ftp-tools.test.ts`
- Create: `src/tools/ftp.ts`

- [ ] **Step 1: Write the failing test**

Create `tests/tools/ftp-tools.test.ts`:
```typescript
import { describe, it, expect, vi, beforeEach } from "vitest";
import type { Registry, SiteConfig } from "../../src/registry";

const siteConfig: SiteConfig = {
  url: "https://mysite.com", rest_api_key: "k", rest_api_secret: "s",
  ftp_host: "ftp.mysite.com", ftp_user: "u", ftp_pass: "p", ftp_root: "/public_html",
};

const mockList = vi.fn();
const mockRead = vi.fn();
const mockUpload = vi.fn();
const mockDelete = vi.fn();

vi.mock("../../src/clients/ftp.js", () => ({
  FtpClient: vi.fn().mockImplementation(() => ({
    list: mockList,
    read: mockRead,
    upload: mockUpload,
    delete: mockDelete,
  })),
}));

const { ftpToolHandlers } = await import("../../src/tools/ftp");

const registry: Registry = {
  getSite: vi.fn().mockReturnValue(siteConfig),
  listSites: vi.fn().mockReturnValue(["mysite"]),
};

beforeEach(() => vi.clearAllMocks());

describe("ftp_list", () => {
  it("returns directory listing as JSON", async () => {
    mockList.mockResolvedValueOnce([
      { name: "wp-config.php", size: 3000, type: "file", date: new Date("2024-01-01") },
    ]);
    const result = await ftpToolHandlers(registry).ftp_list({ site: "mysite", path: "/" });
    const parsed = JSON.parse((result.content[0] as { type: "text"; text: string }).text);
    expect(parsed[0].name).toBe("wp-config.php");
  });

  it("defaults path to /", async () => {
    mockList.mockResolvedValueOnce([]);
    await ftpToolHandlers(registry).ftp_list({ site: "mysite" });
    expect(mockList).toHaveBeenCalledWith("/");
  });
});

describe("ftp_read", () => {
  it("returns file content as plain text", async () => {
    mockRead.mockResolvedValueOnce("<?php define('DB_NAME', 'mydb');");
    const result = await ftpToolHandlers(registry).ftp_read({ site: "mysite", path: "/wp-config.php" });
    expect((result.content[0] as { type: "text"; text: string }).text).toContain("DB_NAME");
  });
});

describe("ftp_upload", () => {
  it("reports successful upload", async () => {
    mockUpload.mockResolvedValueOnce(undefined);
    const result = await ftpToolHandlers(registry).ftp_upload({ site: "mysite", local_path: "/tmp/file.txt", remote_path: "/theme/file.txt" });
    expect((result.content[0] as { type: "text"; text: string }).text).toContain("/tmp/file.txt");
    expect((result.content[0] as { type: "text"; text: string }).text).toContain("/theme/file.txt");
  });
});

describe("ftp_delete", () => {
  it("reports successful deletion", async () => {
    mockDelete.mockResolvedValueOnce(undefined);
    const result = await ftpToolHandlers(registry).ftp_delete({ site: "mysite", path: "/old-file.php" });
    expect((result.content[0] as { type: "text"; text: string }).text).toContain("/old-file.php");
  });

  it("returns isError true on FTP failure", async () => {
    mockDelete.mockRejectedValueOnce(new Error("Connection refused"));
    const result = await ftpToolHandlers(registry).ftp_delete({ site: "mysite", path: "/file.php" });
    expect(result.isError).toBe(true);
  });
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
npm test -- tests/tools/ftp-tools.test.ts
```

Expected: FAIL — `Cannot find module '../../src/tools/ftp'`

- [ ] **Step 3: Implement src/tools/ftp.ts**

```typescript
import { z } from "zod";
import { zodToJsonSchema } from "zod-to-json-schema";
import type { Registry } from "../registry.js";
import { FtpClient } from "../clients/ftp.js";

const SiteParam = z.string().min(1).describe("Site alias from sites.json");
const ListSchema = z.object({ site: SiteParam, path: z.string().default("/") });
const ReadSchema = z.object({ site: SiteParam, path: z.string().min(1) });
const UploadSchema = z.object({ site: SiteParam, local_path: z.string().min(1), remote_path: z.string().min(1) });
const DeleteSchema = z.object({ site: SiteParam, path: z.string().min(1) });

function toSchema(s: z.ZodType): Record<string, unknown> {
  const schema = zodToJsonSchema(s) as Record<string, unknown>;
  delete schema.$schema;
  return schema;
}

type TextContent = { type: "text"; text: string };
type ToolResponse = { content: TextContent[]; isError?: boolean };
const ok = (text: string): ToolResponse => ({ content: [{ type: "text", text }] });
const fail = (e: unknown): ToolResponse => ({
  content: [{ type: "text", text: `Error: ${e instanceof Error ? e.message : String(e)}` }],
  isError: true,
});

export function ftpToolDefinitions() {
  return [
    { name: "ftp_list", description: "List files and directories at a remote path", inputSchema: toSchema(ListSchema) },
    { name: "ftp_read", description: "Read the content of a remote text file (config, template, etc.)", inputSchema: toSchema(ReadSchema) },
    { name: "ftp_upload", description: "Upload a local file to a remote path on the site", inputSchema: toSchema(UploadSchema) },
    { name: "ftp_delete", description: "Delete a remote file", inputSchema: toSchema(DeleteSchema) },
  ];
}

export function ftpToolHandlers(
  registry: Registry
): Record<string, (args: unknown) => Promise<ToolResponse>> {
  return {
    ftp_list: async (args) => {
      try {
        const { site, path } = ListSchema.parse(args);
        const items = await new FtpClient(registry.getSite(site)).list(path);
        return ok(JSON.stringify(items, null, 2));
      } catch (e) { return fail(e); }
    },
    ftp_read: async (args) => {
      try {
        const { site, path } = ReadSchema.parse(args);
        return ok(await new FtpClient(registry.getSite(site)).read(path));
      } catch (e) { return fail(e); }
    },
    ftp_upload: async (args) => {
      try {
        const { site, local_path, remote_path } = UploadSchema.parse(args);
        await new FtpClient(registry.getSite(site)).upload(local_path, remote_path);
        return ok(`Uploaded ${local_path} → ${remote_path}`);
      } catch (e) { return fail(e); }
    },
    ftp_delete: async (args) => {
      try {
        const { site, path } = DeleteSchema.parse(args);
        await new FtpClient(registry.getSite(site)).delete(path);
        return ok(`Deleted ${path}`);
      } catch (e) { return fail(e); }
    },
  };
}
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
npm test -- tests/tools/ftp-tools.test.ts
```

Expected: 6 tests PASS

- [ ] **Step 5: Commit**

```bash
git add src/tools/ftp.ts tests/tools/ftp-tools.test.ts
git commit -m "feat: FTP tools (list, read, upload, delete)"
```

---

## Task 9: Inspect Tools

**Files:**
- Create: `tests/tools/inspect.test.ts`
- Create: `src/tools/inspect.ts`

- [ ] **Step 1: Write the failing test**

Create `tests/tools/inspect.test.ts`:
```typescript
import { describe, it, expect, vi, beforeEach } from "vitest";

vi.mock("../../src/clients/browser.js", () => ({
  takeScreenshot: vi.fn().mockResolvedValue("base64screenshot"),
  auditPage: vi.fn().mockResolvedValue({
    screenshot: "base64audit",
    consoleErrors: ["Uncaught TypeError: foo is undefined"],
    brokenLinks: [{ url: "https://mysite.com/missing.jpg", status: 404 }],
    performanceTiming: { loadTime: 800, domContentLoaded: 400 },
  }),
  comparePages: vi.fn().mockResolvedValue({ a: "base64a", b: "base64b" }),
}));

const { inspectToolHandlers } = await import("../../src/tools/inspect");

beforeEach(() => vi.clearAllMocks());

describe("wp_inspect", () => {
  it("returns a single image content block", async () => {
    const result = await inspectToolHandlers().wp_inspect({ url: "https://mysite.com" });
    expect(result.content).toHaveLength(1);
    expect(result.content[0].type).toBe("image");
    expect((result.content[0] as { data: string }).data).toBe("base64screenshot");
  });

  it("returns isError true on invalid URL", async () => {
    const result = await inspectToolHandlers().wp_inspect({ url: "not-a-url" });
    expect(result.isError).toBe(true);
  });
});

describe("wp_audit", () => {
  it("returns image then text with audit data", async () => {
    const result = await inspectToolHandlers().wp_audit({ url: "https://mysite.com" });
    expect(result.content[0].type).toBe("image");
    expect(result.content[1].type).toBe("text");
    const data = JSON.parse((result.content[1] as { type: "text"; text: string }).text);
    expect(data.consoleErrors).toContain("Uncaught TypeError: foo is undefined");
    expect(data.brokenLinks[0].status).toBe(404);
    expect(data.performanceTiming.loadTime).toBe(800);
  });
});

describe("wp_compare", () => {
  it("returns two image blocks with URL labels", async () => {
    const result = await inspectToolHandlers().wp_compare({
      url_a: "https://staging.mysite.com",
      url_b: "https://mysite.com",
    });
    expect(result.content).toHaveLength(4);
    expect(result.content[0].type).toBe("text");
    expect(result.content[1].type).toBe("image");
    expect(result.content[2].type).toBe("text");
    expect(result.content[3].type).toBe("image");
    expect((result.content[1] as { data: string }).data).toBe("base64a");
    expect((result.content[3] as { data: string }).data).toBe("base64b");
  });
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
npm test -- tests/tools/inspect.test.ts
```

Expected: FAIL — `Cannot find module '../../src/tools/inspect'`

- [ ] **Step 3: Implement src/tools/inspect.ts**

```typescript
import { z } from "zod";
import { zodToJsonSchema } from "zod-to-json-schema";
import { takeScreenshot, auditPage, comparePages } from "../clients/browser.js";

const InspectSchema = z.object({ url: z.string().url() });
const AuditSchema = z.object({ url: z.string().url() });
const CompareSchema = z.object({ url_a: z.string().url(), url_b: z.string().url() });

function toSchema(s: z.ZodType): Record<string, unknown> {
  const schema = zodToJsonSchema(s) as Record<string, unknown>;
  delete schema.$schema;
  return schema;
}

type ImageContent = { type: "image"; data: string; mimeType: "image/png" };
type TextContent = { type: "text"; text: string };
type ToolResponse = { content: Array<ImageContent | TextContent>; isError?: boolean };

const fail = (e: unknown): ToolResponse => ({
  content: [{ type: "text", text: `Error: ${e instanceof Error ? e.message : String(e)}` }],
  isError: true,
});

export function inspectToolDefinitions() {
  return [
    { name: "wp_inspect", description: "Take a full-page screenshot of a URL for visual inspection", inputSchema: toSchema(InspectSchema) },
    { name: "wp_audit", description: "Full page audit: screenshot + console errors + 4xx/5xx links + performance timing", inputSchema: toSchema(AuditSchema) },
    { name: "wp_compare", description: "Screenshot two URLs side-by-side for comparison (e.g. staging vs production)", inputSchema: toSchema(CompareSchema) },
  ];
}

export function inspectToolHandlers(): Record<string, (args: unknown) => Promise<ToolResponse>> {
  return {
    wp_inspect: async (args) => {
      try {
        const { url } = InspectSchema.parse(args);
        const data = await takeScreenshot(url);
        return { content: [{ type: "image", data, mimeType: "image/png" }] };
      } catch (e) { return fail(e); }
    },
    wp_audit: async (args) => {
      try {
        const { url } = AuditSchema.parse(args);
        const result = await auditPage(url);
        return {
          content: [
            { type: "image", data: result.screenshot, mimeType: "image/png" },
            { type: "text", text: JSON.stringify({ consoleErrors: result.consoleErrors, brokenLinks: result.brokenLinks, performanceTiming: result.performanceTiming }, null, 2) },
          ],
        };
      } catch (e) { return fail(e); }
    },
    wp_compare: async (args) => {
      try {
        const { url_a, url_b } = CompareSchema.parse(args);
        const { a, b } = await comparePages(url_a, url_b);
        return {
          content: [
            { type: "text", text: `Left: ${url_a}` },
            { type: "image", data: a, mimeType: "image/png" },
            { type: "text", text: `Right: ${url_b}` },
            { type: "image", data: b, mimeType: "image/png" },
          ],
        };
      } catch (e) { return fail(e); }
    },
  };
}
```

- [ ] **Step 4: Run test to confirm it passes**

```bash
npm test -- tests/tools/inspect.test.ts
```

Expected: 5 tests PASS

- [ ] **Step 5: Run the full test suite**

```bash
npm test
```

Expected: All tests PASS across registry, clients, and tools.

- [ ] **Step 6: Commit**

```bash
git add src/tools/inspect.ts tests/tools/inspect.test.ts
git commit -m "feat: inspect tools (screenshot, audit, compare)"
```

---

## Task 10: MCP Server Entry Point + README

**Files:**
- Create: `src/index.ts`
- Create: `README.md`

- [ ] **Step 1: Implement src/index.ts**

```typescript
import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from "@modelcontextprotocol/sdk/types.js";
import { resolve } from "path";
import { createRegistry } from "./registry.js";
import { contentToolDefinitions, contentToolHandlers } from "./tools/content.js";
import { healthToolDefinitions, healthToolHandlers } from "./tools/health.js";
import { ftpToolDefinitions, ftpToolHandlers } from "./tools/ftp.js";
import { inspectToolDefinitions, inspectToolHandlers } from "./tools/inspect.js";
import { closeBrowser } from "./clients/browser.js";

const registry = createRegistry(resolve(process.cwd(), "sites.json"));

const tools = [
  ...contentToolDefinitions(),
  ...healthToolDefinitions(),
  ...ftpToolDefinitions(),
  ...inspectToolDefinitions(),
];

const handlers: Record<string, (args: unknown) => Promise<unknown>> = {
  ...contentToolHandlers(registry),
  ...healthToolHandlers(registry),
  ...ftpToolHandlers(registry),
  ...inspectToolHandlers(),
};

const server = new Server(
  { name: "pawa-wp-mcp", version: "1.0.0" },
  { capabilities: { tools: {} } }
);

server.setRequestHandler(ListToolsRequestSchema, async () => ({ tools }));

server.setRequestHandler(CallToolRequestSchema, async ({ params }) => {
  const handler = handlers[params.name];
  if (!handler) throw new Error(`Unknown tool: ${params.name}`);
  return handler(params.arguments ?? {});
});

const transport = new StdioServerTransport();
await server.connect(transport);

process.on("SIGINT", async () => {
  await closeBrowser();
  process.exit(0);
});
```

- [ ] **Step 2: Smoke test the server starts without errors**

Copy `sites.example.json` to `sites.json` and replace placeholders with dummy values:
```bash
cp sites.example.json sites.json
```

Then run:
```bash
echo '{"jsonrpc":"2.0","id":1,"method":"tools/list","params":{}}' | tsx src/index.ts
```

Expected: JSON response containing all 15 tool names (`wp_get_posts`, `wp_get_post`, …, `wp_compare`).

- [ ] **Step 3: Create README.md**

```markdown
# pawa-wp-mcp

Local MCP server for managing WordPress sites via REST API, FTP, and Playwright — no SSH or WP-CLI required.

## Setup

### 1. Install dependencies

```bash
npm install
npx playwright install chromium
```

### 2. Configure sites

```bash
cp sites.example.json sites.json
```

Edit `sites.json` and add your WordPress sites. For `rest_api_key` and `rest_api_secret`, create an **Application Password** in WordPress: Users → Profile → Application Passwords.

### 3. Register with Claude Code

```bash
claude mcp add pawa-wp-mcp -s user -- npx tsx /absolute/path/to/pawa-wp-mcp/src/index.ts
```

Or add manually to `~/.claude.json`:

```json
{
  "mcpServers": {
    "pawa-wp-mcp": {
      "command": "npx",
      "args": ["tsx", "/absolute/path/to/pawa-wp-mcp/src/index.ts"],
      "cwd": "/absolute/path/to/pawa-wp-mcp"
    }
  }
}
```

### 4. Install the Claude Code skill

```bash
mkdir -p ~/.claude-personal/plugins/pawa-wp/skills
cp skills/wp-manager.md ~/.claude-personal/plugins/pawa-wp/skills/wp-manager.md
```

## Tools

| Category | Tools |
|---|---|
| Content | `wp_get_posts`, `wp_get_post`, `wp_create_post`, `wp_update_post`, `wp_delete_post`, `wp_get_media` |
| Health | `wp_get_plugins`, `wp_get_theme`, `wp_get_site_info`, `wp_get_users` |
| FTP | `ftp_list`, `ftp_read`, `ftp_upload`, `ftp_delete` |
| Inspect | `wp_inspect`, `wp_audit`, `wp_compare` |
```

- [ ] **Step 4: Commit**

```bash
git add src/index.ts README.md
git commit -m "feat: MCP server entry point and README"
```

---

## Task 11: Claude Code Skill + MCP Registration

**Files:**
- Create: `skills/wp-manager.md`

- [ ] **Step 1: Create skills/wp-manager.md**

```markdown
---
name: wp-manager
description: Use when managing WordPress sites - provides guidance on using pawa-wp-mcp tools for content management, health checks, file operations, and design analysis. Trigger when user mentions a WordPress site, asks to inspect/update/create content, or wants to audit a site.
---

# WordPress Site Manager

You have access to the `pawa-wp-mcp` MCP server which manages WordPress sites via REST API, FTP, and Playwright.

## Behavioral Rules

1. **Always use site aliases** — the `site` parameter is always the short alias from `sites.json` (e.g. `"mysite"`), never a raw URL
2. **Inspect before advising** — when asked about any visual or design issue, call `wp_inspect` first to get a live screenshot before responding
3. **Audit before debugging** — for "something looks broken" reports, call `wp_audit` first to check console errors and 4xx/5xx responses before theorizing
4. **Fan out health checks** — when checking updates across all sites, call `wp_get_plugins` in parallel for all known site aliases
5. **Confirm before modifying** — before calling `wp_create_post`, `wp_update_post`, `wp_delete_post`, `ftp_upload`, or `ftp_delete`, confirm the target site and intended action with the user

## Design Analysis

When you receive a screenshot from `wp_inspect` or `wp_audit`:
- Identify specific layout, typography, spacing, and contrast issues
- Suggest concrete CSS/theme fixes with actual property values — not vague advice like "improve spacing"
- If a staging URL is available, use `wp_compare` to diff against production before recommending changes

## Tool Reference

### Content
- `wp_get_posts` — list posts or pages (params: `site`, `type`, `status`, `limit`)
- `wp_get_post` — get single post/page with full content (params: `site`, `id`, `type`)
- `wp_create_post` — create post or page (params: `site`, `title`, `content`, `status`, `type`)
- `wp_update_post` — update title, content, or status (params: `site`, `id`, `type`, `title?`, `content?`, `status?`)
- `wp_delete_post` — trash or force-delete (params: `site`, `id`, `type`, `force`)
- `wp_get_media` — list media library items (params: `site`, `limit`)

### Site Health
- `wp_get_plugins` — list plugins with update availability (params: `site`)
- `wp_get_theme` — get active theme name and version (params: `site`)
- `wp_get_site_info` — get site name, description, and URL (params: `site`)
- `wp_get_users` — list users with roles (params: `site`)

### File Operations
- `ftp_list` — list remote directory contents (params: `site`, `path`)
- `ftp_read` — read a remote text file (params: `site`, `path`)
- `ftp_upload` — upload a local file to a remote path (params: `site`, `local_path`, `remote_path`)
- `ftp_delete` — delete a remote file (params: `site`, `path`)

### Inspect & Audit
- `wp_inspect` — full-page screenshot of a URL (params: `url`)
- `wp_audit` — screenshot + console errors + 4xx/5xx broken links + performance timing (params: `url`)
- `wp_compare` — side-by-side screenshots of two URLs (params: `url_a`, `url_b`)
```

- [ ] **Step 2: Install the skill**

```bash
mkdir -p ~/.claude-personal/plugins/pawa-wp/skills
cp skills/wp-manager.md ~/.claude-personal/plugins/pawa-wp/skills/wp-manager.md
```

- [ ] **Step 3: Create plugin metadata file**

Create `~/.claude-personal/plugins/pawa-wp/plugin.json`:
```json
{
  "name": "pawa-wp",
  "version": "1.0.0",
  "description": "WordPress site management via pawa-wp-mcp",
  "skills": ["wp-manager"]
}
```

- [ ] **Step 4: Register the MCP server with Claude Code**

Replace `/absolute/path/to/pawa-wp-mcp` with the actual path (e.g. `/home/pixel/projects/personal/wp/pawa-wp-mcp`):

```bash
claude mcp add pawa-wp-mcp -s user -- npx tsx /home/pixel/projects/personal/wp/pawa-wp-mcp/src/index.ts
```

Verify it is registered:
```bash
claude mcp list
```

Expected: `pawa-wp-mcp` appears in the list.

- [ ] **Step 5: Restart Claude Code and verify tools are available**

Start a new Claude Code session and run:
```
/mcp
```

Expected: `pawa-wp-mcp` listed with 15 tools.

- [ ] **Step 6: End-to-end smoke test**

With a real `sites.json` configured, ask Claude:
```
List plugins on mysite and tell me which ones have updates available.
```

Expected: Claude calls `wp_get_plugins` with `site: "mysite"` and returns a formatted list.

- [ ] **Step 7: Commit**

```bash
git add skills/wp-manager.md
git commit -m "feat: Claude Code skill and MCP registration instructions"
```
```
