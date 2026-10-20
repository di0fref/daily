# Daily

A todo app built with Laravel 13, MySQL/MariaDB, Tailwind CSS 4 and Alpine.js.
No authentication — it's a single-user personal board.

## Features

- **Projects** with colour, description, archiving and a live progress bar
- **Tasks** and **subtasks** (one level deep, cascading complete/delete)
- **Quick add** — one field parses `@Project`, `!priority`, `#tags` and natural dates,
  with a live preview and a *More…* button that opens the full form prefilled
- **Priority** (low / normal / high / urgent) and a **flag** for "important today"
- **Due dates** with overdue / today / tomorrow badges
- **Kanban board** — Backlog → Today → In progress → Done, drag and drop (SortableJS),
  persisted via `POST /tasks/{task}/move` including column ordering
- **Daily focus view** — overdue, due today, picked for today, flagged, plus a
  "Done today" section and a progress bar for the day
- **Done section** on every project page and as the board's last column
- **Archive** — hide cards from the board, focus view and progress bars without deleting
  them; restore from the dedicated `/archive` page

## Quick add syntax

Every add field (tasks and subtasks) accepts free text that is parsed server side:

| Token | Example | Result |
| --- | --- | --- |
| `@project` | `@Website`, `@"Client work"`, `@web` | Picks the project (exact, multi-word or prefix match) |
| `!priority` | `!low !normal !high !urgent`, `!h`, `!!` | Sets the priority (`!!` = urgent) |
| `#tag` | `#flag`, `#today`, `#doing`, `#done`, `#backlog` | Flags the task or sets its column |
| date | `today`, `tomorrow`, `friday`, `next week`, `in 3 days`, `20/10`, `2026-10-20`, `5 dec` | Sets the due date |

```
Call Anna tomorrow !high @Website #flag
```

- The first line becomes the **title**, everything after it becomes the **notes**
  (<kbd>Shift</kbd>+<kbd>Enter</kbd> for a new line, <kbd>Enter</kbd> to save).
- Recognised tokens are stripped from the title; anything unrecognised is left alone,
  so `Email support@example.com` stays intact.
- A chip row previews exactly what will be saved, and *More…* opens the full task
  modal with the parsed values prefilled for anything the syntax doesn't cover.
- Parsed values win over the form's context (adding from the focus view defaults to
  *Today*, but `#backlog` overrides it).

## Behaviour notes

- Completing a parent task completes all of its subtasks.
- A parent is auto-completed when every subtask is done, and reopened (to *In progress*)
  when a subtask is reopened.
- Archiving a task archives its subtasks; restoring a subtask also restores its parent so
  it can never be orphaned.
- Archived tasks are excluded from every project progress bar and task count.
- *Archive all* bulk-archives completed **root** tasks (and their subtasks), so a done
  subtask under an open parent is never swept away.
- Moving a root task to another project moves its subtasks with it.
- Deleting a project deletes its tasks; deleting a task deletes its subtasks.
- In the focus view a subtask is hidden when its parent is already listed, since it is
  shown inside the parent's card.

## Requirements

- PHP 8.2+
- MySQL 8 / MariaDB 10.4+
- Node 20+

## Setup

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
```

Create the database and a user:

```sql
CREATE DATABASE daily_todo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'daily'@'127.0.0.1' IDENTIFIED BY 'daily';
GRANT ALL PRIVILEGES ON daily_todo.* TO 'daily'@'127.0.0.1';
```

Point `.env` at it:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=daily_todo
DB_USERNAME=daily
DB_PASSWORD=daily
SESSION_DRIVER=file
```

Then:

```bash
php artisan migrate
php artisan db:seed                                 # generic demo data
php artisan db:seed --class=WorkBacklogSeeder       # the real work backlog
npm run build            # or: npm run dev
php artisan serve
```

> Both seeders truncate `projects` and `tasks` before inserting, so run only one.

Open http://localhost:8000.

## Routes

| Method | URI | Name | Purpose |
| --- | --- | --- | --- |
| GET | `/` | `focus` | Daily focus view |
| GET | `/board` | `board` | Kanban board (`?project=<id>` to filter) |
| GET | `/archive` | `archive` | Archived tasks (`?project=<id>` to filter) |
| GET | `/projects` | `projects.index` | Project list with progress bars |
| POST | `/projects` | `projects.store` | Create a project |
| GET | `/projects/{project}` | `projects.show` | Project detail + done section |
| PUT | `/projects/{project}` | `projects.update` | Update / archive |
| DELETE | `/projects/{project}` | `projects.destroy` | Delete (cascades to tasks) |
| POST | `/tasks` | `tasks.store` | Create a task or subtask (`parent_id`), accepts `raw` quick-add text |
| POST | `/tasks/parse` | `tasks.parse` | Preview what quick-add text resolves to (JSON) |
| PUT | `/tasks/{task}` | `tasks.update` | Update a task |
| DELETE | `/tasks/{task}` | `tasks.destroy` | Delete (cascades to subtasks) |
| POST | `/tasks/{task}/toggle` | `tasks.toggle` | Toggle done |
| POST | `/tasks/{task}/move` | `tasks.move` | Kanban move + reorder (JSON) |
| POST | `/tasks/{task}/archive` | `tasks.archive` | Archive a task + its subtasks |
| POST | `/tasks/{task}/unarchive` | `tasks.unarchive` | Restore from the archive |
| POST | `/tasks/archive-done` | `tasks.archive-done` | Bulk-archive completed tasks |

## Schema

`projects` — `name`, `description`, `color`, `position`, `archived`

`tasks` — `project_id`, `parent_id` (self-referencing, nullable), `title`, `notes`,
`status` (`backlog|today|doing|done`), `priority` (`low|normal|high|urgent`),
`flagged`, `due_date`, `position`, `completed_at`, `archived_at` (null = active)
