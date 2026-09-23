# Secure Voting System

A lightweight, secure online voting app built with **PHP 8**, **MySQL**, and modern HTML/CSS, fully containerized with Docker.

## Features

- **Authentication**: register / login / logout with bcrypt password hashing, session regeneration, HTTP-only cookies
- **Security**: PDO prepared statements (SQL-injection safe), CSRF tokens on every form, input validation, output escaping
- **One vote per user** enforced at the database level (`UNIQUE(election_id, user_id)`)
- **Role-based access**: regular voters vs. admin panel
- **Live results** with per-candidate vote share bars (hidden until you vote or election closes)
- **Admin features**: create/close elections, add/delete candidates, view all users and vote counts

## Quick Start

Requires [Docker](https://docs.docker.com/engine/install/) with Compose v2.

```bash
cd voting_system
docker compose up --build
```

Then open **http://localhost:8080**

**Default admin account:**

| username | password   |
|----------|------------|
| `admin`  | `Admin@123`|

> Change these in `docker-compose.yml` (env vars `ADMIN_USER` / `ADMIN_PASS`) before deploying publicly.

## Usage

1. Log in as `admin`, open the **Admin** panel, and add candidates (a sample election with 3 candidates is seeded automatically).
2. Register voter accounts (or use the admin account). Voters land on the **Dashboard** where they can cast a vote.
3. Results are visible once a user has voted, or after the admin **closes** the election.
4. To reset everything: `docker compose down -v` (wipes the DB volume).

## Project Layout

```
voting_system/
├── docker-compose.yml     # services: web (PHP-Apache) + db (MySQL 8)
├── Dockerfile
├── docker-entrypoint.sh   # waits for DB, seeds admin, starts Apache
├── .env.example           # optional port / admin overrides
├── sql/
│   └── init.sql           # schema + sample election/candidates
├── config/
│   └── db.php             # PDO connection (env-driven)
├── app/
│   ├── auth.php           # sessions, CSRF, auth guards, escaping
│   └── seed_admin.php     # creates default admin on first boot
├── public/                # web root (Apache DocumentRoot)
│   ├── index.php          # dashboard
│   ├── register.php       # create voter account
│   ├── login.php / logout.php
│   ├── vote.php           # cast a vote
│   ├── results.php        # live results
│   ├── admin.php          # admin panel
│   └── includes/          # header / footer
└── css/style.css
```

## Dev tips

- Source files are bind-mounted, so PHP/CSS edits are picked up instantly (no rebuild needed).
- Logs: `docker compose logs -f web`
- Shell into the app: `docker compose exec web bash`

## Security notes / extensibility

Current hardening: prepared statements, bcrypt, CSRF, session regeneration, role checks, DB-level unique vote constraint.

Ideas if you want to go further: rate-limiting logins, two-factor auth, answer-only (no account) voting flows, per-election admin assignment, vote encryption at rest, or audit logs.