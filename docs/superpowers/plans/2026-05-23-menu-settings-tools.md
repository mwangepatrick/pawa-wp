# Menu & Settings Tools Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add WordPress nav menu CRUD tools and a settings tool (for `page_for_posts`, `page_on_front`, etc.) to the pawa-wp-mcp MCP server.

**Architecture:** New `src/tools/menu.ts` file following the existing `content.ts` / `site.ts` pattern (Zod schemas → tool definitions → handlers). REST methods added to `src/clients/wp-rest.ts`. Settings tools (`wp_get_settings`, `wp_update_settings`) added to the existing `src/tools/site.ts`. Both groups registered in `src/index.ts`.

**Tech Stack:** TypeScript, `@modelcontextprotocol/sdk`, `zod`, `zod-to-json-schema`, vitest (tests), WP REST API v2 (`/wp/v2/menus`, `/wp/v2/menu-items`, `/wp/v2/settings`)

---

## File Map

| Action | File | What changes |
|---|---|---|
| Modify | `src/clients/wp-rest.ts` | Add `WpMenu`, `WpMenuItem`, `WpSettings` types + 9 new methods |
| Create | `src/tools/menu.ts` | 7 menu tools: get_menus, get_menu_items, create_menu, delete_menu, add_menu_item, update_menu_item, remove_menu_item |
| Modify | `src/tools/site.ts` | Add `wp_get_settings` + `wp_update_settings` |
| Modify | `src/index.ts` | Import and register `menuToolDefinitions` / `menuToolHandlers` |
| Create | `tests/menu.test.ts` | Unit tests for menu tool handlers (fetch mocked) |
| Create | `tests/settings.test.ts` | Unit tests for settings tool handlers (fetch mocked) |

---

## Task 1: Add types and REST client methods

**Files:**
- Modify: `src/clients/wp-rest.ts`

- [ ] **Step 1: Add WP type interfaces**

Append after the `WpSiteInfo` interface (line 47) in `src/clients/wp-rest.ts`:

```typescript
export interface WpMenu {
  id: number;
  name: string;
  slug: string;
  locations: string[];
}

export interface WpMenuItem {
  id: number;
  title: { rendered: string };
  url: string;
  type: string;
  object: string;
  object_id: number;
  parent: number;
  menu_order: number;
  menus: number;
  status: string;
}

export interface WpSettings {
  page_for_posts: number;
  page_on_front: number;
  show_on_front: string;
  blogname: string;
  blogdescription: string;
  posts_per_page: number;
}
```

- [ ] **Step 2: Add REST client methods**

Append before the closing `}` of the `WpRestClient` class in `src/clients/wp-rest.ts`:

```typescript
  async getMenus(): Promise<WpMenu[]> {
    return this.req<WpMenu[]>(`${this.v2}/menus`);
  }

  async createMenu(data: { name: string }): Promise<WpMenu> {
    return this.req<WpMenu>(`${this.v2}/menus`, {
      method: "POST",
      body: JSON.stringify(data),
    });
  }

  async deleteMenu(id: number, force = false): Promise<void> {
    await this.req(`${this.v2}/menus/${id}?force=${force}`, { method: "DELETE" });
  }

  async getMenuItems(menuId: number): Promise<WpMenuItem[]> {
    return this.req<WpMenuItem[]>(`${this.v2}/menu-items?menus=${menuId}&per_page=100`);
  }

  async addMenuItem(data: {
    title?: string;
    url?: string;
    type: string;
    object: string;
    object_id?: number;
    parent: number;
    menu_order?: number;
    menus: number;
  }): Promise<WpMenuItem> {
    return this.req<WpMenuItem>(`${this.v2}/menu-items`, {
      method: "POST",
      body: JSON.stringify({ ...data, status: "publish" }),
    });
  }

  async updateMenuItem(
    id: number,
    data: { title?: string; url?: string; parent?: number; menu_order?: number }
  ): Promise<WpMenuItem> {
    return this.req<WpMenuItem>(`${this.v2}/menu-items/${id}`, {
      method: "POST",
      body: JSON.stringify(data),
    });
  }

  async removeMenuItem(id: number, force = true): Promise<void> {
    await this.req(`${this.v2}/menu-items/${id}?force=${force}`, { method: "DELETE" });
  }

  async getSettings(): Promise<WpSettings> {
    return this.req<WpSettings>(`${this.v2}/settings`);
  }

  async updateSettings(data: Partial<WpSettings>): Promise<WpSettings> {
    return this.req<WpSettings>(`${this.v2}/settings`, {
      method: "POST",
      body: JSON.stringify(data),
    });
  }
```

- [ ] **Step 3: Verify TypeScript compiles**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp && npx tsc --noEmit
```

Expected: no errors.

- [ ] **Step 4: Commit**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp
git add src/clients/wp-rest.ts
git commit -m "feat: add menu and settings types + REST client methods"
```

---

## Task 2: Create menu tools

**Files:**
- Create: `src/tools/menu.ts`
- Create: `tests/menu.test.ts`

- [ ] **Step 1: Write failing tests**

Create `tests/menu.test.ts`:

```typescript
import { describe, it, expect, vi, beforeEach } from "vitest";
import { menuToolHandlers } from "../src/tools/menu.js";

const SITE_CONFIG = {
  url: "https://mysite.com",
  rest_api_key: "key",
  rest_api_secret: "secret",
  ftp_host: "ftp.mysite.com",
  ftp_user: "user",
  ftp_pass: "pass",
  ftp_root: "/public_html",
};

const registry = { getSite: () => SITE_CONFIG, listSites: () => ["mysite"] };

function mockFetch(payload: unknown, ok = true) {
  vi.stubGlobal(
    "fetch",
    vi.fn().mockResolvedValue({
      ok,
      status: ok ? 200 : 400,
      text: async () => JSON.stringify(payload),
      json: async () => payload,
    })
  );
}

beforeEach(() => vi.restoreAllMocks());

describe("wp_get_menus", () => {
  it("returns menu list", async () => {
    mockFetch([{ id: 1, name: "Primary", slug: "primary", locations: ["primary"] }]);
    const handlers = menuToolHandlers(registry);
    const result = await handlers.wp_get_menus({ site: "mysite" });
    expect(result.isError).toBeFalsy();
    const parsed = JSON.parse((result as { content: { text: string }[] }).content[0].text);
    expect(parsed[0].id).toBe(1);
    expect(parsed[0].name).toBe("Primary");
  });
});

describe("wp_get_menu_items", () => {
  it("returns items for a menu", async () => {
    mockFetch([
      { id: 10, title: { rendered: "About" }, url: "https://mysite.com/about/", type: "post_type", object: "page", object_id: 42, parent: 0, menu_order: 1, menus: 1, status: "publish" },
    ]);
    const handlers = menuToolHandlers(registry);
    const result = await handlers.wp_get_menu_items({ site: "mysite", menu_id: 1 });
    expect(result.isError).toBeFalsy();
    const parsed = JSON.parse((result as { content: { text: string }[] }).content[0].text);
    expect(parsed[0].id).toBe(10);
    expect(parsed[0].title).toBe("About");
  });
});

describe("wp_create_menu", () => {
  it("creates and returns a menu", async () => {
    mockFetch({ id: 5, name: "Footer Menu", slug: "footer-menu", locations: [] });
    const handlers = menuToolHandlers(registry);
    const result = await handlers.wp_create_menu({ site: "mysite", name: "Footer Menu" });
    expect(result.isError).toBeFalsy();
    expect((result as { content: { text: string }[] }).content[0].text).toContain("Footer Menu");
  });
});

describe("wp_delete_menu", () => {
  it("deletes a menu", async () => {
    mockFetch({ deleted: true });
    const handlers = menuToolHandlers(registry);
    const result = await handlers.wp_delete_menu({ site: "mysite", menu_id: 5, force: true });
    expect(result.isError).toBeFalsy();
    expect((result as { content: { text: string }[] }).content[0].text).toContain("deleted");
  });
});

describe("wp_add_menu_item", () => {
  it("adds a page item to a menu", async () => {
    mockFetch({ id: 20, title: { rendered: "Contact" }, url: "https://mysite.com/contact/", type: "post_type", object: "page", object_id: 99, parent: 0, menu_order: 3, menus: 1, status: "publish" });
    const handlers = menuToolHandlers(registry);
    const result = await handlers.wp_add_menu_item({ site: "mysite", menu_id: 1, type: "page", object_id: 99, parent: 0 });
    expect(result.isError).toBeFalsy();
    expect((result as { content: { text: string }[] }).content[0].text).toContain("Contact");
  });

  it("adds a custom URL item", async () => {
    mockFetch({ id: 21, title: { rendered: "Google" }, url: "https://google.com", type: "custom", object: "custom", object_id: 0, parent: 0, menu_order: 4, menus: 1, status: "publish" });
    const handlers = menuToolHandlers(registry);
    const result = await handlers.wp_add_menu_item({ site: "mysite", menu_id: 1, type: "custom", url: "https://google.com", title: "Google", parent: 0 });
    expect(result.isError).toBeFalsy();
    expect((result as { content: { text: string }[] }).content[0].text).toContain("Google");
  });
});

describe("wp_update_menu_item", () => {
  it("updates a menu item", async () => {
    mockFetch({ id: 10, title: { rendered: "About Us" }, url: "https://mysite.com/about/", type: "post_type", object: "page", object_id: 42, parent: 0, menu_order: 2, menus: 1, status: "publish" });
    const handlers = menuToolHandlers(registry);
    const result = await handlers.wp_update_menu_item({ site: "mysite", item_id: 10, title: "About Us", menu_order: 2 });
    expect(result.isError).toBeFalsy();
    expect((result as { content: { text: string }[] }).content[0].text).toContain("About Us");
  });
});

describe("wp_remove_menu_item", () => {
  it("removes a menu item", async () => {
    mockFetch({ deleted: true });
    const handlers = menuToolHandlers(registry);
    const result = await handlers.wp_remove_menu_item({ site: "mysite", item_id: 10, force: true });
    expect(result.isError).toBeFalsy();
    expect((result as { content: { text: string }[] }).content[0].text).toContain("removed");
  });
});
```

- [ ] **Step 2: Run tests — verify they fail**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp && npx vitest run tests/menu.test.ts
```

Expected: FAIL — `../src/tools/menu.js` not found.

- [ ] **Step 3: Create `src/tools/menu.ts`**

```typescript
import { z } from "zod";
import { zodToJsonSchema } from "zod-to-json-schema";
import type { Registry } from "../registry.js";
import { WpRestClient } from "../clients/wp-rest.js";

const SiteParam = z.string().min(1).describe("Site alias from sites.json");

const GetMenusSchema = z.object({ site: SiteParam });

const GetMenuItemsSchema = z.object({
  site: SiteParam,
  menu_id: z.number().int().positive().describe("Menu ID from wp_get_menus"),
});

const CreateMenuSchema = z.object({
  site: SiteParam,
  name: z.string().min(1).describe("Display name for the new menu"),
});

const DeleteMenuSchema = z.object({
  site: SiteParam,
  menu_id: z.number().int().positive(),
  force: z.boolean().default(false),
});

const AddMenuItemSchema = z.object({
  site: SiteParam,
  menu_id: z.number().int().positive().describe("Menu ID to add the item to"),
  type: z.enum(["page", "post", "custom"]).describe("Type of item: page, post, or custom URL"),
  object_id: z.number().int().positive().optional().describe("Page or post ID (required for page/post type)"),
  url: z.string().optional().describe("URL for custom link items"),
  title: z.string().optional().describe("Display title (defaults to the page/post title)"),
  parent: z.number().int().default(0).describe("Parent menu item ID for sub-items (0 = top level)"),
  menu_order: z.number().int().optional().describe("Position in menu (1-based)"),
});

const UpdateMenuItemSchema = z.object({
  site: SiteParam,
  item_id: z.number().int().positive().describe("Menu item ID from wp_get_menu_items"),
  title: z.string().optional().describe("New display title"),
  url: z.string().optional().describe("New URL (custom items only)"),
  parent: z.number().int().optional().describe("New parent menu item ID (0 = top level)"),
  menu_order: z.number().int().optional().describe("New position (1-based)"),
});

const RemoveMenuItemSchema = z.object({
  site: SiteParam,
  item_id: z.number().int().positive().describe("Menu item ID to remove"),
  force: z.boolean().default(true).describe("Permanently delete (true) or trash (false)"),
});

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

export function menuToolDefinitions() {
  return [
    { name: "wp_get_menus", description: "List all registered navigation menus on a WordPress site", inputSchema: toSchema(GetMenusSchema) },
    { name: "wp_get_menu_items", description: "List all items in a specific navigation menu", inputSchema: toSchema(GetMenuItemsSchema) },
    { name: "wp_create_menu", description: "Create a new navigation menu", inputSchema: toSchema(CreateMenuSchema) },
    { name: "wp_delete_menu", description: "Delete a navigation menu and all its items", inputSchema: toSchema(DeleteMenuSchema) },
    { name: "wp_add_menu_item", description: "Add a page, post, or custom URL to a navigation menu", inputSchema: toSchema(AddMenuItemSchema) },
    { name: "wp_update_menu_item", description: "Update the title, URL, order, or parent of a menu item", inputSchema: toSchema(UpdateMenuItemSchema) },
    { name: "wp_remove_menu_item", description: "Remove an item from a navigation menu", inputSchema: toSchema(RemoveMenuItemSchema) },
  ];
}

export function menuToolHandlers(
  registry: Registry
): Record<string, (args: unknown) => Promise<ToolResponse>> {
  return {
    wp_get_menus: async (args) => {
      try {
        const { site } = GetMenusSchema.parse(args);
        const menus = await new WpRestClient(registry.getSite(site)).getMenus();
        return ok(JSON.stringify(menus.map((m) => ({ id: m.id, name: m.name, slug: m.slug, locations: m.locations })), null, 2));
      } catch (e) { return fail(e); }
    },

    wp_get_menu_items: async (args) => {
      try {
        const { site, menu_id } = GetMenuItemsSchema.parse(args);
        const items = await new WpRestClient(registry.getSite(site)).getMenuItems(menu_id);
        return ok(JSON.stringify(items.map((i) => ({
          id: i.id,
          title: i.title.rendered,
          url: i.url,
          type: i.type,
          object: i.object,
          object_id: i.object_id,
          parent: i.parent,
          menu_order: i.menu_order,
        })), null, 2));
      } catch (e) { return fail(e); }
    },

    wp_create_menu: async (args) => {
      try {
        const { site, name } = CreateMenuSchema.parse(args);
        const menu = await new WpRestClient(registry.getSite(site)).createMenu({ name });
        return ok(`Created menu #${menu.id}: "${menu.name}" (slug: ${menu.slug})`);
      } catch (e) { return fail(e); }
    },

    wp_delete_menu: async (args) => {
      try {
        const { site, menu_id, force } = DeleteMenuSchema.parse(args);
        await new WpRestClient(registry.getSite(site)).deleteMenu(menu_id, force);
        return ok(`Menu #${menu_id} ${force ? "permanently deleted" : "moved to trash"}`);
      } catch (e) { return fail(e); }
    },

    wp_add_menu_item: async (args) => {
      try {
        const { site, menu_id, type, object_id, url, title, parent, menu_order } = AddMenuItemSchema.parse(args);

        const itemData: Parameters<WpRestClient["addMenuItem"]>[0] = {
          menus: menu_id,
          parent,
          ...(title && { title }),
          ...(menu_order !== undefined && { menu_order }),
          ...(type === "custom"
            ? { type: "custom", object: "custom", url: url ?? "" }
            : { type: "post_type", object: type, object_id }),
        };

        const item = await new WpRestClient(registry.getSite(site)).addMenuItem(itemData);
        return ok(`Added menu item #${item.id}: "${item.title.rendered}" to menu #${menu_id} (order: ${item.menu_order})`);
      } catch (e) { return fail(e); }
    },

    wp_update_menu_item: async (args) => {
      try {
        const { site, item_id, ...updates } = UpdateMenuItemSchema.parse(args);
        const item = await new WpRestClient(registry.getSite(site)).updateMenuItem(item_id, updates);
        return ok(`Updated menu item #${item.id}: "${item.title.rendered}" (order: ${item.menu_order})`);
      } catch (e) { return fail(e); }
    },

    wp_remove_menu_item: async (args) => {
      try {
        const { site, item_id, force } = RemoveMenuItemSchema.parse(args);
        await new WpRestClient(registry.getSite(site)).removeMenuItem(item_id, force);
        return ok(`Menu item #${item_id} removed`);
      } catch (e) { return fail(e); }
    },
  };
}
```

- [ ] **Step 4: Run tests — verify they pass**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp && npx vitest run tests/menu.test.ts
```

Expected: all 8 tests PASS.

- [ ] **Step 5: Verify TypeScript compiles**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp && npx tsc --noEmit
```

Expected: no errors.

- [ ] **Step 6: Commit**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp
git add src/tools/menu.ts tests/menu.test.ts
git commit -m "feat: add wp_get_menus, wp_get_menu_items, wp_create_menu, wp_delete_menu, wp_add_menu_item, wp_update_menu_item, wp_remove_menu_item"
```

---

## Task 3: Add settings tools to `site.ts`

**Files:**
- Modify: `src/tools/site.ts`
- Create: `tests/settings.test.ts`

- [ ] **Step 1: Write failing tests**

Create `tests/settings.test.ts`:

```typescript
import { describe, it, expect, vi, beforeEach } from "vitest";
import { siteToolHandlers } from "../src/tools/site.js";

const SITE_CONFIG = {
  url: "https://mysite.com",
  rest_api_key: "key",
  rest_api_secret: "secret",
  ftp_host: "ftp.mysite.com",
  ftp_user: "user",
  ftp_pass: "pass",
  ftp_root: "/public_html",
};

const registry = { getSite: () => SITE_CONFIG, listSites: () => ["mysite"] };

function mockFetch(payload: unknown, ok = true) {
  vi.stubGlobal(
    "fetch",
    vi.fn().mockResolvedValue({
      ok,
      status: ok ? 200 : 400,
      text: async () => JSON.stringify(payload),
      json: async () => payload,
    })
  );
}

beforeEach(() => vi.restoreAllMocks());

describe("wp_get_settings", () => {
  it("returns current site settings", async () => {
    mockFetch({ page_for_posts: 6081, page_on_front: 1716, show_on_front: "page", blogname: "Mwangaza", blogdescription: "Making a difference", posts_per_page: 10 });
    const handlers = siteToolHandlers(registry);
    const result = await handlers.wp_get_settings({ site: "mysite" });
    expect(result.isError).toBeFalsy();
    const parsed = JSON.parse((result as { content: { text: string }[] }).content[0].text);
    expect(parsed.page_for_posts).toBe(6081);
    expect(parsed.blogname).toBe("Mwangaza");
  });
});

describe("wp_update_settings", () => {
  it("updates page_for_posts", async () => {
    mockFetch({ page_for_posts: 6081, page_on_front: 1716, show_on_front: "page", blogname: "Mwangaza", blogdescription: "Making a difference", posts_per_page: 10 });
    const handlers = siteToolHandlers(registry);
    const result = await handlers.wp_update_settings({ site: "mysite", page_for_posts: 6081 });
    expect(result.isError).toBeFalsy();
    expect((result as { content: { text: string }[] }).content[0].text).toContain("updated");
  });

  it("updates show_on_front to page", async () => {
    mockFetch({ page_for_posts: 6081, page_on_front: 1716, show_on_front: "page", blogname: "Mwangaza", blogdescription: "Making a difference", posts_per_page: 10 });
    const handlers = siteToolHandlers(registry);
    const result = await handlers.wp_update_settings({ site: "mysite", show_on_front: "page", page_on_front: 1716 });
    expect(result.isError).toBeFalsy();
    expect((result as { content: { text: string }[] }).content[0].text).toContain("updated");
  });
});
```

- [ ] **Step 2: Run tests — verify they fail**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp && npx vitest run tests/settings.test.ts
```

Expected: FAIL — `wp_get_settings` not found in handlers.

- [ ] **Step 3: Add schemas to `src/tools/site.ts`**

Add after the `EnableServerCacheSchema` declaration (after line 21 in `src/tools/site.ts`):

```typescript
const GetSettingsSchema = z.object({ site: SiteParam });

const UpdateSettingsSchema = z.object({
  site: SiteParam,
  page_for_posts: z.number().int().nonnegative().optional().describe("Page ID to use as the blog posts index (0 = none)"),
  page_on_front: z.number().int().nonnegative().optional().describe("Page ID to use as the static front page (0 = none)"),
  show_on_front: z.enum(["posts", "page"]).optional().describe("What to show on the front page: 'posts' (latest posts) or 'page' (static page)"),
  blogname: z.string().optional().describe("Site title"),
  blogdescription: z.string().optional().describe("Site tagline"),
  posts_per_page: z.number().int().positive().optional().describe("Number of posts shown per page"),
});
```

- [ ] **Step 4: Add tool definitions to `siteToolDefinitions()`**

Add to the returned array in `siteToolDefinitions()` in `src/tools/site.ts`:

```typescript
    {
      name: "wp_get_settings",
      description: "Get WordPress site settings including page_for_posts, page_on_front, blogname, and posts_per_page",
      inputSchema: toSchema(GetSettingsSchema),
    },
    {
      name: "wp_update_settings",
      description: "Update WordPress site settings. Use page_for_posts to set which page displays blog posts, page_on_front + show_on_front:'page' to set a static front page.",
      inputSchema: toSchema(UpdateSettingsSchema),
    },
```

- [ ] **Step 5: Add handlers to `siteToolHandlers()`**

Add to the returned object in `siteToolHandlers()` in `src/tools/site.ts`:

```typescript
    wp_get_settings: async (args) => {
      try {
        const { site } = GetSettingsSchema.parse(args);
        const settings = await new WpRestClient(registry.getSite(site)).getSettings();
        return ok(JSON.stringify({
          page_for_posts: settings.page_for_posts,
          page_on_front: settings.page_on_front,
          show_on_front: settings.show_on_front,
          blogname: settings.blogname,
          blogdescription: settings.blogdescription,
          posts_per_page: settings.posts_per_page,
        }, null, 2));
      } catch (e) { return fail(e); }
    },

    wp_update_settings: async (args) => {
      try {
        const { site, ...updates } = UpdateSettingsSchema.parse(args);
        const settings = await new WpRestClient(registry.getSite(site)).updateSettings(updates);
        return ok(`Settings updated.\npage_for_posts: ${settings.page_for_posts}\npage_on_front: ${settings.page_on_front}\nshow_on_front: ${settings.show_on_front}`);
      } catch (e) { return fail(e); }
    },
```

- [ ] **Step 6: Run tests — verify they pass**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp && npx vitest run tests/settings.test.ts
```

Expected: all 3 tests PASS.

- [ ] **Step 7: Run all tests**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp && npx vitest run
```

Expected: all tests PASS (registry + menu + settings).

- [ ] **Step 8: Verify TypeScript compiles**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp && npx tsc --noEmit
```

Expected: no errors.

- [ ] **Step 9: Commit**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp
git add src/tools/site.ts tests/settings.test.ts
git commit -m "feat: add wp_get_settings and wp_update_settings"
```

---

## Task 4: Wire up menu tools in `src/index.ts`

**Files:**
- Modify: `src/index.ts`

- [ ] **Step 1: Add import**

Add after the existing imports in `src/index.ts`:

```typescript
import { menuToolDefinitions, menuToolHandlers } from "./tools/menu.js";
```

- [ ] **Step 2: Register tool definitions**

Add `...menuToolDefinitions(),` to the `tools` array in `src/index.ts`:

```typescript
const tools = [
  ...contentToolDefinitions(),
  ...healthToolDefinitions(),
  ...ftpToolDefinitions(),
  ...inspectToolDefinitions(),
  ...elementorToolDefinitions(),
  ...siteToolDefinitions(),
  ...menuToolDefinitions(),
];
```

- [ ] **Step 3: Register handlers**

Add `...menuToolHandlers(registry),` to the `handlers` object in `src/index.ts`:

```typescript
const handlers: Record<string, (args: unknown) => Promise<unknown>> = {
  ...contentToolHandlers(registry),
  ...healthToolHandlers(registry),
  ...ftpToolHandlers(registry),
  ...inspectToolHandlers(),
  ...elementorToolHandlers(registry),
  ...siteToolHandlers(registry),
  ...menuToolHandlers(registry),
};
```

- [ ] **Step 4: Verify TypeScript compiles**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp && npx tsc --noEmit
```

Expected: no errors.

- [ ] **Step 5: Run all tests**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp && npx vitest run
```

Expected: all tests PASS.

- [ ] **Step 6: Commit**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp
git add src/index.ts
git commit -m "feat: register menu tools in MCP server"
```

---

## Task 5: Restart MCP server and apply to Mwangaza

After all code changes are committed, restart the MCP server so the new tools are available in Claude Code.

- [ ] **Step 1: Restart MCP server**

The MCP server runs as a background process managed by Claude Code. Restart it:

```bash
# Kill the running instance (Claude Code will restart it automatically on next tool call)
pkill -f "tsx src/index.ts" || true
```

- [ ] **Step 2: Apply the two pending Mwangaza changes**

With the new tools live, call:

1. `wp_get_menus` on `mwangaza` to find the active menu ID
2. `wp_get_menu_items` to see the current items
3. `wp_add_menu_item` to add Blogs (page ID 6081) and Downloads (page ID 6080)
4. `wp_update_settings` with `{ page_for_posts: 6081, show_on_front: "page" }` to wire up the Blogs page as the WP posts index
