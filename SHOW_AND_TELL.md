# Invoice Brain — Show & Tell Testing Guide

## Before You Start

**Terminal 1 — web server:**
```bash
php artisan serve
```

**Terminal 2 — fresh demo data:**
```bash
php artisan migrate:fresh --seed --seeder=DemoDataSeeder
```

**Terminal 3 — Claude Code session:**
```bash
cd /Users/daniloradovic/WebDev/Projects/invoice-brain
claude
```

Verify the MCP server is live inside the Claude session:
```
/mcp
```
You should see `invoice-brain ✓` with 14 tools listed.

Open the web UI at **http://localhost:8000** side by side — every write action will be
visible there in real time.

---

## Starting State (after seeding)

| Client        | Rate     | Terms  | State                              |
|---------------|----------|--------|------------------------------------|
| Acme Corp     | $120/hr  | Net 30 | 2 overdue sent invoices ($6,120)   |
| Bright Studio | $95/hr   | Net 14 | 9.5 unbilled hours, 1 paid invoice |
| Nova Health   | $150/hr  | Net 30 | 1 draft invoice ($2,400)           |
| TechStart Ltd | $110/hr  | Net 21 | 7 unbilled hours, no invoices      |

---

## Scene 1 — Orient the AI (read-only)

**Prompt:**
> "What clients do I have? Summarise my current billing situation."

**What Claude does:** reads `clients://list` and `invoices://list`

**Verify:** Claude names all 4 clients, flags the 2 overdue Acme invoices, mentions the
Nova Health draft, and calls out ~16.5 unbilled hours across Bright Studio and TechStart.

---

## Scene 2 — Deep Dive on a Client

**Prompt:**
> "Give me a full report on Acme Corp — how long do they take to pay on average?"

**What Claude does:** reads `clients://2` (or whichever id), calls `get_client_report`

**Verify:** Shows lifetime revenue, outstanding balance ($6,120), average days to pay,
and the client note about paying late.

---

## Scene 3 — Revenue Report

**Prompt:**
> "How much have I invoiced and collected this year? Who's my top client?"

**What Claude does:** calls `get_revenue_report` with `period: this_year`

**Verify:** Returns invoiced total, collected total (only Bright Studio's paid invoice
counts), outstanding amount, and names the top client.

---

## Scene 4 — Log a Single Work Entry

**Prompt:**
> "I spent 3.5 hours on backend work for Acme Corp today."

**What Claude does:** calls `log_work`

**Verify in web UI:** New unbilled work log row appears under Acme Corp.

---

## Scene 5 — Bulk Log Work

**Prompt:**
> "Log this week's work: 2 hours for Nova Health on Monday for platform testing, and
> 4 hours for TechStart on Tuesday for CI/CD setup."

**What Claude does:** calls `bulk_log_work` with 2 entries

**Verify in web UI:** Two new unbilled work log rows appear for the correct clients.

---

## Scene 6 — Invoice From Work Logs

**Prompt:**
> "Invoice Bright Studio for all their unbilled work."

**What Claude does:** reads `worklogs://unbilled/{id}` first, then calls
`create_invoice_from_worklogs`

**Verify in web UI:**
- New draft invoice exists for Bright Studio with 3 line items (React component library,
  design system docs, Figma handoff)
- All 3 Bright Studio work logs are now marked **billed**

---

## Scene 7 — Add a Line Item to a Draft

**Prompt:**
> "Add a $50 travel expense to that Bright Studio invoice."

**What Claude does:** calls `add_line_item` on the draft

**Verify in web UI:** Invoice now has 4 line items, total has increased by $50.

---

## Scene 8 — Send the Invoice

**Prompt:**
> "Send it."

**What Claude does:** calls `send_invoice`

**Verify:**
- Invoice status changes from **draft → sent** in the web UI
- Claude confirms the email was sent to `hello@brightstudio.io`

---

## Scene 9 — Create an Ad-Hoc Invoice

**Prompt:**
> "Create a one-off invoice for TechStart for 10 hours of consulting at their default
> rate, with a note saying this covers the Q1 retainer."

**What Claude does:** calls `create_invoice` with explicit line items and notes

**Verify in web UI:** New draft invoice for TechStart, 10 × $110 = $1,100, notes present.

---

## Scene 10 — Mark an Invoice Paid

**Prompt:**
> "TechStart just paid that invoice."

**What Claude does:** calls `mark_invoice_paid`

**Verify in web UI:** Invoice status changes to **paid**, paid_at date set to today.

---

## Scene 11 — Send a Reminder for One Invoice

**Prompt:**
> "Send a payment reminder to Acme Corp for their oldest overdue invoice."

**What Claude does:** reads `invoices://overdue`, then calls `send_payment_reminder`

**Verify in web UI:** Invoice notes field now has a `[REMINDER SENT YYYY-MM-DD]`
timestamp prepended.

---

## Scene 12 — Bulk Send Reminders

**Prompt:**
> "Send reminders to every overdue invoice."

**What Claude does:** calls `bulk_send_reminders`

**Verify:** Claude reports how many were sent and how many were skipped (the one from
Scene 11 will be skipped — already reminded today).

---

## Scene 13 — Cancel an Invoice

**Prompt:**
> "Cancel the Nova Health draft — the project has been put on hold. Note the reason."

**What Claude does:** calls `cancel_invoice` with reason

**Verify in web UI:**
- Invoice status changes to **cancelled**
- Notes field contains `[CANCELLED YYYY-MM-DD]: project on hold`
- Any attached work logs are returned to **unbilled**

---

## Scene 14 — Update Client Notes

**Prompt:**
> "Note that Acme Corp always pays 2–3 weeks late but they always come through
> eventually — factor this into planning."

**What Claude does:** calls `update_client_notes`

**Verify in web UI:** Client notes updated with a timestamped entry prepended above the
existing note.

---

## Edge Cases — Guardrails

Run these to show the app rejects invalid operations cleanly.

### E1 — Send a non-draft invoice
> "Send Acme Corp's first invoice." *(it's already sent/overdue)*

**Expected:** Error — "status is 'sent', expected 'draft'"

---

### E2 — Double-invoice already-billed work logs
> "Invoice Bright Studio for their unbilled work." *(right after Scene 6)*

**Expected:** Claude reads `worklogs://unbilled/{id}`, finds nothing, and tells you
there are no unbilled logs to invoice.

---

### E3 — Mark a draft invoice as paid
> "Mark the Nova Health draft invoice as paid." *(before sending it)*

**Expected:** Error — "status is 'draft', expected 'sent' or 'overdue'"

---

### E4 — Cancel a paid invoice
> "Cancel the TechStart invoice." *(after marking it paid in Scene 10)*

**Expected:** Error — "cannot be cancelled — it has already been paid"

---

### E5 — Log work with no rate
First create a rate-less client:
> "Create a new client called Ghost Co with email ghost@ghost.com — no default rate."

Then try to log work:
> "Log 2 hours of work for Ghost Co."

**Expected:** Error — "client has no default rate. Please provide a rate in cents."

---

### E6 — Cancel invoice returns work logs to unbilled
1. > "Invoice TechStart for all their unbilled work."
2. > "Actually cancel that invoice — they want to revise the scope."

**Expected:** Invoice cancelled + response confirms work logs returned to unbilled.
Verify in web UI: TechStart work logs are unbilled again.

---

## Reset for Another Run

```bash
php artisan migrate:fresh --seed --seeder=DemoDataSeeder
```

Everything returns to the starting state described at the top of this file.
