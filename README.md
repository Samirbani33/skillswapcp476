# SkillSwap — University Student Services Marketplace
**CP476B Internet Computing · Spring 2026 · Milestone 02**

> A full-stack web application where university students can buy, sell, and swap skills with each other.

---

## Team Members & Contributions
| Name | Role | Module | Key Files |
|------|------|--------|-----------|
| Samir Bani | Team Lead | Architecture & Integration | `backend/index.php`, GitHub setup |
| Isha Shah | Developer 1 | Authentication | `backend/routes/auth.php`, `frontend/register.html`, `frontend/login.html` |
| Shubh Upadhyay | Developer 2 | Marketplace | `backend/routes/services.php`, `frontend/marketplace.html`, `frontend/service-detail.html` |
| Aryan Tagi | Developer 3 | Booking System | `backend/routes/bookings.php`, `frontend/customer-dashboard.html`, `frontend/provider-dashboard.html` |

---

## Tech Stack
| Layer | Technology |
|-------|------------|
| Frontend | HTML5, CSS3, JavaScript ES6 (Fetch API) |
| Backend | PHP 8+ (built-in server, no framework) |
| Database | MySQL 8+ (normalized relational schema) |
| Auth | PHP sessions + bcrypt password hashing |
| Version Control | Git + GitHub |

---

## Project Structure
```
skillswapcp476/
├── frontend/
│   ├── index.html               ← Landing page
│   ├── register.html            ← User registration
│   ├── login.html               ← Login
│   ├── marketplace.html         ← Browse & search services
│   ├── service-detail.html      ← Service detail + booking
│   ├── provider-dashboard.html  ← Provider: manage listings & bookings
│   ├── customer-dashboard.html  ← Customer: track bookings, leave reviews
│   ├── admin-dashboard.html     ← Admin: moderation & user management
│   ├── messages.html            ← Internal messaging
│   ├── css/style.css            ← Global stylesheet (CSS variables)
│   └── js/
│       ├── api.js               ← Central fetch helper + nav updater
│       ├── auth.js              ← Register/login form logic
│       ├── validation.js        ← Client-side validation helpers
│       ├── marketplace.js       ← Service card loading + filters
│       └── dashboard.js         ← Shared dashboard logic
├── backend/
│   ├── index.php                ← Front controller / router
│   ├── config/db.php            ← MySQL connection helper
│   └── routes/
│       ├── auth.php             ← /auth/register|login|logout|me
│       ├── services.php         ← /services CRUD
│       ├── bookings.php         ← /bookings management
│       ├── reviews.php          ← /reviews submission
│       ├── messages.php         ← /messages threading
│       └── admin.php            ← /admin stats|reports|users
├── database/
│   ├── schema.sql               ← All CREATE TABLE statements
│   └── seed.sql                 ← Sample data for testing
├── docs/
│   └── WIKI.md                  ← Activity blog / meeting notes
├── setup.sh                     ← One-command DB setup
└── run.sh                       ← Start backend + open frontend
```

---

## How to Run Locally

### Prerequisites
- PHP 8+ — `brew install php` (Mac) or `sudo apt install php php-mysqli` (Linux)
- MySQL 8+ — `brew install mysql && brew services start mysql` (Mac)

### Step 1 — Clone
```bash
git clone https://github.com/Samirbani33/skillswapcp476.git
cd skillswapcp476
```

### Step 2 — Set up database
```bash
bash setup.sh
```
This creates the `skillswap` database, imports the schema, and optionally loads seed data.

### Step 3 — Run
```bash
bash run.sh
```
Starts PHP backend on `http://localhost:8000` and opens `frontend/index.html` in your browser.

### Test Accounts (after running seed.sql)
| Role | Email | Password |
|------|-------|----------|
| Customer | bob@mylaurier.ca | password123 |
| Provider | alice@mylaurier.ca | password123 |
| Admin | admin@skillswap.ca | password123 |

---

## API Endpoints
| Method | Route | Auth | Description |
|--------|-------|------|-------------|
| POST | /auth/register | — | Register new user |
| POST | /auth/login | — | Login |
| POST | /auth/logout | Session | Logout |
| GET | /auth/me | — | Check session |
| GET | /services | — | Browse/search services |
| GET | /services/{id} | — | Service detail + reviews |
| POST | /services | Provider | Create listing |
| PUT | /services/{id} | Owner | Update listing |
| DELETE | /services/{id} | Owner | Soft-delete listing |
| GET | /bookings | Session | My bookings |
| POST | /bookings | Customer | Book a service |
| PUT | /bookings/{id} | Session | Update status |
| GET | /reviews?service_id= | — | Service reviews |
| POST | /reviews | Customer | Submit review |
| GET | /messages | Session | Conversations list |
| GET | /messages/{id} | Session | Message thread |
| POST | /messages | Session | Send message |
| GET | /admin/stats | Admin | Platform stats |
| GET | /admin/reports | Admin | Moderation queue |
| PUT | /admin/reports/{id} | Admin | Resolve/dismiss report |
| GET | /admin/users | Admin | All users |
| PUT | /admin/users/{id} | Admin | Enable/disable user |

---

## Links
- **GitHub Repo:** https://github.com/Samirbani33/skillswapcp476
- **GitHub Project Board:** [Add Kanban link here]
- **Activity Blog / Wiki:** [docs/WIKI.md](docs/WIKI.md)
