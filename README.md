# Invoice Brain

Invoice Brain is a Laravel 12 demo application that showcases the first-party `laravel/mcp` integration. It is a billing and invoicing tool where an AI assistant (Claude via MCP) can read your billing state and take real actions — creating invoices, sending payment reminders, logging work, and generating reports — all through natural conversation.

The real point of this project is to demonstrate what MCP makes possible: instead of clicking through a UI, you tell Claude what you need in plain English. Claude reads the live data through MCP resources, reasons about it, and executes the right tools. The web UI exists only to make the data visible; the real power is in the Claude Desktop integration.

---

## Prerequisites

- **PHP 8.3+** with the `sqlite3` and `dom` extensions
- **Composer**
- **Claude Desktop** ([download](https://claude.ai/download))

---

## Installation

```bash
git clone https://github.com/daniloradovic/invoice-brain.git
cd invoice-brain

composer install

cp .env.example .env
php artisan key:generate

php artisan migrate --seed
```

This seeds the database with 4 demo clients, several invoices in various states, and unbilled work logs ready to invoice.

---

## Web UI

Start the development server:

```bash
php artisan serve
```

Visit [http://localhost:8000](http://localhost:8000) to see the dashboard with revenue stats, overdue invoices, and draft invoices.

---

## Claude Desktop Setup

Invoice Brain uses the `laravel/mcp` stdio transport — no separate server process needed. Claude Desktop spawns the process directly.

Add the following to your `claude_desktop_config.json`:

**macOS:** `~/Library/Application Support/Claude/claude_desktop_config.json`  
**Windows:** `%APPDATA%\Claude\claude_desktop_config.json`

```json
{
  "mcpServers": {
    "invoice-brain": {
      "command": "php",
      "args": ["artisan", "mcp:serve", "invoice-brain"],
      "cwd": "/ABSOLUTE/PATH/TO/invoice-brain",
      "env": { "APP_ENV": "local" }
    }
  }
}
```

> **Important:** Replace `/ABSOLUTE/PATH/TO/invoice-brain` with the actual absolute path to your project directory (e.g. `/Users/yourname/projects/invoice-brain`). The server name `invoice-brain` must match the `Mcp::local()` name registered in `routes/ai.php`.

Restart Claude Desktop after editing the config. You should see a green "invoice-brain" badge in the Claude Desktop toolbar when the connection is live.

---

## Demo Prompts

Copy and paste these into Claude Desktop to explore the integration:

**1. Get a full overview**
```
Give me a full picture of where things stand right now — clients, outstanding invoices, overdue amounts, and any unbilled work that should be invoiced.
```

**2. Chase overdue payments**
```
Send payment reminders to all overdue invoices. Use a professional but firm tone and mention that payment is now past due.
```

**3. Invoice unbilled work**
```
Bright Studio has unbilled work logs. Create an invoice for all of them and tell me the total.
```

**4. Log work, then invoice and send**
```
Log 3 hours for TechStart today for "Sprint planning and backlog refinement". Then create an invoice for all their unbilled work and send it to them.
```

**5. Mark a payment as received**
```
Acme Corp just paid their oldest overdue invoice. Mark it as paid with today's date and tell me how much we collected.
```

**6. Revenue and client performance report**
```
Give me a revenue summary for this year so far. Which client has generated the most revenue? Are there any clients with a poor payment track record?
```

---

## MCP Resources Reference

These are the read-only data sources Claude uses to understand the current state before taking action.

| URI | Description |
|-----|-------------|
| `clients://list` | All clients with invoice counts, overdue counts, and unbilled hours |
| `clients://{id}` | Single client detail with all invoices and work logs |
| `invoices://list` | All invoices with client name, total, status, and dates |
| `invoices://{id}` | Single invoice with client, line items, and overdue status |
| `invoices://outstanding` | Invoices with status `sent` or `overdue`, sorted by due date |
| `invoices://overdue` | Overdue invoices with calculated `days_overdue` field |
| `invoices://draft` | Draft invoices not yet sent to clients |
| `worklogs://unbilled` | All unbilled work logs grouped by client |
| `worklogs://unbilled/{client_id}` | Unbilled work logs for a single client |
| `reports://summary` | Aggregated revenue stats: YTD invoiced, collected, outstanding, overdue |

---

## MCP Tools Reference

These are the actions Claude can take on your behalf.

| Tool | What it does |
|------|-------------|
| `create_client` | Creates a new client with name, email, rate, and payment terms |
| `update_client_notes` | Appends a timestamped note to a client record |
| `log_work` | Logs a single unbilled work entry for a client |
| `bulk_log_work` | Logs multiple work entries in one call |
| `create_invoice` | Creates an invoice with explicit line items |
| `create_invoice_from_worklogs` | Creates an invoice from existing unbilled work logs and marks them as billed |
| `send_invoice` | Emails a draft invoice to the client as a PDF attachment and marks it sent |
| `mark_invoice_paid` | Records an invoice as paid with an optional payment date |
| `add_line_item` | Adds a line item to an existing draft invoice |
| `send_payment_reminder` | Sends a payment reminder email for an outstanding or overdue invoice |
| `bulk_send_reminders` | Sends reminders to all overdue invoices above a threshold, skipping recently reminded ones |
| `cancel_invoice` | Cancels an invoice and unbills any attached work logs so they can be re-invoiced |
| `get_revenue_report` | Returns revenue stats for a given period (this month, last month, this year, last year) |
| `get_client_report` | Returns lifetime revenue, average invoice size, average days to pay, and unbilled work for a client |
