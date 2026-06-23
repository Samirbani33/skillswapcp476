# SkillSwap — Activity Blog / Wiki
**CP476B Internet Computing · Spring 2026**

---

## Meeting #1 — Project Kickoff & Planning
**Date:** Week 1  
**Attendees:** Samir Bani, Isha Shah, Shubh Upadhyay, Aryan Tagi, Laiba Ali  
**Duration:** 75 minutes  
**Location:** Library Room 203

### Agenda & Minutes
- Brainstormed project ideas; voted on SkillSwap marketplace concept (unanimous)
- Defined problem statement: students lack a trusted peer-to-peer skills exchange
- Identified target users: university students as both buyers and sellers
- Discussed technical stack options (Node.js vs PHP) — chose **PHP** for simpler local setup

### Decisions Made
| Decision | Rationale |
|----------|-----------|
| PHP backend over Node.js | Simpler local dev, no npm complexity, course-covered |
| MySQL with normalized schema | Course requirement, referential integrity, FK constraints |
| Session-based auth (not JWT) | Simpler to implement, course-covered approach |
| Separate categories table | Normalization — avoids string duplication in services |
| conversations + messages split | 3NF — conversation is distinct entity from individual messages |

### Task Assignments
| Task | Assignee | Due |
|------|----------|-----|
| GitHub repo + Kanban setup | Samir | Week 1 |
| Database schema v1 | All (collaborative) | Week 1 |
| User stories + wireframes | All | Week 1 |
| Auth module (register/login) | Isha | Week 2 |
| Marketplace frontend | Shubh | Week 2 |
| Booking system backend | Aryan | Week 3 |
| Reviews system | Laiba | Week 3 |

---

## Meeting #2 — Milestone 01 Review & Milestone 02 Sprint Planning
**Date:** Week 2  
**Attendees:** Full team  
**Duration:** 60 minutes

### Progress Since Last Meeting
- ✅ GitHub repo created: `skillswapcp476`
- ✅ Kanban board initialized with all 5 columns and 10 user story cards
- ✅ Milestone 01 report submitted (ERD, wireframes, user stories, team plan)
- ✅ Database schema drafted (6 tables)

### Milestone 02 Sprint Goals
1. Complete all frontend screens matching wireframes
2. Finalize and normalize database schema
3. Implement backend routes (auth, services, bookings, reviews)
4. Connect frontend to backend via fetch API
5. Run setup script and test locally

### Architecture Decisions
- **Routing:** Single `index.php` front controller — parses URI segments and delegates to route files
- **CORS:** `Access-Control-Allow-Origin: *` header on all responses for local dev
- **Password security:** `password_hash()` with `PASSWORD_BCRYPT` — never store plaintext
- **SQL injection prevention:** All queries use `$db->prepare()` with `bind_param()` — zero string interpolation of user input
- **Category normalization:** `services.category_id` FK → `categories` table (instead of VARCHAR column) — avoids redundancy

### Challenges & Resolutions
| Challenge | Resolution |
|-----------|------------|
| CORS errors when JS called PHP from `file://` | Added full CORS headers to index.php; use `http://localhost:8000` |
| PHP sessions not persisting across fetch calls | Added `credentials: 'include'` on all apiFetch calls |
| `category` as VARCHAR caused duplication | Refactored to `category_id` FK → normalized `categories` table |
| Messaging required unique conversation per pair | Added UNIQUE constraint on `(user_a_id, user_b_id)` with CHECK `user_a < user_b` |

---

## Meeting #3 — Integration & Testing
**Date:** Week 3  
**Attendees:** Full team  
**Duration:** 60 minutes

### Work Completed This Week
- ✅ All backend routes implemented and tested manually:
  - `POST /auth/register` — validates, hashes password, creates session
  - `POST /auth/login` — verifies bcrypt hash, starts session
  - `GET/POST/PUT/DELETE /services` — full CRUD with category FK join
  - `GET/POST/PUT /bookings` — request, accept/reject, complete
  - `POST /reviews` — gated to completed bookings only
  - `GET/POST /messages` — conversation threading
  - `GET /admin/stats|reports|users` — admin moderation
- ✅ Frontend screens complete: Landing, Register, Login, Marketplace, Service Detail, Provider Dashboard, Customer Dashboard, Admin Dashboard, Messages
- ✅ Seed data loaded — 5 users, 6 services, 5 bookings, 2 reviews, 3 messages
- ✅ Navigation updated: logged-in state shows dashboard link + logout

### Open Items for Milestone 03
- [ ] Full integration testing (end-to-end booking flow)
- [ ] Input sanitization review
- [ ] Demo video recording
- [ ] README final update

---

## Definition of Done
A task is "Done" when ALL of the following are true:
1. ✅ Feature code written and committed to a feature branch
2. ✅ Peer code review completed (at least one other team member)
3. ✅ Manually tested locally against the running backend
4. ✅ Kanban card moved to **Done** column
5. ✅ This wiki updated with the completed task and any decisions made
