# SPEC.md — Invoice Brain Task Specifications

> Each task below is a self-contained Cursor prompt. Copy the block under
> **Cursor Prompt** verbatim into Cursor Agent. Complete tasks in order —
> each one builds on the previous.

---

## TASK 01 — Laravel Installation & Base Config

**Cursor Prompt:**
```
Create a new Laravel 12 project called invoice-brain. Configure it to use SQLite
by updating .env and config/database.php. Create the SQLite file at
database/database.sqlite. Install the following packages:
- laravel/mcp (require) — first-party MCP package
- barryvdh/laravel-dompdf (require)
- laravel/breeze (require-dev, minimal/blade stack, no dark mode)

After installing laravel/mcp, publish its routes file:
  php artisan vendor:publish --tag=mcp-routes
This creates routes/ai.php where MCP servers are registered.

Run `php artisan breeze:install blade --no-interaction`.

Configure APP_NAME="Invoice Brain" in .env.
Confirm the app boots with `php artisan serve`.

Commit:
  git init
  git add .
  git commit -m "Task 01: Laravel 12 project setup with SQLite, laravel/mcp, dompdf, breeze"

Then create a new repo on GitHub named "invoice-brain" and push:
  git remote add origin https://github.com/daniloradovic/invoice-brain.git
  git branch -M main
  git push -u origin main
```

---

## TASK 01b — MCP Server Class

**Cursor Prompt:**
```
Create the central MCP Server class for Invoice Brain using the Laravel 12
first-party laravel/mcp package.

1. Run: php artisan make:mcp-server InvoiceBrainServer
   This creates app/Mcp/Servers/InvoiceBrainServer.php

2. Edit the generated class to set:
   - #[Name('invoice-brain')] attribute
   - #[Version('1.0.0')] attribute
   - #[Instructions('...')] attribute with the text:
     "You are an AI billing assistant for Invoice Brain. You have full access to
      the client list, invoices, work logs, and billing reports. Always check
      relevant resources before taking action. Amounts are always in cents in
      raw data — use the formatted fields for display. Never double-invoice
      unbilled work logs."
   - Leave $tools and $resources arrays empty for now (we will populate them
     as we implement each class)

3. Register the server in routes/ai.php:
   use App\Mcp\Servers\InvoiceBrainServer;
   use Laravel\Mcp\Facades\Mcp;

   Mcp::local('invoice-brain', InvoiceBrainServer::class);

   The local() method uses stdio transport, perfect for Claude Desktop demos.

Confirm the server is registered by running:
  php artisan mcp:inspector invoice-brain

Commit:
  git add .
  git commit -m "Task 01b: InvoiceBrainServer MCP server class registered in routes/ai.php"
```

---

## TASK 02 — Enums

**Cursor Prompt:**
```
Create the following PHP 8.3 backed enums in app/Enums/:

1. app/Enums/InvoiceStatus.php
   cases: Draft, Sent, Paid, Overdue, Cancelled
   string values: 'draft', 'sent', 'paid', 'overdue', 'cancelled'
   add method: label(): string — returns human-readable label
   add method: color(): string — returns a Tailwind color class (e.g. 'green', 'red')
   add static method: paidStatuses(): array — returns [Paid]
   add static method: openStatuses(): array — returns [Sent, Overdue]

2. app/Enums/WorkLogStatus.php
   cases: Unbilled, Billed
   string values: 'unbilled', 'billed'

Commit:
  git add .
  git commit -m "Task 02: InvoiceStatus and WorkLogStatus enums"
```

---

## TASK 03 — Migrations

**Cursor Prompt:**
```
Create the following database migrations in order:

1. create_clients_table
   columns: id, name (string), email (string, unique), address (text, nullable),
   default_rate (unsignedInteger nullable, comment: 'Hourly rate in cents'),
   payment_terms (unsignedTinyInteger default 30, comment: 'Days until invoice due'),
   notes (text, nullable), timestamps

2. create_invoices_table
   columns: id, client_id (foreignId->constrained->cascadeOnDelete),
   invoice_number (string, unique), status (string, default 'draft'),
   issued_at (date), due_at (date), paid_at (date nullable),
   notes (text nullable), timestamps

3. create_invoice_line_items_table
   columns: id, invoice_id (foreignId->constrained->cascadeOnDelete),
   description (string), quantity (decimal 8,2), unit_price (unsignedInteger,
   comment: 'In cents'), timestamps

4. create_work_logs_table
   columns: id, client_id (foreignId->constrained->cascadeOnDelete),
   invoice_id (foreignId nullable, constrained, nullOnDelete),
   description (string), hours (decimal 8,2),
   rate (unsignedInteger, comment: 'In cents'),
   worked_at (date), status (string default 'unbilled'), timestamps

Run `php artisan migrate` and confirm all tables created.

Commit:
  git add .
  git commit -m "Task 03: migrations for clients, invoices, invoice_line_items, work_logs"
```

---

## TASK 04 — Models & Factories

**Cursor Prompt:**
```
Create the following Eloquent models with factories:

1. app/Models/Client.php
   fillable: name, email, address, default_rate, payment_terms, notes
   relationships: hasMany Invoice, hasMany WorkLog
   accessor: getUnbilledHoursAttribute() — sum of unbilled work log hours
   factory: 4 defined states named acme(), brightStudio(), novaHealth(), techStart()
   matching the seed data plan (realistic names, rates between 8000-15000 cents)

2. app/Models/Invoice.php
   fillable: client_id, invoice_number, status, issued_at, due_at, paid_at, notes
   casts: status -> InvoiceStatus enum, issued_at/due_at/paid_at -> date
   relationships: belongsTo Client, hasMany InvoiceLineItem, hasMany WorkLog
   accessor: getTotalAttribute() — sum of all line item totals in cents
   accessor: getIsOverdueAttribute(): bool — due_at < today AND status is Sent
   scope: scopeOverdue() — where due_at < now and status = sent
   scope: scopeOutstanding() — where status in [sent, overdue]
   scope: scopeDraft() — where status = draft
   factory: realistic states

3. app/Models/InvoiceLineItem.php
   fillable: invoice_id, description, quantity, unit_price
   casts: quantity -> decimal:2, unit_price -> integer
   relationships: belongsTo Invoice
   accessor: getTotalAttribute() — quantity * unit_price (in cents)
   factory: basic

4. app/Models/WorkLog.php
   fillable: client_id, invoice_id, description, hours, rate, worked_at, status
   casts: status -> WorkLogStatus enum, worked_at -> date, hours -> decimal:2
   relationships: belongsTo Client, belongsTo Invoice (nullable)
   scope: scopeUnbilled() — where status = unbilled
   scope: scopeForClient(int $clientId)
   factory: realistic states

Commit:
  git add .
  git commit -m "Task 04: Client, Invoice, InvoiceLineItem, WorkLog models and factories"
```

---

## TASK 05 — Services

**Cursor Prompt:**
```
Create the following service classes in app/Services/:

1. app/Services/InvoiceNumberService.php
   Method: generate(): string
   Format: INV-{YEAR}-{4-digit-padded-sequential-number}
   Example: INV-2025-0001, INV-2025-0042
   Sequential number is based on count of invoices created THIS year + 1
   Must be safe if called concurrently (use DB transaction or atomic increment)

2. app/Services/MoneyService.php
   Static method: format(int $cents): string — returns "$1,200.00" style string
   Static method: toCents(float|string $amount): int — converts "120.50" -> 12050
   Static method: fromCents(int $cents): float — converts 12050 -> 120.50

3. app/Services/InvoicePdfService.php
   Method: generate(Invoice $invoice): string — returns PDF binary string
   Uses barryvdh/laravel-dompdf
   Loads view: invoices.pdf (we'll create this view later)
   Passes invoice with loaded relationships: client, lineItems

Register MoneyService as a Blade directive @money($cents) in AppServiceProvider
that outputs formatted money string.

Commit:
  git add .
  git commit -m "Task 05: InvoiceNumberService, MoneyService, InvoicePdfService + @money Blade directive"
```

---

## TASK 06 — Mail Classes

**Cursor Prompt:**
```
Create two Mailable classes:

1. app/Mail/InvoiceMail.php
   Constructor: Invoice $invoice, string $message = ''
   Attaches invoice PDF using InvoicePdfService
   Subject: "Invoice {invoice_number} from Invoice Brain"
   View: mail.invoice
   Passes: invoice (with client + lineItems loaded), customMessage

2. app/Mail/PaymentReminderMail.php
   Constructor: Invoice $invoice, string $message = ''
   Subject: "Payment Reminder: Invoice {invoice_number}"
   View: mail.reminder
   Passes: invoice (with client loaded), customMessage

Create the two Blade mail views at:
- resources/views/mail/invoice.blade.php — professional, includes line items table and total
- resources/views/mail/reminder.blade.php — polite reminder with invoice number, amount, due date

Configure MAIL_MAILER=log in .env so emails are written to the log file (safe for demo).

Commit:
  git add .
  git commit -m "Task 06: InvoiceMail, PaymentReminderMail and Blade mail views"
```

---

## TASK 07 — Demo Seeder

**Cursor Prompt:**
```
Create database/seeders/DemoDataSeeder.php with realistic demo data:

CLIENTS:
1. Acme Corp — email: billing@acme.com, rate: $120/hr, payment_terms: 30
   notes: "Large enterprise client. Tends to pay 2-3 weeks late. Always pays eventually."
2. Bright Studio — email: hello@brightstudio.io, rate: $95/hr, payment_terms: 14
   notes: "Design agency. Fast payer, clear briefs."
3. Nova Health — email: accounts@novahealth.com, rate: $150/hr, payment_terms: 30
   notes: "Healthcare startup. Requires formal invoices with detailed line items."
4. TechStart Ltd — email: finance@techstart.dev, rate: $110/hr, payment_terms: 21

INVOICES (create with InvoiceNumberService):
- Acme Corp: 2 invoices with status='sent', due_at = 45 days ago and 20 days ago
  (these are overdue and will trigger reminder demos)
  Each has 2-3 line items totalling $2,000-$4,000
- Bright Studio: 1 invoice status='paid' paid_at=last month, plus 3 unbilled work logs
  from the past 7 days (these will be used to demo create_invoice_from_worklogs)
- Nova Health: 1 invoice status='draft' (never sent — demo for send_invoice)
  3 line items: consulting days at $800/day
- TechStart Ltd: no invoices, 2 unbilled work logs from this week

WORK LOGS for Bright Studio (unbilled):
- "React component library setup" — 4hrs — yesterday
- "Design system documentation" — 2.5hrs — 2 days ago
- "Figma to code handoff review" — 3hrs — 3 days ago

WORK LOGS for TechStart (unbilled):
- "API integration planning session" — 2hrs — yesterday
- "Authentication flow implementation" — 5hrs — today

Register DemoDataSeeder in DatabaseSeeder.php.
Run `php artisan db:seed` and confirm data exists.

Commit:
  git add .
  git commit -m "Task 07: DemoDataSeeder with 4 clients, invoices, and unbilled work logs"
```

---

## TASK 08 — Web Controllers & Routes

**Cursor Prompt:**
```
Create the following controllers and routes for the web UI:

CONTROLLERS:

1. app/Http/Controllers/DashboardController.php
   index(): loads revenue summary (total invoiced, total paid, outstanding, overdue count)
   passes all overdue invoices, all draft invoices, recent paid invoices (last 5)

2. app/Http/Controllers/ClientController.php
   index(): list all clients with invoice counts and unbilled hours
   show(Client $client): client detail with all invoices and work logs

3. app/Http/Controllers/InvoiceController.php
   index(): all invoices with filters (status GET param)
   show(Invoice $invoice): invoice detail with line items
   No create/store/edit/update — Claude handles creation via MCP

4. app/Http/Controllers/WorkLogController.php
   index(): all work logs grouped by client, unbilled first

ROUTES in routes/web.php:
   GET /                        -> DashboardController@index (name: dashboard)
   GET /clients                 -> ClientController@index (name: clients.index)
   GET /clients/{client}        -> ClientController@show (name: clients.show)
   GET /invoices                -> InvoiceController@index (name: invoices.index)
   GET /invoices/{invoice}      -> InvoiceController@show (name: invoices.show)
   GET /work-logs               -> WorkLogController@index (name: worklogs.index)

Remove auth middleware from all routes — this is a public demo app.

Commit:
  git add .
  git commit -m "Task 08: Dashboard, Client, Invoice, WorkLog controllers and routes"
```

---

## TASK 09 — Blade Views (UI)

**Cursor Prompt:**
```
Create the Blade frontend. Use Tailwind CSS and Alpine.js only. No custom CSS files.
Design: clean, professional, dark sidebar layout. Sidebar links to Dashboard, Clients,
Invoices, Work Logs. Top bar shows "Invoice Brain" logo + "MCP Connected" badge in green.

Create these views:

1. resources/views/layouts/app.blade.php
   Dark sidebar (#1a1a2e), white content area. Active link highlighting.
   Flash message support (success/error). @money directive available.

2. resources/views/dashboard.blade.php
   4 stat cards: Total Invoiced (YTD), Collected, Outstanding, Overdue count
   Section: "Needs Attention" — overdue invoices list with client + amount + days overdue
   Section: "Draft Invoices" — unsent drafts with client + amount
   Section: "Recently Paid" — last 5 paid invoices

3. resources/views/clients/index.blade.php
   Table: name, email, default rate, open invoices count, unbilled hours, actions

4. resources/views/clients/show.blade.php
   Client header with stats. Tabs (Alpine): Invoices | Work Logs | Notes
   Invoice tab: table of all invoices with status badge
   Work log tab: table grouped by billed/unbilled with hours + amount

5. resources/views/invoices/index.blade.php
   Filter tabs: All | Draft | Outstanding | Overdue | Paid
   Table: invoice number, client, total, status badge, issued date, due date

6. resources/views/invoices/show.blade.php
   Invoice header: number, client, status badge, dates
   Line items table with totals
   Total row formatted with @money

7. resources/views/invoices/pdf.blade.php
   Clean PDF-optimised invoice template (no sidebar, no nav)
   Logo text "Invoice Brain" top left, invoice number top right
   Client address block, line items table, total, payment terms note

8. resources/views/worklogs/index.blade.php
   Grouped by client. Unbilled entries highlighted. Hours + rate + amount columns.
   "Unbilled Total" summary per client.

Status badges: Draft=gray, Sent=blue, Paid=green, Overdue=red, Cancelled=gray/strikethrough

Commit:
  git add .
  git commit -m "Task 09: Blade views — dashboard, clients, invoices, work logs, PDF template"
```

---

## TASK 10 — MCP Resources

**Cursor Prompt:**
```
Create MCP Resource classes in app/Mcp/Resources/ using the Laravel 12
first-party laravel/mcp package. Each class extends Laravel\Mcp\Server\Resource.
After creating each class, add it to the $resources array in InvoiceBrainServer.

Resource class structure:
  use Laravel\Mcp\Request;
  use Laravel\Mcp\Response;
  use Laravel\Mcp\Server\Resource;

  class ClientListResource extends Resource
  {
      protected string $uri = 'clients://list';
      protected string $description = 'All clients with invoice and billing stats';

      public function handle(Request $request): string
      {
          // ... build data array ...
          return json_encode([
              'data' => $data,
              'summary' => 'X clients. Y have overdue invoices.',
          ]);
      }
  }

Create the following resources:

1. app/Mcp/Resources/ClientListResource.php
   uri: clients://list
   Returns: array of all clients with fields:
     id, name, email, default_rate_formatted, payment_terms,
     open_invoices_count, overdue_invoices_count, unbilled_hours, notes
   summary: "X clients. Y have overdue invoices. Z total unbilled hours."

2. app/Mcp/Resources/ClientDetailResource.php
   uri: clients://{id}  (use $request->string('id') to get the param)
   Returns: single client + all invoices (id, number, status, total_cents, due_at)
     + all work logs (id, description, hours, status, worked_at)
   summary: "Acme Corp. 3 invoices ($4,200 outstanding). 8.5 unbilled hours."
   Return Response::error('Client not found.') if ID does not exist.

3. app/Mcp/Resources/InvoiceListResource.php
   uri: invoices://list
   Returns: all invoices with client name, total_cents, status, issued_at, due_at
   summary: "15 invoices total. 3 draft, 4 outstanding ($8,400), 2 overdue ($3,200), 6 paid."

4. app/Mcp/Resources/InvoiceDetailResource.php
   uri: invoices://{id}
   Returns: invoice with client, all line items, total_cents, is_overdue
   summary: "Invoice INV-2025-0003 for Acme Corp. $2,400. Overdue by 12 days."

5. app/Mcp/Resources/InvoiceOutstandingResource.php
   uri: invoices://outstanding
   Returns: invoices where status=sent or overdue, sorted by due_at asc
   summary: "4 outstanding invoices totalling $9,600. Oldest is 47 days overdue."

6. app/Mcp/Resources/InvoiceOverdueResource.php
   uri: invoices://overdue
   Returns: overdue invoices with days_overdue calculated field
   summary: "2 overdue invoices. $3,200 total. Oldest: Acme Corp, 47 days."

7. app/Mcp/Resources/InvoiceDraftResource.php
   uri: invoices://draft
   Returns: draft invoices with client name and total
   summary: "1 draft invoice for Nova Health worth $2,400. Not yet sent."

8. app/Mcp/Resources/WorkLogUnbilledResource.php
   uri: worklogs://unbilled
   Returns: all unbilled work logs grouped by client_id, with hours + rate + total_cents
   summary: "8.5 unbilled hours across 2 clients. $1,087.50 total value."

9. app/Mcp/Resources/WorkLogUnbilledClientResource.php
   uri: worklogs://unbilled/{client_id}
   Returns: unbilled work logs for one client
   summary: "Bright Studio: 9.5 unbilled hours. $902.50 at $95/hr."

10. app/Mcp/Resources/ReportSummaryResource.php
    uri: reports://summary
    Returns: {
      invoiced_this_month_cents, invoiced_ytd_cents,
      collected_this_month_cents, collected_ytd_cents,
      outstanding_cents, overdue_cents,
      client_count, invoice_count_ytd
    }
    summary: "YTD: $24,000 invoiced, $18,400 collected. $5,600 outstanding ($2,400 overdue)."

After creating all 10 classes, add them all to the $resources array in InvoiceBrainServer.php.

Commit:
  git add .
  git commit -m "Task 10: MCP resources — clients, invoices, work logs, reports (10 total)"
```

---

## TASK 11 — MCP Tools (Read + Create)

**Cursor Prompt:**
```
Create the first set of MCP Tool classes in app/Mcp/Tools/ using the Laravel 12
first-party laravel/mcp package. Each class extends Laravel\Mcp\Server\Tool.

Tool class structure:
  use Illuminate\JsonSchema\JsonSchema;
  use Laravel\Mcp\Request;
  use Laravel\Mcp\Response;
  use Laravel\Mcp\Server\Tool;

  class CreateClientTool extends Tool
  {
      protected string $name = 'create_client';
      protected string $description = 'Creates a new client...';

      public function schema(JsonSchema $schema): array
      {
          return [
              'name' => $schema->string()->description('Client company name')->required(),
              'email' => $schema->string()->description('Billing email address')->required(),
              'address' => $schema->string()->description('Postal address')->nullable(),
              'default_rate' => $schema->integer()->description('Hourly rate in cents')->nullable(),
          ];
      }

      public function handle(Request $request): Response
      {
          $validated = $request->validate([
              'name' => 'required|string',
              'email' => 'required|email|unique:clients,email',
          ]);

          // ... logic ...

          return Response::text("Client '{$client->name}' created with ID {$client->id}.");
      }
  }

Create the following 6 tools:

1. app/Mcp/Tools/CreateClientTool.php
   name: create_client
   Description: "Creates a new client. Use when the user mentions a new client
     that doesn't exist yet. Check clients://list first to avoid duplicates."
   Schema: name (required), email (required), address?, default_rate?,
           payment_terms? (default 30), notes?
   Validates: email is valid format, email is unique in clients table
   Returns Response::text("Client '{name}' created with ID {id}.")

2. app/Mcp/Tools/UpdateClientNotesTool.php
   name: update_client_notes
   Description: "Updates the notes field for a client. Use when the user shares
     context about a client that should be remembered (payment behaviour,
     preferences, special instructions). Appends to existing notes with a timestamp."
   Schema: client_id (required, integer), notes (required, string)
   Behaviour: APPENDS "[{date}] {new notes}\n{old notes}"
   Returns Response::text with the updated notes content.

3. app/Mcp/Tools/LogWorkTool.php
   name: log_work
   Description: "Logs a single work entry for a client (unbilled by default).
     Use when the user describes work done for a client. Rate defaults to
     the client's default_rate if not specified."
   Schema: client_id (required), description (required), hours (required, number),
           worked_at? (string, ISO date, default today), rate? (integer, cents)
   Validates: client exists, hours > 0, rate > 0 or client has default_rate
   Returns Response::text with created entry and computed total.

4. app/Mcp/Tools/BulkLogWorkTool.php
   name: bulk_log_work
   Description: "Logs multiple work entries in one call. Use when the user
     provides a list or summary of work across multiple days or tasks.
     More efficient than calling log_work repeatedly."
   Schema: entries (required, array) — each item: client_id, description,
           hours, worked_at?, rate?
   Returns Response::text summary: "Logged X entries totalling Y hours."

5. app/Mcp/Tools/CreateInvoiceTool.php
   name: create_invoice
   Description: "Creates a new invoice with explicit line items. Use for ad-hoc
     invoices where the user specifies items directly. For invoicing existing
     unbilled work logs, use create_invoice_from_worklogs instead."
   Schema: client_id (required), line_items (required, array of
           {description, quantity, unit_price}), issued_at?, notes?
   Auto-calculates due_at from client.payment_terms.
   Uses InvoiceNumberService to generate invoice_number.
   Returns Response::text with invoice number, total formatted, and due date.

6. app/Mcp/Tools/CreateInvoiceFromWorklogsTool.php
   name: create_invoice_from_worklogs
   Description: "Creates an invoice from existing unbilled work log entries.
     Use when the user wants to invoice a client for work already logged.
     Marks the work logs as billed. Check worklogs://unbilled/{client_id} first."
   Schema: client_id (required), worklog_ids (required, array of integers), notes?
   Validates: all work log IDs exist, belong to client, and are unbilled
   Creates invoice with line items matching work logs. Marks logs as billed.
   Returns Response::text with invoice number, total, and count of work logs billed.

After creating all 6, add them to the $tools array in InvoiceBrainServer.php.

Commit:
  git add .
  git commit -m "Task 11: MCP tools — create_client, log_work, bulk_log_work, create_invoice, create_invoice_from_worklogs"
```

---

## TASK 12 — MCP Tools (Send + Update)

**Cursor Prompt:**
```
Create the remaining MCP Tool classes in app/Mcp/Tools/ following the same
structure as Task 11 (extend Laravel\Mcp\Server\Tool, use schema() + handle()).

1. app/Mcp/Tools/SendInvoiceTool.php
   name: send_invoice
   Description: "Sends an invoice to the client via email with PDF attachment.
     Only works on draft invoices. Updates status to 'sent'. Provide an optional
     custom message to personalise the email."
   Schema: invoice_id (required, integer), message? (string)
   Validates: invoice exists, status is 'draft'
   Uses InvoicePdfService + InvoiceMail. Updates status to 'sent'.
   Returns Response::text with invoice_number, client email, and total.

2. app/Mcp/Tools/MarkInvoicePaidTool.php
   name: mark_invoice_paid
   Description: "Records an invoice as paid. Use when the user confirms payment
     was received. Updates status to 'paid' and records the payment date."
   Schema: invoice_id (required, integer), paid_at? (string, ISO date)
   Validates: invoice exists, status is 'sent' or 'overdue'
   Returns Response::text with invoice_number and amount collected.

3. app/Mcp/Tools/AddLineItemTool.php
   name: add_line_item
   Description: "Adds a line item to an existing draft invoice. Use when the
     user wants to add an item to an invoice that hasn't been sent yet."
   Schema: invoice_id (required), description (required), quantity (required, number),
           unit_price (required, integer, cents)
   Validates: invoice exists and status is 'draft'
   Returns Response::text with updated invoice total.

4. app/Mcp/Tools/SendPaymentReminderTool.php
   name: send_payment_reminder
   Description: "Sends a payment reminder email for an overdue or outstanding
     invoice. Use when the user wants to chase a specific invoice. Appends
     a reminder note to the invoice notes with timestamp."
   Schema: invoice_id (required, integer), message? (string)
   Validates: invoice exists, status is 'sent' or 'overdue'
   Sends PaymentReminderMail. Appends "[REMINDER SENT {date}]" to invoice notes.
   Returns Response::text confirmation.

5. app/Mcp/Tools/BulkSendRemindersTool.php
   name: bulk_send_reminders
   Description: "Sends payment reminders to all overdue invoices matching the
     threshold. Use when the user wants to chase all late payments at once.
     Skips invoices that received a reminder in the last 24 hours."
   Schema: days_overdue_min? (integer, default 1), message? (string)
   Returns Response::text: "Sent X reminders. Skipped Y (recently reminded)."

6. app/Mcp/Tools/CancelInvoiceTool.php
   name: cancel_invoice
   Description: "Cancels an invoice and records the reason. Also unbills any
     work logs attached to this invoice so they can be re-invoiced later."
   Schema: invoice_id (required, integer), reason? (string)
   Validates: invoice is not already 'paid'
   Unbills attached work logs (status='unbilled', invoice_id=null)
   Returns Response::text confirmation.

7. app/Mcp/Tools/GetRevenueReportTool.php
   name: get_revenue_report
   Description: "Returns revenue statistics for a given period. Use when the
     user asks about earnings, income, or financial performance."
   Schema: period (required, string enum: this_month|last_month|this_year|last_year),
           start_date? (string), end_date? (string)
   Returns Response::text with JSON: invoiced, collected, outstanding,
   invoice_count, paid_count, top_client.

8. app/Mcp/Tools/GetClientReportTool.php
   name: get_client_report
   Description: "Returns a full performance report for a single client:
     lifetime revenue, average invoice size, average days to pay, outstanding
     balance, and unbilled work. Use to evaluate client relationship health."
   Schema: client_id (required, integer)
   Validates: client exists
   Returns Response::text with JSON: { lifetime_revenue_cents, avg_invoice_cents,
   avg_days_to_pay, outstanding_cents, unbilled_hours, unbilled_value_cents,
   invoice_count, summary_string }

After creating all 8, add them to the $tools array in InvoiceBrainServer.php.

Commit:
  git add .
  git commit -m "Task 12: MCP tools — send_invoice, mark_paid, reminders, cancel, reports (8 total)"
```

---

## TASK 13 — MCP Inspector Verification

**Cursor Prompt:**
```
Use the built-in Laravel MCP Inspector to verify all resources and tools are
correctly registered and returning data.

1. Run: php artisan mcp:inspector invoice-brain
   This launches the inspector against your locally-registered server.
   No separate terminal or npx command needed — it's built into Laravel 12.

Verify the following resources return data without errors:
- clients://list
- invoices://list
- invoices://overdue
- invoices://draft
- worklogs://unbilled
- reports://summary

Verify the following tools execute correctly via the inspector UI:
- log_work (log 2hrs for client_id=4 "Test entry")
- create_invoice_from_worklogs (for Bright Studio using their unbilled work log IDs)
- send_invoice (for Nova Health's draft invoice)
- send_payment_reminder (for one of Acme Corp's overdue invoices)

Fix any validation errors, missing relationships, or JSON serialisation issues found.
Ensure every resource response includes a non-empty 'summary' key.
Ensure all 14 tools and 10 resources are listed.

Commit:
  git add .
  git commit -m "Task 13: MCP inspector verified — 14 tools and 10 resources passing"
```

---

## TASK 14 — Claude Desktop Integration & README

**Cursor Prompt:**
```
Create README.md at the project root with the following sections:

1. **What is this?** — 2 paragraph explanation of Invoice Brain and why MCP matters

2. **Prerequisites** — PHP 8.3+, Composer, Claude Desktop

3. **Installation** — step by step:
   git clone, composer install, cp .env.example .env,
   php artisan key:generate, php artisan migrate --seed

4. **Claude Desktop Setup** — the laravel/mcp local server uses stdio transport.
   Exact JSON to add to claude_desktop_config.json:
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
   Note: user must replace the cwd path. The server name "invoice-brain" must match
   the Mcp::local() name registered in routes/ai.php.

5. **Demo Prompts** — the 6 demo prompts from the spec, numbered and ready to copy-paste

6. **MCP Resources Reference** — table of all resource URIs with descriptions

7. **MCP Tools Reference** — table of all tool names with what they do

8. **Web UI** — note that `php artisan serve` shows the dashboard at localhost:8000

Also create .env.example from the current .env (remove APP_KEY value).

Commit:
  git add .
  git commit -m "Task 14: README with Claude Desktop setup, demo prompts, and MCP reference tables"
```

---

## TASK 15 — Final Polish & Smoke Test

**Cursor Prompt:**
```
Final polish pass:

1. Ensure the dashboard stats (total invoiced, collected, outstanding, overdue)
   are computed correctly using the Invoice model scopes and MoneyService

2. Add a Blade component resources/views/components/status-badge.blade.php
   that accepts a status string and renders a colored pill badge
   (Draft=gray, Sent=blue, Paid=green, Overdue=red, Cancelled=gray)
   Use this component in all views that show invoice status

3. Add an Artisan command app/Console/Commands/MarkOverdueInvoices.php
   Command signature: invoices:mark-overdue
   Finds all invoices where status='sent' and due_at < today
   Updates them to status='overdue'
   Outputs count of invoices updated
   Register in Console/Kernel.php to run daily

4. Run php artisan db:seed --class=DemoDataSeeder to reset to clean demo state

5. Do a full smoke test:
   - Visit http://localhost:8000 — dashboard loads with stats
   - Visit /clients — 4 clients visible
   - Visit /invoices — invoices with correct status badges
   - MCP Inspector: all resources return data
   - Claude Desktop: connect and run prompt:
     "List all clients and tell me who has the most urgent unpaid invoices"

Fix any issues found. Ensure no dd(), var_dump(), or debug output remains.

Commit:
  git add .
  git commit -m "Task 15: status badge component, mark-overdue command, final polish"
```

---

## TASK 16 — HTTP/SSE Transport + Sanctum Auth

**Cursor Prompt:**
```
Switch the MCP server from local stdio transport to web HTTP/SSE transport so it
can be accessed remotely (Railway deployment). Add Sanctum token authentication
so the endpoint is not publicly open.

STEP 1 — Install Sanctum:
  php artisan install:api
This installs laravel/sanctum, runs its migrations (personal_access_tokens table),
and creates routes/api.php. Confirm migrations ran successfully.

STEP 2 — Add HasApiTokens to the User model:
  use Laravel\Sanctum\HasApiTokens;
  class User extends Authenticatable {
      use HasApiTokens, HasFactory, Notifiable;
  }

STEP 3 — Update routes/ai.php to use web transport with Sanctum middleware:
  <?php
  use App\Mcp\Servers\InvoiceBrainServer;
  use Laravel\Mcp\Facades\Mcp;

  // Keep local for development (stdio, Claude Desktop on same machine)
  Mcp::local('invoice-brain', InvoiceBrainServer::class);

  // Web transport for production (HTTP/SSE, Railway)
  // Uncomment this and comment the local() line when deploying:
  // Mcp::web('/mcp', InvoiceBrainServer::class)
  //     ->middleware('auth:sanctum');

Note: Keep BOTH registrations in the file as comments — local() active for dev,
web() commented out. The Railway task will flip them.

STEP 4 — Exclude the MCP route from CSRF verification.
In bootstrap/app.php, add to the withMiddleware closure:
  $middleware->validateCsrfTokens(except: ['mcp', 'mcp/*']);

STEP 5 — Create an Artisan command to generate a demo MCP token:
  php artisan make:command GenerateMcpToken
  Signature: mcp:token
  Description: "Generate a Sanctum token for MCP access"

  In handle():
  - Find or create a User with name="Demo User", email="demo@invoice-brain.local"
    and a random hashed password (this user is never logged into via the web UI)
  - Delete all existing tokens for this user (clean slate)
  - Create a new token: $user->createToken('mcp-demo')->plainTextToken
  - Output the token to the console in a clear format:
    "Your MCP token: {token}"
    "Add this as a Bearer token in your MCP client config."

STEP 6 — Test locally before Railway:
  a. Temporarily flip routes/ai.php to use Mcp::web() with auth:sanctum
  b. Run: php artisan mcp:token — copy the token
  c. Run: php artisan serve
  d. In Claude Desktop config, temporarily use:
     {
       "mcpServers": {
         "invoice-brain-web": {
           "url": "http://localhost:8000/mcp",
           "headers": { "Authorization": "Bearer {your-token-here}" }
         }
       }
     }
  e. Confirm Claude Desktop connects and all tools/resources work over HTTP
  f. Flip routes/ai.php back to Mcp::local() for continued local dev

Commit:
  git add .
  git commit -m "Task 16: HTTP/SSE transport, Sanctum auth, mcp:token Artisan command"
```

---

## TASK 17 — Railway Deployment

**Cursor Prompt:**
```
Configure the project for deployment on Railway. This task assumes you have a
Railway account and the Railway CLI installed (npm install -g @railway/cli).

STEP 1 — Switch to PostgreSQL.
Update config/database.php default connection to 'pgsql' when DATABASE_URL is set:
  'default' => env('DB_CONNECTION', 'sqlite'),
Railway injects a DATABASE_URL env var — add this to config/database.php
in the pgsql connection block:
  'url' => env('DATABASE_URL'),
  'host' => env('DB_HOST', '127.0.0.1'),
  'port' => env('DB_PORT', '5432'),
  'database' => env('DB_DATABASE', 'forge'),
  'username' => env('DB_USERNAME', 'forge'),
  'password' => env('DB_PASSWORD', ''),
  'sslmode' => env('DB_SSLMODE', 'require'),  // Railway requires SSL

Also add the pgsql PHP extension check — ensure config/database.php doesn't break
if the pgsql driver is missing locally (SQLite still works for local dev via .env).

STEP 2 — Create a Procfile in the project root:
  web: php artisan serve --host=0.0.0.0 --port=$PORT

Railway injects $PORT automatically. The Procfile tells Railway to run this as
the web process.

STEP 3 — Create a railway.json in the project root:
  {
    "$schema": "https://railway.com/railway.schema.json",
    "build": {
      "builder": "NIXPACKS"
    },
    "deploy": {
      "releaseCommand": "php artisan migrate --force && php artisan db:seed --class=DemoDataSeeder --force && php artisan mcp:token",
      "restartPolicyType": "ON_FAILURE",
      "restartPolicyMaxRetries": 3
    }
  }

The releaseCommand runs after each deploy: migrates, seeds fresh demo data,
and prints the MCP token to the Railway deploy logs so you can copy it.

STEP 4 — Update routes/ai.php for production:
Flip to use Mcp::web() and keep Mcp::local() commented out:
  <?php
  use App\Mcp\Servers\InvoiceBrainServer;
  use Laravel\Mcp\Facades\Mcp;

  // Local development (stdio):
  // Mcp::local('invoice-brain', InvoiceBrainServer::class);

  // Production (HTTP/SSE via Railway):
  Mcp::web('/mcp', InvoiceBrainServer::class)
      ->middleware('auth:sanctum');

STEP 5 — Update .env.example with the new Railway-relevant variables:
  APP_ENV=production
  APP_URL=https://your-app.up.railway.app
  DB_CONNECTION=pgsql
  DATABASE_URL=          # injected by Railway, leave blank in example
  MAIL_MAILER=log        # keep log for demo — no real email needed on Railway

STEP 6 — Deploy:
  railway login
  railway init           # link to your Railway project
  railway up             # deploy

After deploy:
  a. Open Railway dashboard → your service → Deploy logs
  b. Find the "Your MCP token: ..." line printed by mcp:token in the release command
  c. Copy the token

STEP 7 — Connect Claude Desktop to the live Railway app:
Update claude_desktop_config.json:
  {
    "mcpServers": {
      "invoice-brain": {
        "url": "https://YOUR-APP.up.railway.app/mcp",
        "headers": {
          "Authorization": "Bearer YOUR-TOKEN-FROM-DEPLOY-LOGS"
        }
      }
    }
  }
Replace YOUR-APP and YOUR-TOKEN with the real values.
Restart Claude Desktop. Confirm the server connects (green dot).

STEP 8 — Update README.md with a new "Railway Deployment" section:
  - Prerequisites: Railway CLI, Railway account
  - Steps 1-7 above condensed
  - Note that the MCP token appears in deploy logs after each `railway up`
  - Note that demo data is re-seeded on every deploy (fresh state for demos)
  - Local dev note: flip routes/ai.php back to Mcp::local() for local use,
    use Mcp::web() for Railway. This is a one-line change.

STEP 9 — Smoke test the live deployment:
  a. Visit https://YOUR-APP.up.railway.app — dashboard loads
  b. In Claude Desktop (connected to Railway URL): run demo prompt 1
     "Give me a full picture of where things stand right now"
  c. Confirm Claude reads the seeded data and responds correctly
  d. Run demo prompt 4 (log + invoice + send) and confirm changes persist
     by refreshing the web dashboard

Commit and push:
  git add .
  git commit -m "Task 17: Railway deployment — Postgres, Procfile, railway.json, HTTP/SSE transport live"
  git push origin main
```
