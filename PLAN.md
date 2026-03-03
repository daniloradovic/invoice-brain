# PLAN.md — Invoice Brain Progress Tracker

> Check off tasks as you complete them. Each task corresponds to a prompt in SPEC.md.

---

## Phase 1 — Foundation

- [ ] **TASK 01** — Laravel 12 install, SQLite, packages (`laravel/mcp`, dompdf, breeze)
- [ ] **TASK 01b** — MCP Server class (`InvoiceBrainServer`) + register in `routes/ai.php`
- [ ] **TASK 02** — Enums: `InvoiceStatus`, `WorkLogStatus`
- [ ] **TASK 03** — Migrations: clients, invoices, invoice_line_items, work_logs
- [ ] **TASK 04** — Models & Factories: Client, Invoice, InvoiceLineItem, WorkLog
- [ ] **TASK 05** — Services: InvoiceNumberService, MoneyService, InvoicePdfService
- [ ] **TASK 06** — Mail classes: InvoiceMail, PaymentReminderMail + Blade mail views
- [ ] **TASK 07** — Demo seeder: 4 clients, invoices in key states, unbilled work logs

**Phase 1 done when:** `php artisan db:seed` runs clean and all 4 demo clients exist with correct data.

---

## Phase 2 — Web UI

- [ ] **TASK 08** — Controllers + routes (Dashboard, Client, Invoice, WorkLog)
- [ ] **TASK 09** — Blade views (layout, dashboard, clients, invoices, work logs, PDF template)

**Phase 2 done when:** `php artisan serve` → dashboard shows stats, all 4 nav pages load without errors.

---

## Phase 3 — MCP Layer

- [ ] **TASK 10** — MCP Resources (ClientResource, InvoiceResource, WorkLogResource, ReportResource)
- [ ] **TASK 11** — MCP Tools set 1: create_client, update_client_notes, log_work, bulk_log_work, create_invoice, create_invoice_from_worklogs
- [ ] **TASK 12** — MCP Tools set 2: send_invoice, mark_invoice_paid, add_line_item, send_payment_reminder, bulk_send_reminders, cancel_invoice, get_revenue_report, get_client_report
- [ ] **TASK 13** — MCP Inspector verification (all resources + key tools smoke tested)

**Phase 3 done when:** `php artisan mcp:inspector invoice-brain` shows 10 resources + 14 tools, all return data without errors.

---

## Phase 4 — Polish & Docs

- [ ] **TASK 14** — README.md with Claude Desktop config + demo prompts + reference tables
- [ ] **TASK 15** — Status badge component, mark-overdue command, full smoke test

**Phase 4 done when:** Claude Desktop connects, demo prompt 1 returns a meaningful response.

---

## MCP Resources Checklist

| URI | Implemented | Tested |
|---|---|---|
| `clients://list` | ☐ | ☐ |
| `clients://{id}` | ☐ | ☐ |
| `invoices://list` | ☐ | ☐ |
| `invoices://{id}` | ☐ | ☐ |
| `invoices://outstanding` | ☐ | ☐ |
| `invoices://overdue` | ☐ | ☐ |
| `invoices://draft` | ☐ | ☐ |
| `worklogs://unbilled` | ☐ | ☐ |
| `worklogs://unbilled/{client_id}` | ☐ | ☐ |
| `reports://summary` | ☐ | ☐ |

## MCP Tools Checklist

| Tool | Implemented | Tested |
|---|---|---|
| `create_client` | ☐ | ☐ |
| `update_client_notes` | ☐ | ☐ |
| `log_work` | ☐ | ☐ |
| `bulk_log_work` | ☐ | ☐ |
| `create_invoice` | ☐ | ☐ |
| `create_invoice_from_worklogs` | ☐ | ☐ |
| `send_invoice` | ☐ | ☐ |
| `mark_invoice_paid` | ☐ | ☐ |
| `add_line_item` | ☐ | ☐ |
| `send_payment_reminder` | ☐ | ☐ |
| `bulk_send_reminders` | ☐ | ☐ |
| `cancel_invoice` | ☐ | ☐ |
| `get_revenue_report` | ☐ | ☐ |
| `get_client_report` | ☐ | ☐ |

---

## Phase 5 — Railway Deployment

- [ ] **TASK 16** — HTTP/SSE transport + Sanctum auth + `mcp:token` Artisan command
- [ ] **TASK 17** — Railway config (Procfile, railway.json, Postgres, deploy + smoke test)

**Phase 5 done when:** Claude Desktop connects to the live Railway URL with a Bearer token and demo prompt 1 returns a correct response.

---

## Demo Run Checklist (pre-show-and-tell)

- [ ] `railway up` deployed — confirm green in Railway dashboard
- [ ] MCP token copied from deploy logs
- [ ] Claude Desktop config updated with Railway URL + Bearer token
- [ ] Claude Desktop restarted — invoice-brain server connected (green dot)
- [ ] Demo Prompt 1 tested: Monday morning catchup
- [ ] Demo Prompt 2 tested: Invoice Bright Studio's unbilled work
- [ ] Demo Prompt 3 tested: Bulk send reminders to overdue clients
- [ ] Demo Prompt 4 tested: Log work + invoice + send in one sentence
- [ ] Demo Prompt 5 tested: Natural language invoice for Nova Health
- [ ] Demo Prompt 6 tested: Business insight / client comparison

---

## Known Issues / Notes

> Add any blockers, workarounds, or TODOs here during development.

-
