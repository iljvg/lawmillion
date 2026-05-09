-- =============================================================================
-- LawMillion.com — Marketplace Migration
-- Run AFTER schema.sql + seed.sql
-- Idempotent: safe to re-run (uses IF NOT EXISTS / INSERT IGNORE / etc.)
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. Extend attorneys table with wallet + plan
-- -----------------------------------------------------------------------------
-- Note: ALTER TABLE ADD COLUMN IF NOT EXISTS works in MySQL 8.0+.
--       If you're on MySQL 5.7, drop the IF NOT EXISTS and run once.
ALTER TABLE attorneys
  ADD COLUMN IF NOT EXISTS wallet_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER review_count,
  ADD COLUMN IF NOT EXISTS plan ENUM('basic','pro','elite') NOT NULL DEFAULT 'basic' AFTER wallet_balance;

-- -----------------------------------------------------------------------------
-- 2. Marketplace leads (auction-style, separate from intake `leads` table)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS marketplace_leads (
  id                  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  external_id         VARCHAR(20)       NOT NULL,             -- "L001", display ref
  title               VARCHAR(255)      NOT NULL,
  practice_area_id    SMALLINT UNSIGNED NOT NULL,
  sub_category        VARCHAR(100)      DEFAULT NULL,         -- "Car Accident"
  city                VARCHAR(80)       DEFAULT NULL,
  state_code          CHAR(2)           DEFAULT NULL,         -- 'TX'
  urgency             ENUM('high','medium','low') NOT NULL DEFAULT 'medium',
  budget_label        VARCHAR(60)       DEFAULT NULL,         -- "Contingency", "$2K-$5K"
  estimated_value     VARCHAR(60)       DEFAULT NULL,         -- "$80K-$150K"
  description         TEXT              DEFAULT NULL,
  facts_json          JSON              DEFAULT NULL,         -- ["fact1","fact2"]
  client_name         VARCHAR(120)      DEFAULT NULL,
  client_phone        VARCHAR(20)       DEFAULT NULL,
  client_email        VARCHAR(120)      DEFAULT NULL,
  status              ENUM('open','won','closed') NOT NULL DEFAULT 'open',
  won_by_attorney_id  INT UNSIGNED      DEFAULT NULL,
  expires_at          DATETIME          NOT NULL,             -- timer end
  created_at          DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_external (external_id),
  KEY idx_status (status),
  KEY idx_expires (expires_at),
  KEY idx_pa (practice_area_id),
  CONSTRAINT fk_ml_pa  FOREIGN KEY (practice_area_id)   REFERENCES practice_areas(id),
  CONSTRAINT fk_ml_won FOREIGN KEY (won_by_attorney_id) REFERENCES attorneys(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- 3. Bids (one row per bid event; one attorney can have many bids per lead)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS marketplace_bids (
  id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  lead_id      INT UNSIGNED  NOT NULL,
  attorney_id  INT UNSIGNED  NOT NULL,
  amount       DECIMAL(8,2)  NOT NULL,
  created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lead (lead_id),
  KEY idx_attorney (attorney_id),
  KEY idx_lead_amount (lead_id, amount DESC),
  CONSTRAINT fk_mb_lead FOREIGN KEY (lead_id)     REFERENCES marketplace_leads(id) ON DELETE CASCADE,
  CONSTRAINT fk_mb_atty FOREIGN KEY (attorney_id) REFERENCES attorneys(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================================
-- SEED DATA
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Make sure Amanda has a wallet and Elite plan (idempotent UPDATE)
-- -----------------------------------------------------------------------------
UPDATE attorneys
SET wallet_balance = 340.00,
    plan           = 'elite'
WHERE slug = 'amanda-j-richardson';

-- -----------------------------------------------------------------------------
-- Seed 7 competitor attorneys (no user logins — these are bid-data fixtures)
-- We use INSERT IGNORE on slug so re-running this file is safe.
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO attorneys (slug, first_name, last_name, is_active, is_verified, plan, wallet_balance) VALUES
('jason-park',     'Jason',   'Park',    1, 1, 'pro',   500.00),
('maria-torres',   'Maria',   'Torres',  1, 1, 'basic', 200.00),
('robert-kim',     'Robert',  'Kim',     1, 1, 'pro',   400.00),
('carlos-vega',    'Carlos',  'Vega',    1, 1, 'basic', 150.00),
('sarah-nguyen',   'Sarah',   'Nguyen',  1, 1, 'elite', 800.00),
('michael-santos', 'Michael', 'Santos',  1, 1, 'pro',   350.00),
('elena-rios',     'Elena',   'Rios',    1, 1, 'basic', 120.00);

-- -----------------------------------------------------------------------------
-- Seed 8 marketplace leads — mirroring the original mockup data so the
-- dashboard looks identical the first time it loads.
-- expires_at is set RELATIVE to NOW() so timers match the mockup's seconds.
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO marketplace_leads
  (external_id, title, practice_area_id, sub_category, city, state_code, urgency,
   budget_label, estimated_value, description, facts_json,
   client_name, client_phone, client_email, status, expires_at)
SELECT 'L001',
       'Car Accident — Rear-end collision',
       (SELECT id FROM practice_areas WHERE slug='personal-injury'),
       'Car Accident', 'Dallas', 'TX', 'high',
       'Contingency', '$80K–$150K',
       'Client stopped at a red light struck from behind by a commercial vehicle. Neck/back pain, 3 weeks missed work. Insurance offering only $8,000. Full claim evaluation and litigation needed.',
       JSON_ARRAY('ER visit & imaging documented','Missed work records available','Driver cited at scene','Commercial vehicle — higher liability'),
       'Michael Davidson', '(214) 555-0812', 'm.davidson@email.com',
       'open', DATE_ADD(NOW(), INTERVAL 7 MINUTE)
WHERE NOT EXISTS (SELECT 1 FROM marketplace_leads WHERE external_id='L001');

INSERT IGNORE INTO marketplace_leads
  (external_id, title, practice_area_id, sub_category, city, state_code, urgency,
   budget_label, estimated_value, description, facts_json,
   client_name, client_phone, client_email, status, expires_at)
SELECT 'L002',
       'DUI Defense — First offense CDL holder',
       (SELECT id FROM practice_areas WHERE slug='criminal-defense'),
       'DUI / DWI', 'Fort Worth', 'TX', 'high',
       '$2K–$5K', '$3,500',
       'First DUI, BAC 0.09. CDL at stake — commercial truck driver. No prior record. Arraignment next week. Urgent defense attorney needed.',
       JSON_ARRAY('No prior criminal record','CDL license at risk — livelihood','BAC at threshold 0.09','Arraignment in 7 days'),
       'Thomas Harris', '(817) 555-0341', 't.harris@email.com',
       'open', DATE_ADD(NOW(), INTERVAL 23 MINUTE)
WHERE NOT EXISTS (SELECT 1 FROM marketplace_leads WHERE external_id='L002');

INSERT IGNORE INTO marketplace_leads
  (external_id, title, practice_area_id, sub_category, city, state_code, urgency,
   budget_label, estimated_value, description, facts_json,
   client_name, client_phone, client_email, status, expires_at)
SELECT 'L003',
       'Contested divorce — custody & business',
       (SELECT id FROM practice_areas WHERE slug='family-law'),
       'Custody', 'Plano', 'TX', 'medium',
       '$5K–$15K', '$12,000',
       'Divorce with two minor children (4 & 7). Spouse has counsel. Disputes over custody and a jointly owned business (~$400K). Primary earner seeking representation.',
       JSON_ARRAY('Two children (4 & 7)','Business interest ~$400K','Both parties have counsel','Client is primary earner'),
       'Jennifer Kelly', '(972) 555-0229', 'j.kelly@email.com',
       'open', DATE_ADD(NOW(), INTERVAL 11 MINUTE)
WHERE NOT EXISTS (SELECT 1 FROM marketplace_leads WHERE external_id='L003');

INSERT IGNORE INTO marketplace_leads
  (external_id, title, practice_area_id, sub_category, city, state_code, urgency,
   budget_label, estimated_value, description, facts_json,
   client_name, client_phone, client_email, status, expires_at)
SELECT 'L004',
       'Slip & Fall — Major retail chain',
       (SELECT id FROM practice_areas WHERE slug='personal-injury'),
       'Premises Liability', 'Irving', 'TX', 'medium',
       'Contingency', '$50K–$200K',
       'Knee injury requiring surgery from unmarked wet floor at a major retailer. Scene photos, ER records secured. Store denying liability. Strong premises case.',
       JSON_ARRAY('Scene photos secured','ER & surgical records ready','No warning signs placed','Store surveillance subpoenaed'),
       'Patricia Reynolds', '(469) 555-0551', 'p.reynolds@email.com',
       'open', DATE_ADD(NOW(), INTERVAL 29 MINUTE)
WHERE NOT EXISTS (SELECT 1 FROM marketplace_leads WHERE external_id='L004');

INSERT IGNORE INTO marketplace_leads
  (external_id, title, practice_area_id, sub_category, city, state_code, urgency,
   budget_label, estimated_value, description, facts_json,
   client_name, client_phone, client_email, status, expires_at)
SELECT 'L005',
       '18-Wheeler accident — I-35, catastrophic',
       (SELECT id FROM practice_areas WHERE slug='personal-injury'),
       'Truck Accident', 'Dallas', 'TX', 'high',
       'Contingency', '$500K+',
       'Client sideswiped by commercial 18-wheeler. Fractured ribs, concussion. Trucking company adjuster already contacted client. BLACK BOX preservation needed URGENTLY.',
       JSON_ARRAY('BLACK BOX — preserve now!','FMCSA violations suspected','Adjuster contacted client','Catastrophic — high case value'),
       'Brian Wilson', '(214) 555-0774', 'b.wilson@email.com',
       'open', DATE_ADD(NOW(), INTERVAL 30 MINUTE)
WHERE NOT EXISTS (SELECT 1 FROM marketplace_leads WHERE external_id='L005');

INSERT IGNORE INTO marketplace_leads
  (external_id, title, practice_area_id, sub_category, city, state_code, urgency,
   budget_label, estimated_value, description, facts_json,
   client_name, client_phone, client_email, status, expires_at)
SELECT 'L006',
       'Deportation defense — 15yr resident',
       (SELECT id FROM practice_areas WHERE slug='immigration'),
       'Deportation', 'Houston', 'TX', 'high',
       '$3K–$8K', '$6,500',
       'Notice to Appear issued. 15-year US resident, no criminal record, has US citizen children. Released on bond. Immigration hearing in 6 weeks.',
       JSON_ARRAY('US citizen children — strong case','15-year residency documented','No criminal record','Hearing in 6 weeks'),
       'Maria Garcia', '(713) 555-0883', 'm.garcia@email.com',
       'open', DATE_ADD(NOW(), INTERVAL 19 MINUTE)
WHERE NOT EXISTS (SELECT 1 FROM marketplace_leads WHERE external_id='L006');

INSERT IGNORE INTO marketplace_leads
  (external_id, title, practice_area_id, sub_category, city, state_code, urgency,
   budget_label, estimated_value, description, facts_json,
   client_name, client_phone, client_email, status, expires_at)
SELECT 'L007',
       'Wrongful termination — Racial discrimination',
       (SELECT id FROM practice_areas WHERE slug='employment-law'),
       'Wrongful Termination', 'Austin', 'TX', 'low',
       'Contingency', '$80K–$200K',
       '11-year employee terminated 2 weeks after filing racial harassment complaint. Documented HR complaints, witnesses, email chain. EEOC deadline in 60 days.',
       JSON_ARRAY('Documented HR complaint','2-week termination gap — retaliation','Email chain preserved','EEOC deadline: 60 days'),
       'Marcus Thomas', '(512) 555-0162', 'm.thomas@email.com',
       'won', DATE_SUB(NOW(), INTERVAL 1 HOUR)
WHERE NOT EXISTS (SELECT 1 FROM marketplace_leads WHERE external_id='L007');

INSERT IGNORE INTO marketplace_leads
  (external_id, title, practice_area_id, sub_category, city, state_code, urgency,
   budget_label, estimated_value, description, facts_json,
   client_name, client_phone, client_email, status, expires_at)
SELECT 'L008',
       'IRS Audit — $340K proposed adjustment',
       (SELECT id FROM practice_areas WHERE slug='tax-law'),
       'IRS Audit', 'Dallas', 'TX', 'medium',
       '$5K–$15K', '$12,000',
       'Small business owner, 3-year IRS field audit, $340K proposed adjustment. Contractor vs. employee classification dispute. CPA referral.',
       JSON_ARRAY('$340K proposed adjustment','3-year audit window','Classification dispute','CPA referral — cooperative'),
       'HIDDEN', 'HIDDEN', 'HIDDEN',
       'closed', DATE_SUB(NOW(), INTERVAL 2 HOUR)
WHERE NOT EXISTS (SELECT 1 FROM marketplace_leads WHERE external_id='L008');

-- -----------------------------------------------------------------------------
-- Mark L007 as won by Amanda (so she sees it in her Won Leads view)
-- -----------------------------------------------------------------------------
UPDATE marketplace_leads
SET won_by_attorney_id = (SELECT id FROM attorneys WHERE slug='amanda-j-richardson')
WHERE external_id='L007';

-- -----------------------------------------------------------------------------
-- Seed bids for the 8 leads
-- We re-insert only if no bids yet exist for a given lead (idempotent guard).
-- -----------------------------------------------------------------------------
SET @amanda_id   := (SELECT id FROM attorneys WHERE slug='amanda-j-richardson');
SET @jason_id    := (SELECT id FROM attorneys WHERE slug='jason-park');
SET @maria_id    := (SELECT id FROM attorneys WHERE slug='maria-torres');
SET @robert_id   := (SELECT id FROM attorneys WHERE slug='robert-kim');
SET @carlos_id   := (SELECT id FROM attorneys WHERE slug='carlos-vega');
SET @sarah_id    := (SELECT id FROM attorneys WHERE slug='sarah-nguyen');
SET @michael_id  := (SELECT id FROM attorneys WHERE slug='michael-santos');
SET @elena_id    := (SELECT id FROM attorneys WHERE slug='elena-rios');

-- L001 — Amanda outbid by 3 others
SET @l001 := (SELECT id FROM marketplace_leads WHERE external_id='L001');
INSERT INTO marketplace_bids (lead_id, attorney_id, amount, created_at)
SELECT * FROM (
  SELECT @l001 AS lid, @amanda_id  AS aid, 12.00 AS amt, DATE_SUB(NOW(), INTERVAL 19 MINUTE) AS ts UNION ALL
  SELECT @l001,         @robert_id,         15.00,         DATE_SUB(NOW(), INTERVAL 14 MINUTE)         UNION ALL
  SELECT @l001,         @maria_id,          18.00,         DATE_SUB(NOW(), INTERVAL  8 MINUTE)         UNION ALL
  SELECT @l001,         @jason_id,          22.00,         DATE_SUB(NOW(), INTERVAL  2 MINUTE)
) AS s
WHERE NOT EXISTS (SELECT 1 FROM marketplace_bids WHERE lead_id=@l001);

-- L002 — Amanda outbid by Carlos
SET @l002 := (SELECT id FROM marketplace_leads WHERE external_id='L002');
INSERT INTO marketplace_bids (lead_id, attorney_id, amount, created_at)
SELECT * FROM (
  SELECT @l002 AS lid, @amanda_id AS aid, 28.00 AS amt, DATE_SUB(NOW(), INTERVAL 11 MINUTE) AS ts UNION ALL
  SELECT @l002,         @carlos_id,        30.00,         DATE_SUB(NOW(), INTERVAL  5 MINUTE)
) AS s
WHERE NOT EXISTS (SELECT 1 FROM marketplace_bids WHERE lead_id=@l002);

-- L003 — no Amanda bid; 3 competitors
SET @l003 := (SELECT id FROM marketplace_leads WHERE external_id='L003');
INSERT INTO marketplace_bids (lead_id, attorney_id, amount, created_at)
SELECT * FROM (
  SELECT @l003 AS lid, @maria_id  AS aid, 35.00 AS amt, DATE_SUB(NOW(), INTERVAL 28 MINUTE) AS ts UNION ALL
  SELECT @l003,         @jason_id,        40.00,         DATE_SUB(NOW(), INTERVAL 25 MINUTE)         UNION ALL
  SELECT @l003,         @sarah_id,        45.00,         DATE_SUB(NOW(), INTERVAL 18 MINUTE)
) AS s
WHERE NOT EXISTS (SELECT 1 FROM marketplace_bids WHERE lead_id=@l003);

-- L004 — Amanda is leading (only bidder)
SET @l004 := (SELECT id FROM marketplace_leads WHERE external_id='L004');
INSERT INTO marketplace_bids (lead_id, attorney_id, amount, created_at)
SELECT * FROM (
  SELECT @l004 AS lid, @amanda_id AS aid, 19.00 AS amt, DATE_SUB(NOW(), INTERVAL 1 MINUTE) AS ts
) AS s
WHERE NOT EXISTS (SELECT 1 FROM marketplace_bids WHERE lead_id=@l004);

-- L005 — no bids yet (be first!)
-- L006 — 3 competitors, Amanda not bidding
SET @l006 := (SELECT id FROM marketplace_leads WHERE external_id='L006');
INSERT INTO marketplace_bids (lead_id, attorney_id, amount, created_at)
SELECT * FROM (
  SELECT @l006 AS lid, @robert_id   AS aid, 42.00 AS amt, DATE_SUB(NOW(), INTERVAL 16 MINUTE) AS ts UNION ALL
  SELECT @l006,         @elena_id,           50.00,         DATE_SUB(NOW(), INTERVAL  9 MINUTE)         UNION ALL
  SELECT @l006,         @michael_id,         55.00,         DATE_SUB(NOW(), INTERVAL  3 MINUTE)
) AS s
WHERE NOT EXISTS (SELECT 1 FROM marketplace_bids WHERE lead_id=@l006);

-- L007 — Amanda WON at $24
SET @l007 := (SELECT id FROM marketplace_leads WHERE external_id='L007');
INSERT INTO marketplace_bids (lead_id, attorney_id, amount, created_at)
SELECT * FROM (
  SELECT @l007 AS lid, @maria_id  AS aid, 20.00 AS amt, DATE_SUB(NOW(), INTERVAL 4 HOUR) AS ts UNION ALL
  SELECT @l007,         @amanda_id,       24.00,         DATE_SUB(NOW(), INTERVAL 3 HOUR)
) AS s
WHERE NOT EXISTS (SELECT 1 FROM marketplace_bids WHERE lead_id=@l007);

-- L008 — closed; won by another attorney; Amanda doesn't see contact
SET @l008 := (SELECT id FROM marketplace_leads WHERE external_id='L008');
UPDATE marketplace_leads
SET won_by_attorney_id = (SELECT id FROM attorneys WHERE slug='jason-park')
WHERE external_id='L008';
INSERT INTO marketplace_bids (lead_id, attorney_id, amount, created_at)
SELECT * FROM (
  SELECT @l008 AS lid, @sarah_id AS aid, 28.00 AS amt, DATE_SUB(NOW(), INTERVAL 5 HOUR) AS ts UNION ALL
  SELECT @l008,         @jason_id,        35.00,         DATE_SUB(NOW(), INTERVAL 4 HOUR)
) AS s
WHERE NOT EXISTS (SELECT 1 FROM marketplace_bids WHERE lead_id=@l008);
