# Combined Users Lab App (Plain PHP + MySQL + CURL)

## Files

- `config.php` - all configuration values in one place
- `db.php` - local database connection + local user query
- `api/users.php` - local API endpoint returning users in JSON
- `helpers_curl.php` - CURL helper for remote API calls
- `index.php` - main page showing merged local + remote users
- `sample_users.sql` - sample SQL schema and seed data

## Quick Setup

1. Create/import local DB data:
   - Run `sample_users.sql` in MySQL.
2. Update `config.php`:
   - `company_name`
   - DB credentials (`host`, `database`, `username`, `password`)
   - `remote_api_urls` for partner companies
3. Serve project with PHP (example):
   - `php -S localhost:8000`
4. Open:
   - `http://localhost:8000/companies/sarah/combined-users/index.php`
   - Local API test: `http://localhost:8000/companies/sarah/combined-users/api/users.php`

## Example JSON from api/users.php

```json
{
  "success": true,
  "company": "Company A",
  "count": 4,
  "users": [
    {
      "id": 1,
      "name": "Alice Johnson",
      "email": "alice@company-a.com",
      "company": "Company A"
    }
  ]
}
```

## How Company A/B/C use same code

Each company runs the same files, but changes only `config.php` and local DB values:

- **Company A**
  - `company_name = Company A`
  - DB = `company_a_db`
  - remote URLs point to Company B + C APIs
- **Company B**
  - `company_name = Company B`
  - DB = `company_b_db`
  - remote URLs point to Company A + C APIs
- **Company C**
  - `company_name = Company C`
  - DB = `company_c_db`
  - remote URLs point to Company A + B APIs

The code stays the same; only config changes.
