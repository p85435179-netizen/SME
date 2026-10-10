# SME Financial Tracker Role Guide

This guide explains the modules and daily workflow for Business Owners, Accountants, and Admins. All roles work in the same business workspace. Financial totals include both pending and reviewed records.

## Business Owner

**Responsibility:** Maintain income and expense records and monitor business finances.

### Available modules

- Overview: View income, expenses, net balance, pending counts, charts, and recent transactions.
- All records: Create, view, edit, and delete workspace financial records.
- Income: View and manage income entries.
- Expenses: View and manage expense entries.
- Financial reports: Filter results, view summaries, and export reports.
- Record review: View pending records. Owners cannot mark records reviewed.
- Activity log: View recent changes and the team members who made them.

### Daily workflow

1. Sign in with your Owner account.
2. Open Overview to check your financial position.
3. Select Add record, choose Income or Expense, and enter the date, description, amount, and matching category.
4. Select Save record. The entry becomes pending review.
5. Use All records, Income, or Expenses to search and filter entries.
6. Select Edit to correct an entry. Saving an edit returns it to pending review.
7. To remove an incorrect entry, select Delete and confirm Delete record. Select Keep record to cancel.
8. Open Financial reports, select a period, review totals, and export if needed.
9. Sign out when finished.

**Restrictions:** Owners cannot review records or create user accounts. Deletion is permanent. Owners can manage workspace records regardless of who created them.

## Accountant

**Responsibility:** Review financial entries and examine reports.

### Available modules

- Overview: Monitor financial totals and the pending review count.
- All records: View, search, filter, sort, and export financial entries.
- Income and Expenses: Examine each type of financial activity.
- Financial reports: Review period results and export reports.
- Record review: Mark pending records reviewed.
- Activity log: Check recent financial changes and review actions.

### Review workflow

1. Sign in with your Accountant account.
2. Open Record review to see pending entries.
3. Check the description, category, date, amount, and creator.
4. If a correction is needed, ask an Owner or Admin to edit the entry outside the application. The system has no built-in correction-request or messaging feature.
5. Select Review when the record is ready. It leaves the pending queue and appears as reviewed in other pages.
6. Open Financial reports and select dates or review status to inspect results.
7. Use Export report or Export CSV to download matching records.
8. Sign out when finished.

**Restrictions:** Accountants cannot create, edit, or delete financial records or create accounts. Review changes the record status; it does not determine whether an entry contributes to totals. Edited records require another review.

## Admin

**Responsibility:** Manage workspace records, reviews, and user accounts.

### Available modules

- Overview: Monitor workspace financial activity.
- All records, Income, and Expenses: Create, view, edit, and delete records.
- Financial reports: Filter, inspect, and export financial results.
- Record review: Review pending entries.
- User accounts: View members and create accounts.
- Activity log: Monitor recent record, review, and account-creation actions.

### Administration workflow

1. Sign in with your Admin account.
2. Open User accounts to view names, email addresses, roles, and account status.
3. Select Create account and enter the full name, unique email, role, and password.
4. Choose Owner for record management, Accountant for review work, or Admin for full workspace management.
5. Select Create account to save. Names must contain 2–80 characters. Passwords require at least 10 characters and must satisfy the server's 72-byte maximum.
6. Manage financial entries through All records, Income, or Expenses as needed.
7. Review pending entries through Record review.
8. Check Activity log and Financial reports to monitor changes and results.
9. Sign out when finished.

**Limitations:** The system supports account creation and listing but has no account editing, deactivation, or password-reset interface. Editing a reviewed record returns it to pending status. Deleting an entry permanently removes it from totals.

## Shared Controls and Scope

Search matches descriptions, categories, and creator names. Status and date filters apply immediately. Reset clears filters. Tables display up to eight records per page. CSV export includes all matching records, not only the visible page. Activity log displays the latest 30 actions with UTC timestamps.

This system supports basic income and expense tracking. It does not provide full accounting, tax filing, bank integration, or separate workspaces for different businesses.
