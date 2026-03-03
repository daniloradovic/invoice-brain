# Show & Tell — Learning Curve Guide
## Invoice Brain: Laravel MCP Server

> This guide explains the *why* behind every architectural decision,
> so you can confidently answer any question from developers during your presentation.

---

## 1. What is MCP and Why Does It Exist?

**The one-line answer:**
MCP (Model Context Protocol) is a standard that lets an LLM talk to your application
the same way a developer would talk to an API — except the LLM figures out *what* to call
and *when*, based on natural language.

**The longer answer worth knowing:**
Before MCP, connecting an AI to your app required custom glue code for every integration:
custom prompts, custom parsing, custom function calling schemas — all non-transferable.
Anthropic open-sourced MCP so that any app that speaks the protocol can connect to
any MCP-compatible client (Claude Desktop, Cursor, custom chatbots) without glue code.
It's the USB-C of AI integrations.

**The analogy that lands:**
*"REST APIs let browsers and mobile apps talk to your backend. MCP lets AI assistants
talk to your backend. Same idea, different client type."*

---

## 2. Resources vs Tools — The Core Distinction

This is the question you'll definitely get. Nail this.

| | Resources | Tools |
|---|---|---|
| What they are | Read-only data snapshots | Actions with side effects |
| HTTP equivalent | `GET` endpoints | `POST/PUT/DELETE` endpoints |
| When Claude uses them | To understand context before acting | To make something happen |
| Example | `invoices://overdue` → list of overdue invoices | `send_payment_reminder` → sends an email |

**Why the split matters:**
Claude is designed to read all relevant resources first, then decide which tools to call.
This mirrors how a good assistant works: *understand the situation, then act.*
If everything were tools, Claude would have no way to "just look" without side effects.

**In Invoice Brain specifically:**
Before sending reminders, Claude reads `invoices://overdue` to understand which invoices
are overdue. Then it calls `send_payment_reminder` for each one. The resource is the
context; the tool is the action.

---

## 3. Why stdio Transport for the Demo?

When Claude Desktop runs your MCP server, it does this:
```bash
php artisan mcp:serve
```
It spawns the PHP process and communicates via stdin/stdout (pipes). This is called
**stdio transport** and is the simplest possible setup — no network port, no auth,
no HTTP overhead. Perfect for a local demo.

**The trade-off:** stdio only works for local processes. For a real product where users
connect remotely, you'd use **HTTP/SSE transport** — your Laravel app exposes an endpoint
(`/mcp/sse`) and clients connect over the network. The `php-mcp/laravel` package
supports both; we just start with stdio for simplicity.

**What to say if asked:** *"Stdio is for local dev. The same tools and resources work
over HTTP/SSE for production deployments — just change one config line."*

---

## 4. How Claude Decides Which Tools to Call

Claude never needs to be told "call tool X". Here's what actually happens:

1. Claude Desktop connects and calls `list_tools` + `list_resources` on startup
2. Your app returns all 14 tools and 10 resources with their names and descriptions
3. When the user types a message, Claude reads those descriptions and reasons:
   *"The user wants to invoice Bright Studio's unbilled work.
   I should check `worklogs://unbilled/{client_id}` first, then call
   `create_invoice_from_worklogs` with the IDs I find."*
4. Claude calls the tools, gets results, and responds in natural language

**This is why tool descriptions are written for Claude, not humans.**
The description IS the documentation Claude reads at decision time. Be specific:
*"Use this when the user has existing unbilled work logs. For ad-hoc invoices
without work logs, use create_invoice instead."* That sentence prevents Claude
from calling the wrong tool.

---

## 5. The `summary` Field Pattern

Every resource in Invoice Brain returns:
```json
{
  "data": { ... },
  "summary": "2 overdue invoices. $3,200 total. Oldest: Acme Corp, 47 days."
}
```

**Why:** LLMs consume tokens. If Claude has to read a full array of 20 invoices to
answer *"what's overdue?"*, it costs tokens and slows the response. The `summary` string
lets Claude answer immediately without parsing the full data. The `data` is there if
Claude needs specifics (IDs for tool calls, amounts for calculations).

**The pattern generalises:** Any time you build an MCP resource, ask *"what would a
smart assistant say about this data in one sentence?"* and put that in `summary`.

---

## 6. Money in Cents — Why This Matters

Every amount in Invoice Brain is stored and passed as **integers in cents**.
`$120.50` is stored as `12050`.

**Why:** Floating point arithmetic is unreliable for money.
`0.1 + 0.2` in PHP (and most languages) is `0.30000000000000004`.
For an invoice app that's embarrassing and potentially dangerous.
Integers never have precision errors. This is the same approach Stripe uses.

**The MoneyService** handles the display conversion:
```php
MoneyService::format(12050) // "$120.50"
MoneyService::toCents("120.50") // 12050
```

Claude always receives amounts as cents in the raw data, and formatted strings in
the summary — so it can both reason about amounts AND quote them nicely.

---

## 7. Status as Computed vs Stored

`overdue` is not stored in the database. It's computed:
```php
// Invoice is overdue if: it was sent but not paid, and the due date has passed
public function getIsOverdueAttribute(): bool
{
    return $this->status === InvoiceStatus::Sent && $this->due_at->isPast();
}
```

**Why:** If you store `overdue` as a status, you need a scheduled job to update it.
If that job fails or runs late, your data is wrong. Computing it dynamically means
it's always accurate. The `invoices:mark-overdue` Artisan command exists for
*display* purposes only (so the web UI shows the right badge) — the truth lives
in the `due_at` date + `sent` status, not in a stored `overdue` string.

---

## 8. The `create_invoice_from_worklogs` Pattern

This is the most impressive demo tool. Here's how it works end-to-end:

```
User: "Invoice Bright Studio for last week's work"
       ↓
Claude reads: worklogs://unbilled/2
→ finds 3 entries: 4hrs, 2.5hrs, 3hrs at $95/hr
       ↓
Claude calls: create_invoice_from_worklogs
  { client_id: 2, worklog_ids: [5, 6, 7] }
       ↓
Tool creates Invoice with 3 line items
Tool sets work_logs.status = 'billed'
Tool sets work_logs.invoice_id = new_invoice.id
Tool returns: { invoice_number: "INV-2025-0005", total: "$902.50", due_at: "..." }
       ↓
Claude: "I've created invoice INV-2025-0005 for Bright Studio — $902.50 for 9.5 hours
         of work. Due in 14 days. Want me to send it now?"
```

The key detail that impresses people: **the work logs are automatically marked as billed**,
so they can never be accidentally double-invoiced. Claude doesn't need to do that — the
tool handles it atomically.

---

## 9. What MCP Is NOT Good For

Be ready to give a nuanced answer. MCP is not a silver bullet.

**MCP is overkill when:**
- You just want to paste data into Claude once for analysis → just paste it
- The task is purely generative (write an email, summarise a document) → no app integration needed
- Your users are non-technical and won't have Claude Desktop → HTTP chat widget is better

**MCP shines when:**
- The AI needs *live* data from your app (not a one-time paste)
- The AI needs to *act* on your app (create, update, send)
- You want the same tools available across multiple AI clients (Claude Desktop, Cursor, custom)
- The read→reason→act loop needs to repeat multiple times in one conversation

---

## 10. Questions You'll Probably Get

**"Is this secure? Can Claude delete everything?"**
In this demo: no auth, so yes, Claude has full access. In production, you'd wrap the
MCP server behind authentication and scope tools to what the user is allowed to do.
The `php-mcp/laravel` package supports middleware on tool registration.

**"Can this work with ChatGPT or other LLMs?"**
MCP is an open protocol. OpenAI has announced MCP support. Cursor, Continue, and other
tools already support it. The tools you write once work everywhere.

**"What about rate limiting / cost?"**
Each tool call is one LLM turn. A 4-step conversation (read → decide → act → confirm)
uses ~4 API calls. For Claude Sonnet that's fractions of a cent. Not a real concern
for internal tools.

**"Does Laravel need to be running for this to work?"**
For stdio: only when the MCP client (Claude Desktop) is active — it spawns and kills
the PHP process automatically. For HTTP/SSE: yes, the app needs to be running as a
normal web server.

**"What's the difference between this and a REST API with a chatbot on top?"**
Great question. A chatbot on a REST API needs a custom integration layer: you write
code to parse the LLM's intent and map it to API calls. With MCP, the LLM reads
your tool descriptions and does that mapping itself. Less glue code, and it works
with any MCP-compatible client without changes.

---

## Demo Flow (Recommended Order)

1. **Open web UI first** — show the dashboard, explain the domain. "This is a normal
   Laravel billing app. Four clients, some overdue invoices, some unbilled work."

2. **Open Claude Desktop** — "Now let's connect an AI assistant to it."

3. **Run Demo Prompt 1** (Monday morning catchup) — show Claude reading multiple
   resources and synthesising a summary. Emphasise: *"I didn't tell it what to look at."*

4. **Run Demo Prompt 4** (log + invoice + send in one sentence) — this is the peak
   demo. Three tools, one sentence, email sent, work logs billed. Refresh web UI to
   show the changes persisted.

5. **Run Demo Prompt 6** (business insight) — show Claude *reasoning* over data, not
   just retrieving it. No single tool does this — it calls `get_client_report` for each
   client and compares. The AI is doing the analysis.

6. **Show the code** — open `app/Mcp/Tools/CreateInvoiceFromWorklogsTool.php`. It's
   ~60 lines of plain PHP. "This is all it takes to give an AI the ability to invoice
   a client."
