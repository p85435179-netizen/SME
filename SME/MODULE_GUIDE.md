# SME Financial Tracker — Module Guide

## 1. System Overview

**Problem:** Small businesses struggle to organize income and expenses and understand their financial position.

**Solution:** A web application that records income and expenses, summarizes financial activity, and generates basic reports.

**Scope:** Basic financial tracking in a shared business workspace.

**Out of scope:** Full accounting, tax filing, bank integration, and multiple business workspaces.

## 2. Users and Permissions

| Action | Business Owner | Accountant | Admin |
|---|---|---|---|
| View dashboard and records | Yes | Yes | Yes |
| Create, edit, and delete financial records | Yes | No | Yes |
| Review pending records | No | Yes | Yes |
| Search, filter, sort, and export | Yes | Yes | Yes |
| View financial reports and activity log | Yes | Yes | Yes |
| View user accounts and create an account | No | No | Yes |

Accounts share the same financial workspace. Owners can manage workspace records; records are not restricted to their original creator.

## 3. Authentication Module

**Purpose:** Allow authorized users to access the system.

**How to use:**

1. Open `http://localhost/SME/` with Apache and MySQL running.
2. Enter your email address and password.
3. Select **Sign in**.
4. Select **Sign out** in the top bar when finished.

Protected workspace data requires a valid session. Invalid credentials display an error. Sessions expire after eight hours. Role restrictions are enforced by the API as well as the interface.

## 4. Overview Module

**Purpose:** Display the current financial summary.

| Dashboard item | Meaning |
|---|---|
| Net balance | Total recorded income minus total recorded expenses |
| Total income | Sum of income entries |
| Total expenses | Sum of expense entries |
| Awaiting review | Number of pending entries |
| Cash flow overview | Income and expenses over the last six calendar months |
| Expense breakdown | Expenses grouped by category |
| Recent transactions | Five most recent records |

Dashboard totals include both pending and reviewed records across all recorded dates. Charts use the periods described above. Values come from database records. Net balance represents recorded activity, not a verified bank balance.

Select **Add record** to create an entry, or **View all records** to open the records table. The Add record action is available to Owners and Admins.

## 5. Financial Records Module

**Purpose:** Create, view, update, and delete income and expense entries.

### Create a record

1. Select **Add record**.
2. Select **Income** or **Expense**.
3. Enter the date, description, and amount in Philippine pesos.
4. Choose a category matching the record type.
5. Select **Save record**.

The saved entry appears in the table with **Pending review** status.

### View and update records

Open **All records** to view descriptions, categories, dates, amounts, status, and available actions. Select **Edit**, change the required fields, and save. Updating a reviewed record returns it to pending review and clears its previous reviewer.

### Delete a record

Select **Delete**, check the description in the confirmation dialog, and select **Delete record** to confirm. Select **Keep record** to cancel. Deletion permanently removes the entry from financial totals and creates an activity-log entry.

### Validation rules

| Field | Rule |
|---|---|
| Type | Income or expense |
| Description | 2–160 characters after trimming |
| Amount | 0.01–999,999,999.00 PHP; maximum two decimal places |
| Date | Valid calendar date between years 1000 and 9999 |
| Category | Existing category matching the selected type |

Browser constraints and JavaScript provide immediate feedback. The server independently validates data before database operations.

## 6. Income and Expenses Modules

**Income:** Displays only income records and their summary. Use it to track sales, service payments, and other earnings.

**Expenses:** Displays only expense records and their summary. Use it to track costs such as rent, supplies, utilities, and marketing.

Both pages support the same record actions, filters, sorting, pagination, and CSV export as All records. Available actions depend on the user's role.

## 7. Search, Filters, and Pagination

| Control | Function |
|---|---|
| Search | Matches keywords in descriptions, categories, or creator names |
| Review status | Shows all, pending, or reviewed records |
| From date | Includes entries on or after the selected date |
| To date | Includes entries on or before the selected date |
| Sort by | Orders records by newest, oldest, or highest amount |
| Reset | Clears filters and restores newest-first ordering |
| Previous / Next | Navigates table pages with up to eight records each |

Filters apply immediately. Blank dates impose no date limit. Changing filters resets pagination to the first page. Opening another module clears the previous module's filters.

## 8. Financial Reports Module

**Purpose:** Summarize financial results for a selected set of records.

1. Open **Financial reports**.
2. Set dates, search keywords, or review status as needed.
3. Review the summary cards, cash flow chart, and expense breakdown.
4. Check the financial summary for income, expenses, net result, net margin, and record count.
5. Select **Export report** to download a CSV.

**Net result:** Income − expenses.

**Net margin:** Net result ÷ income × 100. When income is zero, the system displays a dash.

Reports include pending and reviewed entries unless filtered by status. Summary totals follow the active filters; the cash flow chart shows only the last six calendar months within those results. CSV reports contain matching records and income, expense, net-result, and date-range summary rows. They do not provide formal financial statements or tax calculations.

## 9. Record Review Module

**Purpose:** Allow Accountants and Admins to review pending entries.

1. Open **Record review**.
2. Inspect the pending record's details.
3. Select **Review** to mark it reviewed.

The record leaves the pending queue and appears as reviewed elsewhere. Owners can view the queue but cannot mark records reviewed. Accountants cannot create, edit, or delete financial records. Review is a status check; pending records already contribute to financial totals.

## 10. User Accounts Module

**Access:** Admin only.

1. Open **User accounts**.
2. Select **Create account**.
3. Enter a full name, email address, role, and password.
4. Select **Create account** to save.

Names must contain 2–80 characters, emails must be valid and unique, and passwords must satisfy the 10-character minimum and the server's 72-byte maximum. Passwords are stored as bcrypt hashes. This module lists account status but does not provide account editing, deactivation, or password reset.

## 11. Activity Log Module

**Purpose:** Show who performed key changes.

Open **Activity log** to view the latest 30 actions, their team members, and timestamps in UTC. Logged actions include financial-record creation, updates, deletion, review, and account creation. It is not a complete login-history log.

## 12. CSV Export Module

Select **Export CSV** from a records page to download the records matching the current filters and order. Export includes all matching records, not just the visible table page.

Record exports contain date, type, description, category, amount in PHP, status, and creator. Open the downloaded file in a spreadsheet application. Report exports also contain summary rows.

## 13. Typical Workflow

1. The Owner signs in and records business income or an expense.
2. The system validates and saves the entry as pending review.
3. The Accountant reviews the entry.
4. The Owner checks the dashboard and generates a report.
5. If the entry needs correction, the Owner edits it, and the Accountant reviews it again.
6. The Admin creates accounts as needed and monitors workspace activity.

## 14. Technical Module Map

| File or directory | Responsibility |
|---|---|
| `index.html` | Page structure, login form, navigation, and dialogs |
| `static/app.js` | Rendering, navigation, validation, filters, charts, and API requests |
| `static/workspace.css` | Current application styling and responsive layouts |
| `static/style.css` | Base tracker styles |
| `static/grc-app.css`, `static/grc-theme.css` | Legacy base styles overridden by application styles |
| `api.php` | Authentication, permissions, validation, SQL CRUD, review, and logging |
| `config.php` | Database and session-cookie configuration |
| `database/sme_financial_tracker.sql` | Database schema and seed data |
| `test_api.py` | Integration checks using a temporary financial record |

The database uses `users`, `categories`, `transactions`, and `activity`. SQL JOINs connect transaction categories and creators, and connect activity entries to users. Monetary amounts are stored as integer cents.

## 15. Criteria Coverage

| Mandatory requirement | Module or implementation |
|---|---|
| Login / logout and protected access | Authentication and authenticated API routes |
| At least three database-derived dashboard values | Overview: income, expenses, net balance, and pending count |
| Create, read, update, and delete | Financial Records with forms, table, editing, and delete confirmation |
| Search / filter | Search, review status, and date controls |
| Client-side validation | HTML constraints and JavaScript validation |
| Server-side validation | PHP validation before database writes |
| Database integration and SQL CRUD | PDO MySQL queries in the API |
| SQL JOIN | Records joined to categories and users; activity joined to users |
| Responsive design | Desktop sidebar and mobile navigation/table scrolling |

Implemented bonus features include user roles, pagination, sorting, CSV export, activity logs, and combined filtering. Image upload and password reset are outside the current implementation.
