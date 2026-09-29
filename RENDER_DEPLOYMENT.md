# Render deployment

## Prerequisites

- A GitHub repository containing this project.
- A MySQL database reachable from Render (Render PostgreSQL is not compatible with this application).
- Docker available locally if you want to test the image before deploying.

## Prepare the database

1. Create a MySQL database and user with permission to create and modify tables in the application database.
2. Import the schema into that database:

```bash
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p "$DB_NAME" < database/schema.sql
```

3. Optionally run `database/seed.php` once from a trusted shell after setting the same environment variables. It creates demo accounts and sample polls. Do not run it on every startup or expose it publicly.

## Create the Render Web Service

1. Push the project to GitHub.
2. Create a new **Web Service** in Render and select the repository.
3. Choose **Docker** as the runtime; Render will build the root `Dockerfile`.
4. Add `DB_HOST`, `DB_PORT` (normally `3306`), `DB_NAME`, `DB_USER`, and `DB_PASSWORD` using values from the MySQL provider.
5. Deploy. The container changes Apache's listening port to Render's injected `PORT`; local containers default to port 80.

## Verify deployment

- Open `/health.php`; it should return `OK`.
- Confirm polls load.
- Log in with a user created by your seed/import process.
- As an admin, create a poll and verify it appears publicly.
- As a normal user, vote once, refresh, and confirm a second vote is rejected.

## Logs and common fixes

Use the Render service's **Logs** tab. For database errors, verify all five variables, network access, and that the schema was imported into `DB_NAME`. For missing tables, repeat the schema import. For failed login, confirm users exist in `users`. For missing assets, deploy the complete repository; existing relative paths are preserved.

Database/password values must be configured in Render, never committed to Git. `.env` is ignored and `database/` is denied by Apache so schema/seed files are not publicly served.
