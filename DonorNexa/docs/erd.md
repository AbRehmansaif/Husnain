# DonorNexa relational ERD

The diagram shows the core 3NF relationships. Names, roles, contact profiles and request state are separated so that account credentials are not repeated across role-specific records.

```mermaid
erDiagram
    CITIES ||--o{ DONORS : "based in"
    CITIES ||--o{ HOSPITALS : "based in"
    USERS ||--o| DONORS : "donor profile"
    USERS ||--o| HOSPITALS : "hospital profile"
    HOSPITALS ||--o{ BLOOD_REQUESTS : "creates"
    BLOOD_REQUESTS ||--o{ REQUEST_MATCHES : "matched through"
    DONORS ||--o{ REQUEST_MATCHES : "receives"
    REQUEST_MATCHES ||--o| DONATIONS : "completed as"
    HOSPITALS ||--o{ BLOOD_INVENTORY : "holds"
    USERS ||--o{ NOTIFICATIONS : "receives"
    USERS ||--o{ AUDIT_LOGS : "performs"
    USERS ||--o{ REMEMBER_TOKENS : "authenticates with"

    USERS {
        bigint id PK
        varchar name
        varchar email UK
        varchar password_hash
        varchar role
        varchar status
    }
    DONORS {
        bigint user_id PK,FK
        varchar blood_group
        date dob
        decimal weight
        smallint city_id FK
        varchar phone
        char cnic UK
        date last_donation_date
        boolean is_available
    }
    HOSPITALS {
        bigint user_id PK,FK
        varchar hospital_name
        varchar licence_no UK
        smallint city_id FK
        varchar verification_status
        smallint trust_score
    }
    CITIES {
        smallint id PK
        varchar city_name
        varchar province
    }
    BLOOD_REQUESTS {
        bigint id PK
        bigint hospital_id FK
        varchar blood_group
        tinyint units_needed
        tinyint units_reserved
        tinyint units_fulfilled
        varchar urgency
        datetime required_by
        varchar status
    }
    REQUEST_MATCHES {
        bigint id PK
        bigint request_id FK
        bigint donor_id FK
        decimal score
        varchar status
        datetime responded_at
    }
    DONATIONS {
        bigint id PK
        bigint match_id FK,UK
        tinyint units
        datetime donated_on
        bigint verified_by FK
    }
    BLOOD_INVENTORY {
        bigint hospital_id PK,FK
        varchar blood_group PK
        smallint units_available
    }
    NOTIFICATIONS {
        bigint id PK
        bigint user_id FK
        varchar message
        boolean is_read
        timestamp created_at
    }
    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK
        varchar action
        varchar entity
        bigint entity_id
        json old_value
        json new_value
        varchar ip_address
        timestamp created_at
    }
    SYSTEM_SETTINGS {
        varchar setting_key PK
        varchar setting_value
    }
    REMEMBER_TOKENS {
        char token_hash PK
        bigint user_id FK
        datetime expires_at
    }
```

## Relationship and normalization notes

- Each `users` row has one role; role-specific donor and hospital attributes live in a one-to-one profile table. This avoids nullable donor/hospital columns on every account.
- `city_id` references one canonical city row, and hospital city is used for request matching; the request table does not duplicate city name/province.
- A request-to-donor relationship is many-to-many and is represented by `request_matches`, with a unique `(request_id, donor_id)` key and relationship attributes such as score, response state and response time.
- A donation is tied to one accepted match. `UNIQUE(match_id)` prevents recording the same match twice.
- Blood inventory is keyed by `(hospital_id, blood_group)`; changing stock does not require adding a new column for each blood group.
- `system_settings` stores mutable cooldown and ranking configuration. `v_eligible_donors` reads the configured cooldown; `trg_donation_updates_donor` maintains the donor's latest donation date.
- The audit log captures who performed a change and when. `old_value`/`new_value` are JSON because changes differ by entity type; they are not used as normalized business attributes.

## Index justification

- `users(email)` and profile licence/CNIC columns are unique lookup and duplicate-prevention keys.
- `donors(blood_group, city_id, is_available)` and `donors(last_donation_date)` support candidate discovery and eligibility.
- `blood_requests(status, urgency, required_by)`, `(hospital_id, status, created_at)` and `(blood_group, urgency, required_by)` support the public board, hospital dashboard and request filtering.
- `request_matches(request_id, status)` and `(donor_id, status, created_at)` support donor and hospital work queues.
- Audit and notification indexes follow the common time/user filters used by their respective dashboards.
