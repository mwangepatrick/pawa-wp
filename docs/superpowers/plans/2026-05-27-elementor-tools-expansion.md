# Elementor Tools Expansion Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add five new Elementor MCP tools (`wp_get_elementor_data`, `wp_get_elementor_kit`, `wp_update_elementor_kit`, `wp_get_elementor_page_settings`, `wp_update_elementor_page_settings`), fix a post-type bug in the two existing Elementor tools, and close seven design gaps identified in audit.

**Architecture:** All changes live in two files: `src/clients/wp-rest.ts` (generalise the two hardcoded meta methods + add kit-discovery method) and `src/tools/elementor.ts` (full rewrite: new schemas, a `doClearCache` helper replacing duplicated FTP logic, a `mergeById` helper for ID-keyed array merge, and seven tool definitions/handlers). `src/index.ts` needs no change — elementor tools are already wired.

**Tech Stack:** TypeScript, `@modelcontextprotocol/sdk`, `zod`, `zod-to-json-schema`, vitest, WP REST API v2 (`/wp/v2/elementor_library`, `/wp/v2/pages`, `/wp/v2/posts`), FTP (cache clearing)

---

## File Map

| Action | File | What changes |
|---|---|---|
| Modify | `src/clients/wp-rest.ts` | Add `WpElementorKitItem` interface; add `postType` param to `getPostMeta` and `updatePostMeta`; add `findElementorKit()` method |
| Rewrite | `src/tools/elementor.ts` | Extract `doClearCache` + `mergeById` helpers; fix `UpdateElementorDataSchema` (add `post_type`); add 5 new schemas + tool definitions + handlers |
| Create | `tests/elementor.test.ts` | Tests for all 7 tools (2 existing fixed + 5 new) using single and sequential fetch mocking |

---

## Task 1: REST client — generalise meta methods + add kit finder

**Files:**
- Modify: `src/clients/wp-rest.ts`

- [ ] **Step 1: Add `WpElementorKitItem` interface**

Open `src/clients/wp-rest.ts`. After the closing `}` of the `WpSettings` interface (after line 76), insert:

```typescript
export interface WpElementorKitItem {
  id: number;
  meta: Record<string, unknown>;
}
```

- [ ] **Step 2: Add `postType` parameter to `getPostMeta`**

Replace the existing `getPostMeta` method (lines 194–199):

```typescript
  async getPostMeta(postId: number, metaKey: string, postType = "pages"): Promise<unknown> {
    const res = await this.req<{ meta: Record<string, unknown> }>(
      `${this.v2}/${postType}/${postId}?context=edit&_fields=meta`
    );
    return res.meta?.[metaKey] ?? null;
  }
```

- [ ] **Step 3: Add `postType` parameter to `updatePostMeta`**

Replace the existing `updatePostMeta` method (lines 201–206):

```typescript
  async updatePostMeta(postId: number, metaKey: string, value: unknown, postType = "pages"): Promise<void> {
    await this.req(`${this.v2}/${postType}/${postId}`, {
      method: "POST",
      body: JSON.stringify({ meta: { [metaKey]: value } }),
    });
  }
```

- [ ] **Step 4: Add `findElementorKit()` method**

Append before the closing `}` of the `WpRestClient` class (after `updateSettings`, before the final `}`):

```typescript
  async findElementorKit(): Promise<{ id: number } | null> {
    // Fetch all library items with context=edit so _elementor_template_type meta is included.
    // Elementor registers _elementor_template_type with show_in_rest:true for elementor_library.
    const items = await this.req<WpElementorKitItem[]>(
      `${this.v2}/elementor_library?per_page=100&context=edit&_fields=id,meta`
    );
    const kit = items.find(
      (item) => item.meta?.["_elementor_template_type"] === "kit"
    );
    return kit ? { id: kit.id } : null;
  }
```

- [ ] **Step 5: Verify TypeScript compiles**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp && npx tsc --noEmit
```

Expected: no errors.

- [ ] **Step 6: Commit**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp
git add src/clients/wp-rest.ts
git commit -m "fix: generalise getPostMeta/updatePostMeta to accept any post type; add findElementorKit"
```

---

## Task 2: Write all tests (red phase)

**Files:**
- Create: `tests/elementor.test.ts`

- [ ] **Step 1: Create `tests/elementor.test.ts`**

```typescript
import { describe, it, expect, vi, beforeEach } from "vitest";
import { elementorToolHandlers } from "../src/tools/elementor.js";

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

/** Mock every fetch call with the same static response. */
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

/** Mock fetch calls in sequence — each entry consumed once in order. */
function mockFetchSequence(responses: Array<{ data: unknown; ok?: boolean }>) {
  const mockFn = responses.reduce(
    (fn, { data, ok = true }) =>
      fn.mockResolvedValueOnce({
        ok,
        status: ok ? 200 : 400,
        text: async () => JSON.stringify(data),
        json: async () => data,
      }),
    vi.fn()
  );
  vi.stubGlobal("fetch", mockFn);
}

beforeEach(() => vi.restoreAllMocks());

// ─── wp_get_elementor_data ────────────────────────────────────────────────

describe("wp_get_elementor_data", () => {
  it("returns parsed _elementor_data for a page", async () => {
    const elementorData = [{ id: "abc123", elType: "section", elements: [] }];
    mockFetch({ meta: { _elementor_data: JSON.stringify(elementorData) } });
    const handlers = elementorToolHandlers(registry);
    const result = await handlers.wp_get_elementor_data({ site: "mysite", post_id: 99 });
    expect(result.isError).toBeFalsy();
    const parsed = JSON.parse((result as { content: { text: string }[] }).content[0].text);
    expect(parsed[0].id).toBe("abc123");
  });

  it("returns error when no _elementor_data exists", async () => {
    mockFetch({ meta: {} });
    const handlers = elementorToolHandlers(registry);
    const result = await handlers.wp_get_elementor_data({ site: "mysite", post_id: 99 });
    expect(result.isError).toBe(true);
    expect((result as { content: { text: string }[] }).content[0].text).toContain("No _elementor_data");
  });

  it("uses the correct endpoint for post_type posts", async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ meta: { _elementor_data: JSON.stringify([]) } }),
      text: async () => "",
    });
    vi.stubGlobal("fetch", fetchMock);
    const handlers = elementorToolHandlers(registry);
    await handlers.wp_get_elementor_data({ site: "mysite", post_id: 5, post_type: "posts" });
    expect(fetchMock.mock.calls[0][0]).toContain("/posts/5");
  });
});

// ─── wp_update_elementor_data (fixed: now accepts post_type) ─────────────

describe("wp_update_elementor_data", () => {
  it("applies replacements to pages (default post_type)", async () => {
    mockFetch({ meta: { _elementor_data: '{"text":"Hello World"}' } });
    const handlers = elementorToolHandlers(registry);
    const result = await handlers.wp_update_elementor_data({
      site: "mysite",
      post_id: 10,
      replacements: [{ search: "Hello World", replace: "Welcome" }],
    });
    expect(result.isError).toBeFalsy();
    expect((result as { content: { text: string }[] }).content[0].text).toContain("Hello World");
  });

  it("uses /posts/ endpoint when post_type is posts", async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ meta: { _elementor_data: '{"text":"Hi"}' } }),
      text: async () => "",
    });
    vi.stubGlobal("fetch", fetchMock);
    const handlers = elementorToolHandlers(registry);
    await handlers.wp_update_elementor_data({
      site: "mysite",
      post_id: 7,
      post_type: "posts",
      replacements: [{ search: "Hi", replace: "Hey" }],
    });
    // First call is GET (getPostMeta), should hit /posts/7
    expect(fetchMock.mock.calls[0][0]).toContain("/posts/7");
    // Second call is POST (updatePostMeta), should also hit /posts/7
    expect(fetchMock.mock.calls[1][0]).toContain("/posts/7");
  });
});

// ─── wp_get_elementor_kit ────────────────────────────────────────────────

describe("wp_get_elementor_kit", () => {
  it("finds the kit and returns its settings", async () => {
    const kitSettings = {
      system_colors: [{ _id: "primary", title: "Primary", color: "#EC4899" }],
    };
    mockFetchSequence([
      // Call 1: findElementorKit → list elementor_library items
      { data: [{ id: 42, meta: { _elementor_template_type: "kit" } }] },
      // Call 2: getPostMeta(42, "_elementor_page_settings", "elementor_library")
      { data: { meta: { _elementor_page_settings: JSON.stringify(kitSettings) } } },
    ]);
    const handlers = elementorToolHandlers(registry);
    const result = await handlers.wp_get_elementor_kit({ site: "mysite" });
    expect(result.isError).toBeFalsy();
    const parsed = JSON.parse((result as { content: { text: string }[] }).content[0].text);
    expect(parsed.kit_id).toBe(42);
    expect(parsed.settings.system_colors[0].color).toBe("#EC4899");
  });

  it("returns error when no kit is found", async () => {
    // Library has items but none are type 'kit'
    mockFetchSequence([
      { data: [{ id: 1, meta: { _elementor_template_type: "page" } }] },
    ]);
    const handlers = elementorToolHandlers(registry);
    const result = await handlers.wp_get_elementor_kit({ site: "mysite" });
    expect(result.isError).toBe(true);
    expect((result as { content: { text: string }[] }).content[0].text).toContain("No Elementor kit found");
  });

  it("returns empty settings object when kit has no _elementor_page_settings", async () => {
    mockFetchSequence([
      { data: [{ id: 42, meta: { _elementor_template_type: "kit" } }] },
      { data: { meta: {} } },
    ]);
    const handlers = elementorToolHandlers(registry);
    const result = await handlers.wp_get_elementor_kit({ site: "mysite" });
    expect(result.isError).toBeFalsy();
    const parsed = JSON.parse((result as { content: { text: string }[] }).content[0].text);
    expect(parsed.kit_id).toBe(42);
    expect(parsed.settings).toEqual({});
  });
});

// ─── wp_update_elementor_kit ─────────────────────────────────────────────

describe("wp_update_elementor_kit", () => {
  const existingSettings = {
    system_colors: [
      { _id: "primary", title: "Primary", color: "#old-primary" },
      { _id: "secondary", title: "Secondary", color: "#54595F" },
    ],
    system_typography: [
      { _id: "primary", title: "Primary", typography_font_family: "Roboto" },
    ],
    custom_css: "/* old */",
  };

  it("merges system_colors by _id, preserving untouched slots", async () => {
    const fetchMock = vi.fn()
      .mockResolvedValueOnce({ ok: true, status: 200, json: async () => [{ id: 42, meta: { _elementor_template_type: "kit" } }], text: async () => "" })
      .mockResolvedValueOnce({ ok: true, status: 200, json: async () => ({ meta: { _elementor_page_settings: JSON.stringify(existingSettings) } }), text: async () => "" })
      .mockResolvedValueOnce({ ok: true, status: 200, json: async () => ({}), text: async () => "" });
    vi.stubGlobal("fetch", fetchMock);

    const handlers = elementorToolHandlers(registry);
    const result = await handlers.wp_update_elementor_kit({
      site: "mysite",
      system_colors: [{ _id: "primary", color: "#EC4899" }],
    });

    expect(result.isError).toBeFalsy();

    // Inspect what was written to the REST API (3rd fetch call, POST body)
    const postBody = JSON.parse(fetchMock.mock.calls[2][1].body as string) as {
      meta: { _elementor_page_settings: string };
    };
    const written = JSON.parse(postBody.meta._elementor_page_settings) as {
      system_colors: Array<{ _id: string; color: string }>;
    };

    expect(written.system_colors).toHaveLength(2); // both slots preserved
    expect(written.system_colors.find((c) => c._id === "primary")?.color).toBe("#EC4899");
    expect(written.system_colors.find((c) => c._id === "secondary")?.color).toBe("#54595F"); // untouched
  });

  it("appends a new custom colour that has no matching _id", async () => {
    const fetchMock = vi.fn()
      .mockResolvedValueOnce({ ok: true, status: 200, json: async () => [{ id: 42, meta: { _elementor_template_type: "kit" } }], text: async () => "" })
      .mockResolvedValueOnce({ ok: true, status: 200, json: async () => ({ meta: { _elementor_page_settings: JSON.stringify({ custom_colors: [] }) } }), text: async () => "" })
      .mockResolvedValueOnce({ ok: true, status: 200, json: async () => ({}), text: async () => "" });
    vi.stubGlobal("fetch", fetchMock);

    const handlers = elementorToolHandlers(registry);
    await handlers.wp_update_elementor_kit({
      site: "mysite",
      custom_colors: [{ _id: "brand-magenta", title: "Brand Magenta", color: "#EC4899" }],
    });

    const postBody = JSON.parse(fetchMock.mock.calls[2][1].body as string) as {
      meta: { _elementor_page_settings: string };
    };
    const written = JSON.parse(postBody.meta._elementor_page_settings) as {
      custom_colors: Array<{ _id: string; color: string }>;
    };

    expect(written.custom_colors).toHaveLength(1);
    expect(written.custom_colors[0]._id).toBe("brand-magenta");
  });

  it("replaces custom_css entirely when provided", async () => {
    const fetchMock = vi.fn()
      .mockResolvedValueOnce({ ok: true, status: 200, json: async () => [{ id: 42, meta: { _elementor_template_type: "kit" } }], text: async () => "" })
      .mockResolvedValueOnce({ ok: true, status: 200, json: async () => ({ meta: { _elementor_page_settings: JSON.stringify(existingSettings) } }), text: async () => "" })
      .mockResolvedValueOnce({ ok: true, status: 200, json: async () => ({}), text: async () => "" });
    vi.stubGlobal("fetch", fetchMock);

    const handlers = elementorToolHandlers(registry);
    await handlers.wp_update_elementor_kit({ site: "mysite", custom_css: "body { margin: 0; }" });

    const postBody = JSON.parse(fetchMock.mock.calls[2][1].body as string) as {
      meta: { _elementor_page_settings: string };
    };
    const written = JSON.parse(postBody.meta._elementor_page_settings) as { custom_css: string };
    expect(written.custom_css).toBe("body { margin: 0; }");
  });

  it("success message includes updated field names and kit id", async () => {
    mockFetchSequence([
      { data: [{ id: 42, meta: { _elementor_template_type: "kit" } }] },
      { data: { meta: { _elementor_page_settings: JSON.stringify(existingSettings) } } },
      { data: {} },
    ]);
    const handlers = elementorToolHandlers(registry);
    const result = await handlers.wp_update_elementor_kit({
      site: "mysite",
      system_colors: [{ _id: "primary", color: "#EC4899" }],
      custom_css: "/* new */",
    });
    const text = (result as { content: { text: string }[] }).content[0].text;
    expect(text).toContain("Kit #42");
    expect(text).toContain("system_colors");
    expect(text).toContain("custom_css");
  });
});

// ─── wp_get_elementor_page_settings ──────────────────────────────────────

describe("wp_get_elementor_page_settings", () => {
  it("returns parsed page settings", async () => {
    const pageSettings = { template: "canvas", hide_title: "yes" };
    mockFetch({ meta: { _elementor_page_settings: JSON.stringify(pageSettings) } });
    const handlers = elementorToolHandlers(registry);
    const result = await handlers.wp_get_elementor_page_settings({ site: "mysite", post_id: 55 });
    expect(result.isError).toBeFalsy();
    const parsed = JSON.parse((result as { content: { text: string }[] }).content[0].text);
    expect(parsed.template).toBe("canvas");
    expect(parsed.hide_title).toBe("yes");
  });

  it("returns empty object when no settings exist", async () => {
    mockFetch({ meta: {} });
    const handlers = elementorToolHandlers(registry);
    const result = await handlers.wp_get_elementor_page_settings({ site: "mysite", post_id: 55 });
    expect(result.isError).toBeFalsy();
    const parsed = JSON.parse((result as { content: { text: string }[] }).content[0].text);
    expect(parsed).toEqual({});
  });

  it("uses the correct endpoint for elementor_library post type", async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      status: 200,
      json: async () => ({ meta: { _elementor_page_settings: "{}" } }),
      text: async () => "",
    });
    vi.stubGlobal("fetch", fetchMock);
    const handlers = elementorToolHandlers(registry);
    await handlers.wp_get_elementor_page_settings({
      site: "mysite",
      post_id: 42,
      post_type: "elementor_library",
    });
    expect(fetchMock.mock.calls[0][0]).toContain("/elementor_library/42");
  });
});

// ─── wp_update_elementor_page_settings ───────────────────────────────────

describe("wp_update_elementor_page_settings", () => {
  it("shallow-merges new keys into existing settings", async () => {
    const existing = { template: "canvas", hide_title: "yes" };
    const fetchMock = vi.fn()
      .mockResolvedValueOnce({ ok: true, status: 200, json: async () => ({ meta: { _elementor_page_settings: JSON.stringify(existing) } }), text: async () => "" })
      .mockResolvedValueOnce({ ok: true, status: 200, json: async () => ({}), text: async () => "" });
    vi.stubGlobal("fetch", fetchMock);

    const handlers = elementorToolHandlers(registry);
    const result = await handlers.wp_update_elementor_page_settings({
      site: "mysite",
      post_id: 55,
      settings: { post_title: "Custom Title" },
    });

    expect(result.isError).toBeFalsy();
    const postBody = JSON.parse(fetchMock.mock.calls[1][1].body as string) as {
      meta: { _elementor_page_settings: string };
    };
    const written = JSON.parse(postBody.meta._elementor_page_settings) as Record<string, unknown>;
    expect(written.template).toBe("canvas");     // preserved
    expect(written.hide_title).toBe("yes");       // preserved
    expect(written.post_title).toBe("Custom Title"); // new
  });

  it("overwrites an existing key with the provided value", async () => {
    const existing = { template: "canvas" };
    const fetchMock = vi.fn()
      .mockResolvedValueOnce({ ok: true, status: 200, json: async () => ({ meta: { _elementor_page_settings: JSON.stringify(existing) } }), text: async () => "" })
      .mockResolvedValueOnce({ ok: true, status: 200, json: async () => ({}), text: async () => "" });
    vi.stubGlobal("fetch", fetchMock);

    const handlers = elementorToolHandlers(registry);
    await handlers.wp_update_elementor_page_settings({
      site: "mysite",
      post_id: 55,
      settings: { template: "full-width" },
    });

    const postBody = JSON.parse(fetchMock.mock.calls[1][1].body as string) as {
      meta: { _elementor_page_settings: string };
    };
    const written = JSON.parse(postBody.meta._elementor_page_settings) as { template: string };
    expect(written.template).toBe("full-width");
  });

  it("success message lists updated keys", async () => {
    mockFetchSequence([
      { data: { meta: { _elementor_page_settings: "{}" } } },
      { data: {} },
    ]);
    const handlers = elementorToolHandlers(registry);
    const result = await handlers.wp_update_elementor_page_settings({
      site: "mysite",
      post_id: 55,
      settings: { hide_title: "yes", template: "canvas" },
    });
    const text = (result as { content: { text: string }[] }).content[0].text;
    expect(text).toContain("55");
    expect(text).toContain("hide_title");
    expect(text).toContain("template");
  });
});
```

- [ ] **Step 2: Run tests — verify they all fail**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp && npx vitest run tests/elementor.test.ts 2>&1 | tail -20
```

Expected: multiple FAIL — `elementorToolHandlers` exists but tools like `wp_get_elementor_data` are not yet exported, and the `post_type` param doesn't exist yet.

---

## Task 3: Rewrite `src/tools/elementor.ts`

**Files:**
- Rewrite: `src/tools/elementor.ts`

- [ ] **Step 1: Replace the full file content**

Write the following as the complete contents of `src/tools/elementor.ts`:

```typescript
import type { Registry, SiteConfig } from "../registry.js";
import { WpRestClient } from "../clients/wp-rest.js";
import { FtpClient } from "../clients/ftp.js";
import { z } from "zod";
import { zodToJsonSchema } from "zod-to-json-schema";

// ─── Schemas ─────────────────────────────────────────────────────────────────

const SiteParam = z.string().min(1).describe("Site alias from sites.json");

const PostTypeParam = z
  .enum(["pages", "posts", "elementor_library"])
  .default("pages")
  .describe(
    "WordPress REST endpoint segment: 'pages' for page post type, 'posts' for post type, 'elementor_library' for Elementor library items"
  );

const GetElementorDataSchema = z.object({
  site: SiteParam,
  post_id: z.number().int().positive().describe("Post or page ID"),
  post_type: PostTypeParam,
});

const UpdateElementorDataSchema = z.object({
  site: SiteParam,
  post_id: z.number().int().positive().describe("Post or page ID"),
  post_type: PostTypeParam,
  replacements: z
    .array(z.object({ search: z.string(), replace: z.string() }))
    .min(1)
    .describe("Array of search/replace pairs to apply to the Elementor JSON data"),
});

const ClearElementorCacheSchema = z.object({ site: SiteParam });

const GetElementorKitSchema = z.object({ site: SiteParam });

const KitColorEntrySchema = z.object({
  _id: z
    .string()
    .describe(
      "Colour slot identifier — e.g. 'primary', 'secondary', 'text', 'accent' for system slots; any string for custom slots"
    ),
  title: z.string().optional().describe("Human-readable label shown in the Elementor panel"),
  color: z.string().describe("CSS colour value, e.g. '#EC4899' or 'rgba(236,72,153,0.5)'"),
});

const KitTypographyEntrySchema = z.object({
  _id: z
    .string()
    .describe(
      "Typography slot identifier — e.g. 'primary', 'secondary', 'text', 'accent' for system slots"
    ),
  title: z.string().optional(),
  typography_font_family: z.string().optional().describe("Font family name, e.g. 'Poppins'"),
  typography_font_size: z
    .object({ unit: z.string(), size: z.number() })
    .optional()
    .describe("Font size with unit, e.g. { unit: 'px', size: 16 }"),
  typography_font_weight: z.string().optional().describe("e.g. '400', '600', 'bold'"),
  typography_font_style: z.string().optional().describe("e.g. 'normal', 'italic'"),
  typography_text_transform: z.string().optional().describe("e.g. 'none', 'uppercase'"),
  typography_letter_spacing: z
    .object({ unit: z.string(), size: z.number() })
    .optional(),
  typography_line_height: z.object({ unit: z.string(), size: z.number() }).optional(),
});

const UpdateElementorKitSchema = z.object({
  site: SiteParam,
  system_colors: z
    .array(KitColorEntrySchema)
    .optional()
    .describe(
      "Update built-in colour slots by _id. Only specified slots are changed; others are preserved."
    ),
  custom_colors: z
    .array(KitColorEntrySchema)
    .optional()
    .describe("Update or append custom colour entries. Matched by _id; appended if _id is new."),
  system_typography: z
    .array(KitTypographyEntrySchema)
    .optional()
    .describe("Update built-in typography slots by _id. Unspecified slots are preserved."),
  custom_typography: z
    .array(KitTypographyEntrySchema)
    .optional()
    .describe("Update or append custom typography entries."),
  custom_css: z
    .string()
    .optional()
    .describe("Full replacement for the kit-level custom CSS block."),
});

const GetElementorPageSettingsSchema = z.object({
  site: SiteParam,
  post_id: z.number().int().positive().describe("Post or page ID"),
  post_type: PostTypeParam,
});

const UpdateElementorPageSettingsSchema = z.object({
  site: SiteParam,
  post_id: z.number().int().positive().describe("Post or page ID"),
  post_type: PostTypeParam,
  settings: z
    .record(z.unknown())
    .describe(
      "Key-value pairs to shallow-merge into _elementor_page_settings. Existing keys not listed here are preserved."
    ),
});

// ─── Utilities ────────────────────────────────────────────────────────────────

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

/**
 * Merge an array of objects by their `_id` field.
 * Existing entries with a matching _id are updated (shallow merged).
 * Entries whose _id is not in `existing` are appended.
 */
function mergeById<T extends { _id: string }>(existing: T[], updates: T[]): T[] {
  const result = [...existing];
  for (const update of updates) {
    const idx = result.findIndex((e) => e._id === update._id);
    if (idx >= 0) {
      result[idx] = { ...result[idx], ...update };
    } else {
      result.push(update);
    }
  }
  return result;
}

/**
 * Clear Elementor's generated CSS cache via FTP.
 * Tries public_html/... first, falls back to root-level install path.
 * Handles FTP unavailability gracefully — returns a message instead of throwing.
 */
async function doClearCache(config: SiteConfig): Promise<string> {
  const ftp = new FtpClient(config);
  const candidates = [
    "public_html/wp-content/uploads/elementor/css",
    "wp-content/uploads/elementor/css",
  ];
  for (const dir of candidates) {
    let files: { name: string }[] = [];
    try {
      files = await ftp.list(dir);
    } catch {
      continue; // try next candidate
    }
    let deleted = 0;
    for (const f of files) {
      if (f.name.endsWith(".css") || f.name.endsWith(".css.gz")) {
        try {
          await ftp.delete(`${dir}/${f.name}`);
          deleted++;
        } catch {
          // skip locked files
        }
      }
    }
    return `Cleared Elementor CSS cache: deleted ${deleted} file(s) from ${dir}`;
  }
  return "Note: FTP cache deletion was not available. Elementor will regenerate its cache on next page load. If styles seem stale, manually click 'Regenerate Files & Data' in Elementor > Tools in the WordPress admin.";
}

/**
 * Parse a meta value that Elementor stores as a JSON-encoded string.
 * Returns an empty object on null/undefined or parse failure.
 */
function parseMetaJson(raw: unknown): Record<string, unknown> {
  if (!raw) return {};
  if (typeof raw === "string") {
    try {
      return JSON.parse(raw) as Record<string, unknown>;
    } catch {
      return {};
    }
  }
  if (typeof raw === "object" && raw !== null) return raw as Record<string, unknown>;
  return {};
}

// ─── Tool Definitions ─────────────────────────────────────────────────────────

export function elementorToolDefinitions() {
  return [
    {
      name: "wp_get_elementor_data",
      description:
        "Fetch the _elementor_data widget tree for a post, page, or Elementor library item. Returns parsed JSON so you can inspect the structure before making changes.",
      inputSchema: toSchema(GetElementorDataSchema),
    },
    {
      name: "wp_update_elementor_data",
      description:
        "Update Elementor page/post data by applying search-and-replace pairs to the _elementor_data meta. Use this instead of wp_update_post when the site uses Elementor, otherwise changes won't appear on the live page. Supports pages, posts, and elementor_library items via post_type.",
      inputSchema: toSchema(UpdateElementorDataSchema),
    },
    {
      name: "wp_clear_elementor_cache",
      description:
        "Clear Elementor's CSS/asset cache for a site. Run this after updating Elementor page data or styles so changes reflect immediately on the front-end.",
      inputSchema: toSchema(ClearElementorCacheSchema),
    },
    {
      name: "wp_get_elementor_kit",
      description:
        "Fetch the active Elementor kit and its settings: global colours, typography, custom CSS, and any other site-level styling. The kit is the source of truth for all brand-level styling in Elementor.",
      inputSchema: toSchema(GetElementorKitSchema),
    },
    {
      name: "wp_update_elementor_kit",
      description:
        "Update the active Elementor kit's global settings. Colours and typography entries are merged by _id — unspecified slots are preserved. custom_css is fully replaced when provided. Automatically clears the Elementor CSS cache after writing so changes appear immediately.",
      inputSchema: toSchema(UpdateElementorKitSchema),
    },
    {
      name: "wp_get_elementor_page_settings",
      description:
        "Fetch the _elementor_page_settings for a specific post or page: page template, title bar visibility, status, custom CSS overrides, etc.",
      inputSchema: toSchema(GetElementorPageSettingsSchema),
    },
    {
      name: "wp_update_elementor_page_settings",
      description:
        "Shallow-merge key-value pairs into the _elementor_page_settings of a specific post or page. Existing keys not included in the update are preserved.",
      inputSchema: toSchema(UpdateElementorPageSettingsSchema),
    },
  ];
}

// ─── Tool Handlers ────────────────────────────────────────────────────────────

export function elementorToolHandlers(
  registry: Registry
): Record<string, (args: unknown) => Promise<ToolResponse>> {
  return {
    wp_get_elementor_data: async (args) => {
      try {
        const { site, post_id, post_type } = GetElementorDataSchema.parse(args);
        const raw = await new WpRestClient(registry.getSite(site)).getPostMeta(
          post_id,
          "_elementor_data",
          post_type
        );
        if (!raw) throw new Error(`No _elementor_data found for post ${post_id}`);
        return ok(JSON.stringify(parseMetaJson(raw), null, 2));
      } catch (e) {
        return fail(e);
      }
    },

    wp_update_elementor_data: async (args) => {
      try {
        const { site, post_id, post_type, replacements } = UpdateElementorDataSchema.parse(args);
        const config = registry.getSite(site);
        const client = new WpRestClient(config);

        const raw = await client.getPostMeta(post_id, "_elementor_data", post_type);
        if (!raw) throw new Error(`No _elementor_data found for post ${post_id}`);

        let updated = raw as string;
        const applied: string[] = [];
        for (const { search, replace } of replacements) {
          if (updated.includes(search)) {
            updated = updated.split(search).join(replace);
            applied.push(`"${search}" → "${replace}"`);
          } else {
            applied.push(`(not found) "${search}"`);
          }
        }

        await client.updatePostMeta(post_id, "_elementor_data", updated, post_type);

        return ok(
          `Updated _elementor_data for post ${post_id}:\n` +
            applied.map((r) => `  • ${r}`).join("\n")
        );
      } catch (e) {
        return fail(e);
      }
    },

    wp_clear_elementor_cache: async (args) => {
      try {
        const { site } = ClearElementorCacheSchema.parse(args);
        return ok(await doClearCache(registry.getSite(site)));
      } catch (e) {
        return fail(e);
      }
    },

    wp_get_elementor_kit: async (args) => {
      try {
        const { site } = GetElementorKitSchema.parse(args);
        const client = new WpRestClient(registry.getSite(site));
        const kit = await client.findElementorKit();
        if (!kit) throw new Error("No Elementor kit found on this site");
        const raw = await client.getPostMeta(kit.id, "_elementor_page_settings", "elementor_library");
        const settings = parseMetaJson(raw);
        return ok(JSON.stringify({ kit_id: kit.id, settings }, null, 2));
      } catch (e) {
        return fail(e);
      }
    },

    wp_update_elementor_kit: async (args) => {
      try {
        const {
          site,
          system_colors,
          custom_colors,
          system_typography,
          custom_typography,
          custom_css,
        } = UpdateElementorKitSchema.parse(args);

        const config = registry.getSite(site);
        const client = new WpRestClient(config);

        const kit = await client.findElementorKit();
        if (!kit) throw new Error("No Elementor kit found on this site");

        const raw = await client.getPostMeta(kit.id, "_elementor_page_settings", "elementor_library");
        const existing = parseMetaJson(raw);

        const updated = { ...existing };
        if (system_colors)
          updated.system_colors = mergeById(
            (existing.system_colors as typeof system_colors) ?? [],
            system_colors
          );
        if (custom_colors)
          updated.custom_colors = mergeById(
            (existing.custom_colors as typeof custom_colors) ?? [],
            custom_colors
          );
        if (system_typography)
          updated.system_typography = mergeById(
            (existing.system_typography as typeof system_typography) ?? [],
            system_typography
          );
        if (custom_typography)
          updated.custom_typography = mergeById(
            (existing.custom_typography as typeof custom_typography) ?? [],
            custom_typography
          );
        if (custom_css !== undefined) updated.custom_css = custom_css;

        await client.updatePostMeta(
          kit.id,
          "_elementor_page_settings",
          JSON.stringify(updated),
          "elementor_library"
        );

        const cacheMsg = await doClearCache(config);

        const changes: string[] = [];
        if (system_colors) changes.push(`system_colors (${system_colors.length} slot(s))`);
        if (custom_colors) changes.push(`custom_colors (${custom_colors.length} slot(s))`);
        if (system_typography) changes.push(`system_typography (${system_typography.length} slot(s))`);
        if (custom_typography) changes.push(`custom_typography (${custom_typography.length} slot(s))`);
        if (custom_css !== undefined) changes.push("custom_css");

        return ok(`Kit #${kit.id} updated: ${changes.join(", ")}.\n${cacheMsg}`);
      } catch (e) {
        return fail(e);
      }
    },

    wp_get_elementor_page_settings: async (args) => {
      try {
        const { site, post_id, post_type } = GetElementorPageSettingsSchema.parse(args);
        const raw = await new WpRestClient(registry.getSite(site)).getPostMeta(
          post_id,
          "_elementor_page_settings",
          post_type
        );
        return ok(JSON.stringify(parseMetaJson(raw), null, 2));
      } catch (e) {
        return fail(e);
      }
    },

    wp_update_elementor_page_settings: async (args) => {
      try {
        const { site, post_id, post_type, settings } =
          UpdateElementorPageSettingsSchema.parse(args);
        const client = new WpRestClient(registry.getSite(site));
        const raw = await client.getPostMeta(post_id, "_elementor_page_settings", post_type);
        const existing = parseMetaJson(raw);
        const updated = { ...existing, ...settings };
        await client.updatePostMeta(
          post_id,
          "_elementor_page_settings",
          JSON.stringify(updated),
          post_type
        );
        return ok(
          `Updated _elementor_page_settings for post ${post_id}: ${Object.keys(settings).join(", ")}`
        );
      } catch (e) {
        return fail(e);
      }
    },
  };
}
```

- [ ] **Step 2: Run the elementor tests — verify they now pass**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp && npx vitest run tests/elementor.test.ts 2>&1
```

Expected: all tests PASS.

- [ ] **Step 3: Run the full test suite — no regressions**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp && npx vitest run 2>&1
```

Expected: all tests PASS (registry + menu + settings + elementor).

- [ ] **Step 4: Verify TypeScript compiles clean**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp && npx tsc --noEmit 2>&1
```

Expected: no errors.

- [ ] **Step 5: Commit**

```bash
cd /home/pixel/projects/personal/wp/pawa-wp-mcp
git add src/tools/elementor.ts tests/elementor.test.ts
git commit -m "feat: add wp_get_elementor_data, wp_get_elementor_kit, wp_update_elementor_kit, wp_get_elementor_page_settings, wp_update_elementor_page_settings; fix post_type bug in existing elementor tools"
```
