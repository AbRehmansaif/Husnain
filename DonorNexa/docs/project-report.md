# DonorNexa
## A smart emergency blood-donor coordination portal

**Web Engineering — Complex Engineering Problem (CEP) semester project**  
**Technology:** PHP 8.1+, MySQL/MariaDB, PDO, HTML5, CSS3 and vanilla JavaScript  
**Deployment target:** XAMPP / Apache on a local Windows computer  
**Project slogan:** Connecting donors. Bridging lives. Saving hope.

> **Academic-integrity declaration:** This implementation was AI-assisted. The student authors must disclose that assistance, run the application themselves, understand the implementation and make their own final submission decisions. This report is a technical project report, not evidence that a clinical service has been validated.

## 1. Executive summary

DonorNexa is a role-based portal prototype for coordinating eligible blood donors with verified hospitals in Pakistan. It addresses delays and unfairness created when urgent blood requests are coordinated only through public posts, informal messaging and repeated phone calls. The design prioritizes rapid matching while protecting personal contact information and avoiding requests to donors who are unavailable or medically outside the project's basic eligibility rules.

The solution is a conventional PHP application with a small front controller, namespaced classes, PDO repositories/services, role-specific controllers and reusable PHP templates. MySQL stores normalized accounts, profiles, requests, matches, donations, inventory, settings, notifications and audit events. The front end is mobile-first and uses no third-party runtime JavaScript or CSS framework. Fetch requests supply the public dashboard statistics, emergency board and hospital donor search.

The key trade-offs are deliberate. The public board exposes only blood group, city, urgency, unit count and deadline. Candidate contact information is withheld until a donor accepts. Acceptance reserves one unit under a database transaction and row lock, so concurrent acceptances cannot exceed the number of unreserved units. A configurable cooldown and an eligibility view reduce donor fatigue. Donor photos and hospital licence documents are kept outside the public web root and streamed only to their authorized viewer. The system is designed as a locally runnable coursework prototype; it is not a clinical decision system.

## 2. Problem analysis

The project brief identifies four connected failures in informal blood coordination:

1. **Time:** a hospital may have to repeat calls and posts to find a donor.
2. **Trust:** a public post does not prove that the request came from a registered hospital.
3. **Donor welfare and fairness:** donors can be contacted repeatedly or while within a recovery cooldown.
4. **Privacy and allocation:** phone numbers may be exposed publicly and multiple donors may respond to a request whose remaining units are already covered.

DonorNexa converts those risks into explicit product rules. Hospitals register with a licence document and cannot create requests until an administrator verifies the account. Requests have a blood group, unit count, urgency, deadline, ward and optional coordination note. Donors are candidates only when their account is active, they are available, age 18–60, weight is at least 50 kg, and the configured cooldown has elapsed. The complete ABO/Rh red-cell compatibility map is applied before ranking. Matching uses blood-group exactness, city, time since last donation and response history. Donor contact is authorized only after acceptance, and the reveal is recorded in the audit trail.

The public board deliberately omits patient and donor identities, phone numbers, ward notes and hospital-specific contact details. This is a privacy boundary, not a limitation of the internal hospital work queue.

## 3. Goals, scope and non-functional requirements

### 3.1 Functional goals

- Let guests view live high-level statistics and a safe list of active emergencies.
- Let donors register, sign in/out, keep a profile, set availability, see eligibility, respond to matches and review donation history.
- Let hospitals register for verification, create blood requests, review ranked donors, see accepted donor contact details, close requests and record donations/inventory.
- Let administrators verify hospitals, block/unblock non-admin accounts, manage cities/settings, review analytics and audit events, and export reports.

### 3.2 Non-functional goals

- **Security:** password hashing, CSRF, output encoding, parameterized PDO queries, role guards, session regeneration and restrictive upload handling.
- **Privacy:** public responses contain no names or phone numbers; accepted-match phone access is audited.
- **Reliability:** reserve and completion operations use transactions; constraints and foreign keys protect stored relationships; errors are logged without displaying stack traces.
- **Usability:** responsive layout, semantic forms, keyboard focus states, clear validation, reduced-motion support and small-screen navigation.
- **Performance:** small dependency-free assets, indexed query columns and bounded/paginated lists. The under-two-second goal still needs measurement on the target PC using realistic data.
- **Maintainability:** separation into `config`, `src`, `public`, `templates`, `api`, `sql` and `docs`; PSR-12-inspired formatting and a simple namespace autoloader.

## 4. Roles and principal use cases

| Actor | Use case | Main preconditions and outcome |
| --- | --- | --- |
| Guest | View active emergencies | Only active requests from verified hospitals are returned; each item contains blood group, urgency, city, remaining units and deadline. |
| Guest | Register as donor | Server validates account/profile fields; email, CNIC and role-profile constraints prevent duplicate registrations. |
| Guest | Register a hospital | A PDF licence is required; the hospital account remains pending until administrator verification. |
| Donor | Sign in and manage profile | The session is regenerated on login; profile fields and an optional JPG/PNG image can be changed. |
| Donor | Change availability | Availability is auditable; the eligibility view still excludes donors who do not meet the age, weight or cooldown requirements. |
| Donor | Accept or decline a match | A response applies only to the signed-in donor's unresponded match. Acceptance reserves one request unit in a transaction; a decline may include a short reason. |
| Donor | Review history and badges | Verified donations are listed; first, fifth and tenth donation milestones are shown when reached. |
| Hospital | Verify and create a request | Only an active, verified hospital can create a request. At most three open Critical requests are permitted. |
| Hospital | Review matching donors | Eligible candidates are ranked; an accepted donor's phone becomes available only on the private hospital view. |
| Hospital | Complete a donation | Only the hospital owning the accepted match can verify it. Completion updates the request, donation record, donor cooldown and inventory atomically. |
| Hospital | Cancel a request | Open requests may be cancelled; pending/accepted matches are closed and trust score is reduced by five points. |
| Administrator | Review operations | Dashboard metrics, charts, pending hospitals, settings, account search, city management, audit search and CSV exports are available. |

### 4.1 Primary interaction flows

**Donor registration:** visitor opens account creation → chooses donor → enters identity/contact and eligibility profile → server validates the data → PDO transaction creates a password-hashed account and donor profile → the donor signs in and controls availability.

**Hospital verification:** hospital submits account details and PDF licence → status is pending → administrator inspects the licence and verifies or rejects → only verified hospitals can publish a request.

**Emergency fulfillment:** verified hospital creates a dated request → matching service filters eligible compatible donors and ranks candidates → top-N matches are recorded and portal notifications are created → donors accept/decline → acceptance reserves one unit under lock → hospital verifies donation → inventory and donor cooldown update.

## 5. Architecture and responsibilities

The browser requests `public/index.php`. That front controller starts a secure-configured session, restores an optional persistent login, runs request expiry, chooses a controller action and renders a reusable layout. JSON requests share the same front controller and are dispatched from `api/index.php`.

| Layer | Responsibility | Representative files |
| --- | --- | --- |
| Configuration | Timezone, session/CSRF helpers, DB connection | `config/app.php`, `config/database.php` |
| Core | Namespace autoloading, session lifecycle and template rendering | `src/Core/Autoloader.php`, `src/Core/Session.php`, `src/Core/BaseController.php` |
| Models | Shared PDO base, role profiles and typed behavior | `src/Models/User.php`, `Donor.php`, `Hospital.php`, `BloodRequest.php` |
| Repositories | Reusable user and city lookups | `src/Repositories/UserRepository.php`, `CityRepository.php` |
| Services | Matching, compatibility, audit, upload, persistent login and expiry | `src/Services/` |
| Controllers | Authentication, donor/hospital portal, administrator and public/API actions | `src/Controllers/` |
| Templates | Semantic layout and role-specific pages, escaped with `e()` | `templates/` |
| Browser assets | Responsive UI and Fetch/AJAX behavior | `public/assets/css/app.css`, `public/assets/js/app.js` |
| Persistence | Schema, view, trigger and fictional seed data | `sql/schema.sql`, `sql/seed.sql` |

Controllers call services for domain work, use repositories for reusable account and city lookups, and use prepared PDO statements for workflow-specific persistence. The project keeps the abstraction light: it does not add a framework or a separate generic repository for every table.

## 6. Database design

The database has a 3NF-oriented account/profile design. `users` stores the common identity, role, credential hash and status. Donor and hospital details are in their own one-to-one profile tables. City names are canonical in `cities`. Requests reference a hospital; `request_matches` represents the many-to-many donor/request relationship and stores its score, response status and timestamps. One donation can be recorded per accepted match. Inventory uses a composite hospital/blood-group key.

`system_settings` stores the cooldown and initial match-pool size. `v_eligible_donors` joins active users, donor profiles, cities and the configured cooldown. The donation trigger updates the donor's last-donation date and turns availability off. `audit_logs` has an optional actor, action, entity, old/new JSON snapshots, IP address and timestamp. Foreign-key delete rules protect request history while cascading a removed account's dependent profile and notifications.

The complete diagram and index discussion are in [`erd.md`](erd.md).

### 6.1 Concurrency and unit accounting

`units_needed` is the requested total. `units_reserved` represents accepted donations not yet verified. `units_fulfilled` represents completed donations. The database check constraint ensures that reserved plus fulfilled units do not exceed the request total.

For acceptance, the service obtains the request identifier, locks the request row with `SELECT ... FOR UPDATE`, then locks the match row. It checks request status and remaining capacity, changes the match to accepted, increments the reservation, and marks the donor unavailable within the same transaction. The consistent request-before-match lock order is also used when completing a donation. A failure rolls back instead of leaving a false reservation.

When a donation is verified, the transaction changes the match, inserts the unique donation record (triggering donor cooldown), moves one unit from reserved to fulfilled, updates request status and increments the hospital's blood inventory. This design makes concurrent donor acceptance safe and prevents duplicate completion for the same match.

### 6.2 Five meaningful database queries

1. **Find eligible donors:** the `v_eligible_donors` view combines account status, availability, age, weight and configured cooldown. `MatchingService` applies compatibility and calculates ranking values.
2. **Public emergency board:**

   ```sql
   SELECT r.id, r.blood_group, r.urgency, r.units_needed, r.units_fulfilled,
          r.required_by, c.city_name
   FROM blood_requests r
   JOIN hospitals h ON h.user_id = r.hospital_id
   JOIN cities c ON c.id = h.city_id
   WHERE r.status IN ('open', 'partially_fulfilled')
     AND r.required_by > NOW()
     AND h.verification_status = 'verified'
   ORDER BY FIELD(r.urgency, 'critical', 'urgent', 'normal'), r.required_by;
   ```
   No names, phone numbers or notes are selected for the public response.
3. **Request demand versus supply:**

   ```sql
   SELECT blood_group,
          SUM(units_needed) AS demand,
          SUM(units_fulfilled) AS supply
   FROM blood_requests
   GROUP BY blood_group;
   ```
4. **Requests per week:**

   ```sql
   SELECT DATE_FORMAT(created_at, '%x-%v') AS week, COUNT(*) AS total
   FROM blood_requests
   WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
   GROUP BY DATE_FORMAT(created_at, '%x-%v')
   ORDER BY week;
   ```
5. **Donor response rate for ranking:**

   ```sql
   SELECT donor_id,
          COUNT(*) AS responses,
          SUM(status IN ('accepted', 'declined', 'donated')) / COUNT(*) AS response_rate
   FROM request_matches
   GROUP BY donor_id;
   ```
6. **Fulfilment rate:** the administrator dashboard computes total verified fulfilled units divided by requested units, with `NULLIF` protecting the zero-request case.
7. **Hospital queue:** request lists are filtered by hospital ownership and optional search/urgency conditions, ordered by urgency/deadline and paginated.

## 7. Business logic

### 7.1 Compatibility

The `CompatibilityService` stores the red-cell donor-to-recipient map in an array, not an `if/else` chain. The map contains all eight ABO/Rh donor groups and their compatible recipient groups. Example: O− is accepted by all recipient groups; AB+ only accepts AB+. Matching passes each eligible donor through this map before calculating a score.

### 7.2 Eligibility

The database view and `Donor::isEligible` implement the basic project rule: age from 18 through 60 inclusive; weight at least 50 kg; availability on; active account; no last donation or at least the configured cooldown days since that date. Registration prevents under-18 accounts, but the live age view also excludes a donor after they age beyond the configured upper bound. An accepted donor becomes unavailable immediately, and a completed donation sets the cooldown date through the trigger.

### 7.3 Ranking and critical widening

For each compatible candidate:

```text
score = exact blood-group bonus (35)
      + same-city bonus (25)
      + capped days since last donation (0–20)
      + response-rate factor (0–20)
```

Candidates are sorted by descending score. The configured top-N pool is notified; Critical requests use twice the configured pool. This prioritizes high-confidence local candidates while still allowing broader outreach for the highest urgency. The score is a coordination heuristic, not a clinical prioritization score.

### 7.4 Expiry and anti-misuse

The expiry service runs on web requests, marks overdue open/partially fulfilled requests expired, marks still-unanswered notification rows `no_response`, and writes audit entries. Accepted donors are not silently converted to no-response; their reservations remain linked to the match. The hospital controller prevents more than three open Critical requests by locking the hospital row and checking its current critical queue. Each hospital cancellation reduces trust by five, down to zero.

## 8. Security and privacy controls

| Risk | Implemented control |
| --- | --- |
| Plaintext credential exposure | `password_hash(PASSWORD_DEFAULT)` on registration; `password_verify` on login. |
| SQL injection | PDO prepared statements with emulated prepares disabled; dynamic sort/search fields are whitelisted or parameterized. |
| Cross-site request forgery | A per-session cryptographically random token is rendered into forms and verified before every state-changing POST. |
| Cross-site scripting | Template values pass through `htmlspecialchars` via `e()`; browser-generated dynamic content uses `textContent`/DOM nodes. |
| Session fixation/theft | Session ID regeneration at login/remember restoration; HttpOnly, SameSite=Lax and HTTPS-aware Secure session cookies. |
| Insecure persistent login | Random 256-bit token in a 30-day HttpOnly cookie; only SHA-256 token hash stored; cookie rotates after restoration and is revoked at logout. |
| Malicious uploads | Server MIME detection with `finfo`, strict JPG/PNG/PDF allowlist, 2 MB photo and 5 MB licence limits, random filenames and private storage outside the web root with role-guarded streaming. |
| Unauthorized access | Role checks at controller actions; hospital ownership checks on request and match operations. |
| Donor phone disclosure | Public API excludes contact fields; hospital query reveals phone only when match status is accepted/donated; reveal is audit logged. |
| CSV formula injection | Export values beginning with spreadsheet formula characters are prefixed to avoid execution in common spreadsheet tools. |
| Operational errors | Friendly error response, server-side error log, no stack trace to the browser. |

### Security limitations

This is not a production security certification. A deployment needs HTTPS everywhere, a hardened database account, anti-brute-force/rate-limit controls, a strict Content Security Policy, upload malware scanning, backups and monitoring. The demo app intentionally contains test credentials and fictional seed data; do not expose it on a public server.

## 9. User interface and accessibility

The visual system uses a restrained warm-neutral canvas, dark ink, botanical green and a small coral accent. Donor privacy, hospital verification and request urgency have distinct visual treatments. Semantic landmarks (`header`, `nav`, `main`, `section`, `article`, `footer`), explicit labels, a skip link, visible keyboard focus, input types, form helper text, `aria-live` search output and a reduced-motion media query support accessible use. The mobile navigation is collapsible and tables remain horizontally scrollable on narrow screens.

CSS is hand-written; the app has no framework dependency. JavaScript uses Fetch for live statistics, the emergency board and donor search. Countdown labels update once per minute. Registration toggles the role-specific field set, applies client-side CNIC/mobile/upload checks and leaves server validation authoritative.

### 9.1 Screenshots for the final submission

Capture these screenshots on the target XAMPP setup after importing the database. Use fictional seed data and hide the browser's saved passwords:

1. Public landing page showing the hero and live community metrics.
2. Public emergency board showing a request with only safe fields.
3. Donor dashboard showing availability and matched requests.
4. Hospital dashboard showing a verified account and request form.
5. Hospital match view before acceptance (masked phone) and after acceptance (authorized phone).
6. Admin dashboard showing metrics, charts and pending verification.
7. Audit log containing the donor contact reveal event.

Suggested filenames: `docs/screenshots/01-home.png` through `07-audit-reveal.png`. Screenshots are intentionally not fabricated in this source-only workspace; insert genuine local captures before the final course submission.

## 10. Verification plan and test cases

Run the cases after setup and record actual results, browser and date. The outcomes below are expected results, not a claim that the target machine has executed them.

| ID | Scenario | Steps / input | Expected result |
| --- | --- | --- | --- |
| T01 | Public statistics | Open landing page; inspect network call to `?api=stats`. | JSON contains active requests, registered donors and fulfilled units; no identities. |
| T02 | Public privacy | Inspect emergency board API response. | Only request ID, group, urgency, unit counts, deadline and city are present. |
| T03 | Invalid email / duplicate email | Register with malformed email or an existing seeded email. | Client or server displays validation; database has no duplicate. |
| T04 | Invalid CNIC/mobile | Submit values not matching `#####-#######-#` / `03XX-XXXXXXX`. | Client validation provides a correction; server rejects tampered requests too. |
| T05 | Upload allowlist | Submit an executable renamed `.jpg` or an oversized photo/PDF. | MIME/size validation rejects it; no unsafe file is stored. |
| T06 | Hospital pending gate | Register a hospital and attempt to create a request before verification. | Status remains pending and request creation is denied. |
| T07 | Hospital verification | Admin verifies a pending test hospital. | Hospital status changes, audit event is created and request creation becomes available. |
| T08 | Donor eligibility | Use donors with age/weight/availability/cooldown boundary values. | Only age 18–60, weight ≥50, active, available, cooldown-complete donors appear in candidate set. |
| T09 | Compatibility map | Check O−→all, O+→positive recipients, AB+→AB+ only and all other matrix entries. | Candidate inclusion matches the RBC map. |
| T10 | Ranking | Create comparable compatible donors in same/different cities and vary last donation/response history. | Exact group/city and response history affect rank as specified; Critical doubles configured pool size. |
| T11 | Privacy before accept | Open a hospital matched donor list with notified match. | Phone remains masked and public endpoint never returns it. |
| T12 | Privacy after accept | Donor accepts, then reload the hospital match list. | Phone is visible to that hospital; a reveal audit record exists. |
| T13 | Concurrent capacity | Two independent browser sessions accept the last available unit at near the same time. | One transaction reserves the unit; the other is rejected; reserved + fulfilled never exceeds requested. |
| T14 | Decline | Donor declines with a reason, then attempts a second response. | Decline and reason are stored/audited; second response is rejected. |
| T15 | Donation completion | Hospital verifies an accepted donation twice. | First transaction records donation, inventory and cooldown; duplicate completion is rejected by match status/unique constraint. |
| T16 | Cooldown update | Change the admin setting; review donor eligibility after a donation. | `eligible again on` and the candidate view use the new configured value. |
| T17 | Expiry | Set request deadline in the past, then load a page. | Request becomes expired and unanswered matches become no-response with audit entries. |
| T18 | Critical cap and cancellation | Create three open Critical requests; try a fourth; cancel one request. | Fourth is blocked; cancellation releases reservations, closes pending matches and reduces trust by five. |
| T19 | Role authorization / CSRF | Submit a hospital URL as a donor or POST without a valid token. | Unauthorized action is denied; invalid token returns an expired-form error. |
| T20 | CSV export | Download donor and request reports; open in a spreadsheet. | Correct columns are exported; formula-leading text is neutralized. |
| T21 | Responsive and keyboard use | Use 360 px and desktop widths; navigate without a mouse. | Layout stays usable, focus remains visible and labels associate with controls. |
| T22 | Database outage | Stop MySQL and load the home page. | Friendly database error appears; details are written to the local error log. |

## 11. Requirements-to-code coverage

| CEP concept | DonorNexa implementation |
| --- | --- |
| Variables, types, arrays, functions, loops and conditionals | Compatibility array and candidate loops in `src/Services/CompatibilityService.php` and `MatchingService.php`; input and branch handling in controllers. |
| Classes, interfaces, inheritance, traits, namespaces, autoloading | Namespaced classes under `src/`; repository interface/implementations; `Authenticatable` interface; `Donor`/`Hospital` extend `User`; `Timestamped` trait; `Core/Autoloader.php`. |
| Forms and superglobals | Registration, donor responses, request creation, filters, `$_POST`, `$_GET`, `$_FILES`, `$_SERVER` usage in `src/Controllers/`. |
| Sessions and cookies | `Core/Session.php`, `config/app.php`, `Services/RememberMeService.php`; login regeneration and one-time flash queue. |
| Include/require and templating | `public/index.php`, `Core/BaseController.php`, reusable `templates/layout.php` and role templates. |
| File handling/uploads/CSV/logging | `Services/UploadService.php`, admin CSV export, `logs/error.log`. |
| Date/time, cooldown, expiry, timezone | `APP_TIMEZONE`, `Donor`, `RequestExpiryService`, MySQL view and JavaScript countdown labels. |
| Regex and `filter_var` validation | `AuthController` CNIC/mobile/email validation; browser patterns in registration forms and `app.js`. |
| Exceptions and logging | `ValidationException`, controller/service try-catch around transactions and `public/index.php` global error boundary. |
| PDO/prepared statements/transactions | `config/database.php`, prepared CRUD throughout controllers/services, accept/complete/cancel/verification transactions. |
| Joins, aggregates, group-by, subqueries, views | `AdminController`, dashboard queries, `MatchingService`, `v_eligible_donors` in `sql/schema.sql`. |
| HTML semantic markup/accessibility | Shared layout and page templates; labels, landmarks, status messages, skip link and keyboard focus. |
| CSS Grid/Flexbox/responsive design | `public/assets/css/app.css`; CSS variables, mobile-first layouts, media queries and handwritten styling. |
| DOM/events/Fetch/AJAX/debounce/charts | `public/assets/js/app.js`; live stats, board filter, donor search debounce/countdowns and chart rendering. |
| Password, CSRF, escaping, role-based access | `password_hash`/`password_verify`, `csrf_field`/`verify_csrf`, `e()`, `require_role()`. |
| Search, filter, sort and pagination | Hospital request and admin account/audit queues; urgency order and bounded page sizes. |

## 12. Design trade-offs and limitations

- **PHP without a framework:** chosen to keep the project portable and make core Web Engineering concepts directly visible. A larger production system should use a maintained framework, migrations and automated dependency management.
- **Notifications are in-app:** external SMS/email delivery would require a provider, verified sender identities, consent and retry handling. The prototype does not send messages outside the local system.
- **Expiry is request-driven:** this avoids requiring a background service on another student's laptop; a request-triggered cleanup is not a substitute for a reliable scheduler.
- **Basic donor screening only:** age, weight, availability and cooldown cannot determine clinical suitability. Medical staff must make all real-world decisions.
- **Hospital licence review is manual:** uploaded PDFs need an authorized reviewer; automated document validation is not attempted.
- **City-based proximity:** no geolocation is collected. City bonus is transparent and avoids sharing precise donor location.
- **Demo records are fictional:** seeds and default passwords exist only for local testing.
- **Screenshot and runtime evidence:** the source workspace did not include a PHP runtime when this report was prepared. Capture authentic screenshots, record test results and measure page load time on the actual local PC before final submission.

## 13. Conclusion

DonorNexa demonstrates how a role-based PHP/MySQL application can turn an urgent coordination problem into explicit, testable rules. Its central engineering choices are verified hospital participation, an eligibility/compatibility/ranking pipeline, privacy-gated contact disclosure, row-locked unit reservation and an auditable status history. The supplied schema, demo data, report and setup guide make the project portable to a second XAMPP computer. Before submission, the student team should run every test case, add real screenshots from its own local run, verify the SQL on its selected MySQL/MariaDB release and prepare to explain and defend all design choices.

## References and technical guidance

- Project requirements: *23SW_WE_CEP_PHP_Project.pdf*, Web Engineering CEP semester project brief, Department of Software Engineering.
- PHP manual: password hashing, PDO, sessions, file uploads, filters and date/time functions.
- MySQL reference manual: InnoDB transactions, indexes, views, triggers, constraints and JSON data.
- OWASP Cheat Sheet Series: CSRF Prevention, Session Management, SQL Injection Prevention, Cross Site Scripting Prevention and File Upload guidance.
