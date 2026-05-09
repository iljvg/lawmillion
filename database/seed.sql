-- =============================================================================
-- LawMillion.com — Seed Data
-- Run AFTER schema.sql
-- =============================================================================

USE lawmillion;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- US STATES (50 + DC)
-- -----------------------------------------------------------------------------
INSERT INTO us_states (code, slug, name) VALUES
('AL','alabama','Alabama'),('AK','alaska','Alaska'),('AZ','arizona','Arizona'),
('AR','arkansas','Arkansas'),('CA','california','California'),('CO','colorado','Colorado'),
('CT','connecticut','Connecticut'),('DE','delaware','Delaware'),('FL','florida','Florida'),
('GA','georgia','Georgia'),('HI','hawaii','Hawaii'),('ID','idaho','Idaho'),
('IL','illinois','Illinois'),('IN','indiana','Indiana'),('IA','iowa','Iowa'),
('KS','kansas','Kansas'),('KY','kentucky','Kentucky'),('LA','louisiana','Louisiana'),
('ME','maine','Maine'),('MD','maryland','Maryland'),('MA','massachusetts','Massachusetts'),
('MI','michigan','Michigan'),('MN','minnesota','Minnesota'),('MS','mississippi','Mississippi'),
('MO','missouri','Missouri'),('MT','montana','Montana'),('NE','nebraska','Nebraska'),
('NV','nevada','Nevada'),('NH','new-hampshire','New Hampshire'),('NJ','new-jersey','New Jersey'),
('NM','new-mexico','New Mexico'),('NY','new-york','New York'),('NC','north-carolina','North Carolina'),
('ND','north-dakota','North Dakota'),('OH','ohio','Ohio'),('OK','oklahoma','Oklahoma'),
('OR','oregon','Oregon'),('PA','pennsylvania','Pennsylvania'),('RI','rhode-island','Rhode Island'),
('SC','south-carolina','South Carolina'),('SD','south-dakota','South Dakota'),('TN','tennessee','Tennessee'),
('TX','texas','Texas'),('UT','utah','Utah'),('VT','vermont','Vermont'),
('VA','virginia','Virginia'),('WA','washington','Washington'),('WV','west-virginia','West Virginia'),
('WI','wisconsin','Wisconsin'),('WY','wyoming','Wyoming'),('DC','district-of-columbia','District of Columbia');

-- -----------------------------------------------------------------------------
-- A handful of major US cities (expand as needed)
-- -----------------------------------------------------------------------------
INSERT INTO us_cities (state_id, slug, name, population) VALUES
((SELECT id FROM us_states WHERE code='TX'), 'dallas',         'Dallas',          1304379),
((SELECT id FROM us_states WHERE code='TX'), 'houston',        'Houston',         2304580),
((SELECT id FROM us_states WHERE code='TX'), 'austin',         'Austin',           961855),
((SELECT id FROM us_states WHERE code='TX'), 'san-antonio',    'San Antonio',     1434625),
((SELECT id FROM us_states WHERE code='CA'), 'los-angeles',    'Los Angeles',     3898747),
((SELECT id FROM us_states WHERE code='CA'), 'san-francisco',  'San Francisco',    873965),
((SELECT id FROM us_states WHERE code='CA'), 'san-diego',      'San Diego',       1386932),
((SELECT id FROM us_states WHERE code='NY'), 'new-york',       'New York',        8336817),
((SELECT id FROM us_states WHERE code='NY'), 'buffalo',        'Buffalo',          278349),
((SELECT id FROM us_states WHERE code='IL'), 'chicago',        'Chicago',         2746388),
((SELECT id FROM us_states WHERE code='FL'), 'miami',          'Miami',            442241),
((SELECT id FROM us_states WHERE code='FL'), 'orlando',        'Orlando',          307573),
((SELECT id FROM us_states WHERE code='FL'), 'tampa',          'Tampa',            384959),
((SELECT id FROM us_states WHERE code='GA'), 'atlanta',        'Atlanta',          498715),
((SELECT id FROM us_states WHERE code='AZ'), 'phoenix',        'Phoenix',         1608139),
((SELECT id FROM us_states WHERE code='PA'), 'philadelphia',   'Philadelphia',    1603797),
((SELECT id FROM us_states WHERE code='PA'), 'pittsburgh',     'Pittsburgh',       302971),
((SELECT id FROM us_states WHERE code='OH'), 'columbus',       'Columbus',         906528),
((SELECT id FROM us_states WHERE code='OH'), 'cleveland',      'Cleveland',        372624),
((SELECT id FROM us_states WHERE code='MI'), 'detroit',        'Detroit',          639111),
((SELECT id FROM us_states WHERE code='WA'), 'seattle',        'Seattle',          737015),
((SELECT id FROM us_states WHERE code='CO'), 'denver',         'Denver',           715522),
((SELECT id FROM us_states WHERE code='MA'), 'boston',         'Boston',           654776),
((SELECT id FROM us_states WHERE code='NV'), 'las-vegas',      'Las Vegas',        641903),
((SELECT id FROM us_states WHERE code='DC'), 'washington',     'Washington',       689545);

-- -----------------------------------------------------------------------------
-- 15 PRACTICE AREAS (matches the 15 HTML files)
-- -----------------------------------------------------------------------------
INSERT INTO practice_areas (slug, name, noun, meta_title, meta_description, meta_keywords, h1, intro_html, sort_order) VALUES
('personal-injury', 'Personal Injury', 'lawyer',
 'Personal Injury Lawyer Near Me | Free Consultation | LawMillion',
 'Find top-rated personal injury lawyers near you. Car accidents, slip & fall, medical injuries, wrongful death. Free consultation. Verified attorneys in all 50 states.',
 'personal injury lawyer, personal injury attorney, car accident lawyer, slip and fall attorney, wrongful death lawyer, accident attorney, injury claim, free consultation',
 'Personal Injury Lawyers Near You — Free Case Review',
 '<p>If you''ve been injured in an accident, you may be entitled to compensation for medical bills, lost wages, and pain and suffering. Connect with verified personal injury lawyers across all 50 U.S. states.</p>',
 1),

('employment-law', 'Employment Law', 'attorney',
 'Employment Attorney Near Me | Wrongful Termination | LawMillion',
 'Find top-rated employment attorneys for wrongful termination, workplace discrimination, sexual harassment, wage theft, EEOC claims, and more. Free consultation. All 50 states.',
 'employment attorney, employment lawyer, wrongful termination attorney, discrimination attorney, sexual harassment attorney, wage and hour, EEOC, FMLA, retaliation, whistleblower',
 'Employment Lawyers Near You — Wrongful Termination, Discrimination & Wage Disputes',
 '<p>Workplace problems? Connect with verified employment attorneys for wrongful termination, discrimination, harassment, wage theft, EEOC claims, and more — free consultations nationwide.</p>',
 2),

('family-law', 'Family Law', 'attorney',
 'Family Law Attorney Near Me | Divorce & Custody | LawMillion',
 'Find top-rated family law attorneys for divorce, child custody, child support, alimony, prenups, and adoption. Free consultation. Verified family lawyers in all 50 states.',
 'family law attorney, divorce attorney, child custody lawyer, child support attorney, alimony lawyer, prenuptial agreement, adoption attorney, family lawyer near me',
 'Family Law Attorneys — Divorce, Custody & Child Support',
 '<p>Going through a divorce, custody dispute, or other family matter? Connect with experienced family law attorneys offering free consultations in your state.</p>',
 3),

('bankruptcy', 'Bankruptcy', 'attorney',
 'Bankruptcy Attorney Near Me | Ch. 7, 11, 13 | LawMillion',
 'Find top-rated bankruptcy attorneys for Chapter 7, Chapter 11, and Chapter 13 filings. Stop foreclosure, eliminate debt, get a fresh start. Free consultation, all 50 states.',
 'bankruptcy attorney, bankruptcy lawyer, chapter 7 attorney, chapter 13 attorney, chapter 11 attorney, debt relief lawyer, foreclosure defense, bankruptcy near me',
 'Bankruptcy Attorneys Near You — Chapter 7, 11 & 13 Help',
 '<p>Drowning in debt? Bankruptcy may give you a fresh start. Connect with bankruptcy attorneys experienced in Chapter 7, Chapter 11, and Chapter 13 filings — free consultations.</p>',
 4),

('business-law', 'Business Law', 'attorney',
 'Business Law Attorney Near Me | Contracts & LLC | LawMillion',
 'Find top-rated business law attorneys for contracts, LLC formation, M&A, startups, and litigation. Verified business lawyers nationwide. Free consultation.',
 'business law attorney, business lawyer, contract attorney, LLC formation, M&A attorney, startup lawyer, commercial litigation, business law near me',
 'Business Law Attorneys — Contracts, LLC Formation, M&A & Litigation',
 '<p>Whether you''re forming an LLC, signing a contract, or facing commercial litigation, our verified business law attorneys can help — free consultations across all 50 states.</p>',
 5),

('civil-rights', 'Civil Rights', 'attorney',
 'Civil Rights Attorney | Police Brutality & Discrimination',
 'Find civil rights attorneys for police brutality, discrimination, constitutional rights violations, and Section 1983 claims. Free consultation. Nationwide coverage.',
 'civil rights attorney, civil rights lawyer, police brutality attorney, section 1983, constitutional rights, excessive force, discrimination lawyer, ACLU lawyer',
 'Civil Rights Attorneys — Police Brutality, Discrimination & Constitutional Rights',
 '<p>If your civil rights have been violated, you have legal options. Connect with civil rights attorneys experienced in police brutality, discrimination, and constitutional rights cases.</p>',
 6),

('dui-traffic', 'DUI & Traffic', 'attorney',
 'DUI Attorney Near Me | Traffic Defense Lawyer | LawMillion',
 'Find top-rated DUI defense attorneys and traffic lawyers. DUI/DWI, reckless driving, speeding, license suspension. Free consultation. Verified lawyers in all 50 states.',
 'DUI attorney, DWI lawyer, traffic ticket lawyer, reckless driving attorney, license suspension, DUI defense, traffic court attorney, drunk driving lawyer',
 'DUI & Traffic Attorneys Near You — DUI Defense & Reckless Driving',
 '<p>Charged with DUI, DWI, reckless driving, or facing license suspension? Connect with experienced DUI defense and traffic attorneys offering free consultations.</p>',
 7),

('environmental-law', 'Environmental Law', 'lawyer',
 'Environmental Lawyer | PFAS & Toxic Tort Attorneys',
 'Find environmental lawyers for PFAS contamination, toxic tort claims, EPA compliance, and water/soil contamination cases. Free consultation. Nationwide.',
 'environmental lawyer, environmental attorney, PFAS attorney, toxic tort lawyer, EPA compliance, contamination lawyer, superfund attorney, water contamination',
 'Environmental Lawyers — PFAS, Toxic Torts & EPA Compliance',
 '<p>Exposed to environmental toxins or facing EPA enforcement? Connect with environmental lawyers experienced in PFAS, toxic torts, contamination, and compliance cases.</p>',
 8),

('estate-planning', 'Estate Planning', 'attorney',
 'Estate Planning Attorney Near Me | Wills & Trusts | LawMillion',
 'Find top-rated estate planning attorneys for wills, trusts, probate, and powers of attorney. Protect your family. Free consultation. Verified attorneys in all 50 states.',
 'estate planning attorney, will attorney, trust attorney, probate lawyer, power of attorney, living will, estate lawyer near me, inheritance attorney',
 'Estate Planning Attorneys — Wills, Trusts, Probate & Power of Attorney',
 '<p>Protect your family''s future. Connect with estate planning attorneys to draft wills, set up trusts, handle probate, and create powers of attorney — free consultations nationwide.</p>',
 9),

('immigration', 'Immigration', 'lawyer',
 'Immigration Lawyer Near Me | Visa & Green Card | LawMillion',
 'Find top-rated immigration lawyers for visas, green cards, citizenship, deportation defense, and asylum. Free consultation. Verified attorneys nationwide.',
 'immigration lawyer, immigration attorney, visa lawyer, green card attorney, citizenship attorney, deportation defense, asylum lawyer, USCIS attorney, H1B lawyer',
 'Immigration Lawyers Near You — Visas, Green Cards & Deportation Defense',
 '<p>Need help with a visa, green card, citizenship, or deportation case? Connect with verified immigration lawyers offering free consultations across all 50 U.S. states.</p>',
 10),

('intellectual-property', 'Intellectual Property', 'attorney',
 'Intellectual Property Attorney | Trademark & Patent Lawyers',
 'Find top-rated intellectual property attorneys for trademarks, copyrights, patents, and trade secrets. Free consultation. Verified IP lawyers nationwide.',
 'intellectual property attorney, IP lawyer, trademark attorney, patent attorney, copyright lawyer, trade secret, USPTO attorney, IP litigation',
 'Intellectual Property Attorneys — Trademark, Copyright & Patent Law',
 '<p>Protect your inventions, brands, and creative work. Connect with verified IP attorneys for trademark, copyright, patent, and trade secret matters — free consultations.</p>',
 11),

('medical-malpractice', 'Medical Malpractice', 'lawyer',
 'Medical Malpractice Lawyer Near Me | Free Case Review',
 'Find top-rated medical malpractice lawyers for misdiagnosis, surgical errors, birth injury, hospital negligence. Free case review. Verified attorneys in all 50 states.',
 'medical malpractice lawyer, medical malpractice attorney, misdiagnosis lawyer, surgical error attorney, birth injury lawyer, hospital negligence, medical mistakes',
 'Medical Malpractice Lawyers — Misdiagnosis, Surgical Errors & Birth Injury',
 '<p>Harmed by a medical mistake? Connect with experienced medical malpractice lawyers for misdiagnosis, surgical errors, birth injury, and hospital negligence cases.</p>',
 12),

('real-estate', 'Real Estate', 'attorney',
 'Real Estate Attorney Near Me | Closing & Disputes | LawMillion',
 'Find top-rated real estate attorneys for buying, selling, landlord-tenant disputes, and commercial real estate. Free consultation. Verified attorneys nationwide.',
 'real estate attorney, real estate lawyer, closing attorney, landlord tenant lawyer, commercial real estate attorney, property dispute lawyer, real estate near me',
 'Real Estate Attorneys — Buying, Selling & Landlord-Tenant Disputes',
 '<p>Buying or selling property? Facing a landlord-tenant dispute? Connect with verified real estate attorneys for residential and commercial matters — free consultations.</p>',
 13),

('social-security-disability', 'Social Security Disability', 'lawyer',
 'Social Security Disability Lawyer | SSDI & SSI Appeals',
 'Find top-rated Social Security disability lawyers for SSDI, SSI, and appeals. Get the benefits you deserve. Free consultation. Verified attorneys in all 50 states.',
 'social security disability lawyer, SSDI attorney, SSI lawyer, disability appeal attorney, SSA hearing lawyer, disability benefits attorney',
 'Social Security Disability Lawyers — SSDI, SSI & Appeals',
 '<p>Denied disability benefits? Connect with Social Security disability lawyers who handle SSDI, SSI, and appeals — pay nothing unless you win.</p>',
 14),

('tax-law', 'Tax Law', 'attorney',
 'Tax Attorney Near Me | IRS & Audit Defense | LawMillion',
 'Find top-rated tax attorneys for IRS problems, tax debt relief, audit defense, and offshore disclosures. Free consultation. Verified tax lawyers nationwide.',
 'tax attorney, tax lawyer, IRS attorney, tax debt relief, audit defense, offer in compromise, FBAR attorney, tax court lawyer',
 'Tax Attorneys Near You — IRS Problems, Tax Debt & Audit Defense',
 '<p>Owe the IRS? Facing an audit? Connect with tax attorneys who handle IRS disputes, tax debt relief, audit defense, and offshore compliance — free consultations.</p>',
 15);

-- Seed FAQ data for the Employment Law page (used by FAQPage schema)
UPDATE practice_areas
SET faq_json = JSON_ARRAY(
  JSON_OBJECT('q','What is wrongful termination?','a','Wrongful termination occurs when an employer fires an employee for illegal reasons such as discrimination, retaliation, or breach of contract. Most U.S. employees are at-will, but firings based on protected characteristics or in retaliation for protected activities (like reporting harassment) are unlawful.'),
  JSON_OBJECT('q','What constitutes workplace discrimination?','a','Workplace discrimination occurs when an employer treats an employee unfavorably because of race, color, religion, sex (including pregnancy, gender identity, sexual orientation), national origin, age (40+), disability, or genetic information. An EEOC charge must typically be filed within 180–300 days.'),
  JSON_OBJECT('q','What are my rights under the FLSA?','a','The Fair Labor Standards Act requires federal minimum wage of $7.25/hour (states may set higher), overtime pay at 1.5x for hours over 40 per week for non-exempt workers, and equal pay regardless of sex. Workers can sue for back wages plus liquidated damages.'),
  JSON_OBJECT('q','How do I file an EEOC charge?','a','File at publicportal.eeoc.gov, in person, or by mail within 180 days of the discriminatory act (300 days in most states). The EEOC investigates and may mediate; if no resolution, they issue a Right to Sue letter. You then have 90 days to file in federal court.'),
  JSON_OBJECT('q','How much does an employment attorney cost?','a','Most employment lawyers representing workers offer free consultations and work on contingency (33–40% of recovery, no fee if you don''t win). Attorney fees are also typically awarded against the employer in successful discrimination, harassment, and wage cases.')
)
WHERE slug = 'employment-law';

-- -----------------------------------------------------------------------------
-- BLOG CATEGORIES + sample post
-- -----------------------------------------------------------------------------
INSERT INTO blog_categories (slug, name) VALUES
('tax-law-updates',    'Tax Law Updates'),
('employment-rights',  'Employment Rights'),
('know-your-rights',   'Know Your Rights'),
('legal-guides-2026',  'Legal Guides 2026');

INSERT INTO blog_posts (slug, category_id, practice_area_id, title, meta_title, meta_description, excerpt, body_html, author_name, status, reading_minutes, date_published) VALUES
('tcja-sunset-2026-tax-cuts-jobs-act-guide',
 (SELECT id FROM blog_categories WHERE slug='tax-law-updates'),
 (SELECT id FROM practice_areas  WHERE slug='tax-law'),
 'TCJA Sunset 2026: What Every American Must Know About the Tax Cuts & Jobs Act Expiration',
 'TCJA Sunset 2026 Guide | Tax Cuts & Jobs Act Expiration',
 'The Tax Cuts and Jobs Act expires at the end of 2025. Learn how the TCJA sunset will affect your taxes, brackets, deductions, and estate planning in 2026 and beyond.',
 'The 2017 Tax Cuts and Jobs Act (TCJA) expires at the end of 2025. Here is what U.S. taxpayers need to know about how brackets, the standard deduction, the SALT cap, and estate exemptions will change in 2026.',
 '<p>The Tax Cuts and Jobs Act of 2017 brought sweeping changes to the U.S. tax code — most of them favorable to individual taxpayers. But many of those provisions sunset on December 31, 2025, which means 2026 will bring significant tax changes for nearly every American household.</p><h2>Individual tax brackets</h2><p>Lower brackets revert to pre-TCJA levels. The top rate moves from 37% back to 39.6%, and most middle-income brackets see 2–3 percentage point increases.</p><h2>Standard deduction</h2><p>The standard deduction roughly doubles under TCJA. Post-sunset, it returns to pre-2018 levels (approximately half) — making itemized deductions more attractive again for many filers.</p><h2>SALT cap</h2><p>The $10,000 state and local tax deduction cap expires, restoring the unlimited SALT deduction.</p><h2>Estate tax exemption</h2><p>The federal estate tax exemption (currently roughly $13.6M per individual) is set to roughly halve. Families with estates above the new threshold should consider gifting strategies in 2025.</p><h2>What to do now</h2><p>Talk to a tax attorney before year-end about Roth conversions, accelerating income, gifting strategies, and revisiting your estate plan.</p>',
 'LawMillion Editorial Team',
 'published',
 8,
 '2026-04-02 09:00:00');

-- -----------------------------------------------------------------------------
-- Sample attorney (matches the existing /attorney-profile.html demo)
-- -----------------------------------------------------------------------------
INSERT INTO users (email, password_hash, role, email_verified, is_active) VALUES
('amanda@richardsonlaw.example.com',
 '$2y$10$placeholderHashReplaceThisAfterSeedingxxxxxxxxxxxxxxxxxxxxxxx',
 'attorney', 1, 1);

INSERT INTO attorneys (
  user_id, slug, first_name, last_name, middle_initial,
  headline, bio_html, bar_number, bar_state_id,
  years_practicing, law_school, firm_name,
  street_address, city_id, state_id, zip_code,
  phone, email_public, website_url, photo_url,
  hourly_rate_low, hourly_rate_high, free_consultation, contingency_fee,
  rating_avg, review_count, is_verified, is_active,
  meta_title, meta_description
) VALUES (
  (SELECT id FROM users WHERE email='amanda@richardsonlaw.example.com'),
  'amanda-j-richardson', 'Amanda', 'Richardson', 'J.',
  'Personal Injury Attorney',
  '<p>Amanda J. Richardson is a board-certified personal injury attorney serving clients across Dallas–Fort Worth for over 15 years. She has recovered more than $80 million for accident victims and their families, with a focus on serious auto accidents, trucking collisions, and wrongful death cases.</p>',
  '24056789',
  (SELECT id FROM us_states WHERE code='TX'),
  15, 'University of Texas School of Law', 'Richardson Injury Law, PLLC',
  '1717 Main Street, Suite 3500',
  (SELECT id FROM us_cities WHERE slug='dallas' AND state_id=(SELECT id FROM us_states WHERE code='TX')),
  (SELECT id FROM us_states WHERE code='TX'),
  '75201',
  '+12145551717', 'amanda@richardsonlaw.example.com', 'https://richardsonlaw.example.com',
  NULL,
  NULL, NULL, 1, 1,
  4.9, 187, 1, 1,
  'Amanda J. Richardson | Personal Injury Lawyer Dallas TX',
  'Amanda J. Richardson — board-certified personal injury attorney in Dallas, TX. 15+ years, $80M+ recovered. Free consultation.'
);

-- Map this attorney to Personal Injury (primary)
INSERT INTO attorney_practice_areas (attorney_id, practice_area_id, is_primary) VALUES
((SELECT id FROM attorneys WHERE slug='amanda-j-richardson'),
 (SELECT id FROM practice_areas WHERE slug='personal-injury'), 1);

-- Sample reviews to feed AggregateRating schema
INSERT INTO attorney_reviews (attorney_id, reviewer_name, rating, title, body) VALUES
((SELECT id FROM attorneys WHERE slug='amanda-j-richardson'), 'Marcus T.', 5, 'Outstanding result',
 'Amanda settled my truck accident case for far more than I expected. She kept me informed every step of the way.'),
((SELECT id FROM attorneys WHERE slug='amanda-j-richardson'), 'Jennifer R.', 5, 'Highly recommend',
 'After my car accident, Amanda handled everything. The insurance company fought hard but she got us a great recovery.'),
((SELECT id FROM attorneys WHERE slug='amanda-j-richardson'), 'David K.',     4, 'Professional and thorough',
 'Very professional team. Took longer than I hoped but the result was worth it.');

-- -----------------------------------------------------------------------------
-- 301 REDIRECT MAP (legacy .html -> clean URLs)
-- -----------------------------------------------------------------------------
INSERT INTO redirects (old_path, new_path) VALUES
('/lawmillion-personal-injury.html',           '/practice-areas/personal-injury/'),
('/lawmillion-employment-law.html',            '/practice-areas/employment-law/'),
('/lawmillion-family-law.html',                '/practice-areas/family-law/'),
('/lawmillion-bankruptcy.html',                '/practice-areas/bankruptcy/'),
('/lawmillion-business-law.html',              '/practice-areas/business-law/'),
('/lawmillion-civil-rights.html',              '/practice-areas/civil-rights/'),
('/lawmillion-dui-traffic.html',               '/practice-areas/dui-traffic/'),
('/lawmillion-environmental-law.html',         '/practice-areas/environmental-law/'),
('/lawmillion-estate-planning.html',           '/practice-areas/estate-planning/'),
('/lawmillion-immigration.html',               '/practice-areas/immigration/'),
('/lawmillion-intellectual-property.html',     '/practice-areas/intellectual-property/'),
('/lawmillion-medical-malpractice.html',       '/practice-areas/medical-malpractice/'),
('/lawmillion-real-estate.html',               '/practice-areas/real-estate/'),
('/lawmillion-social-security.html',           '/practice-areas/social-security-disability/'),
('/lawmillion-tax-law.html',                   '/practice-areas/tax-law/'),
('/lawmillion-find-lawyers.html',              '/find-a-lawyer/'),
('/lawmillion-attorney-profile.html',          '/attorneys/tx/dallas/amanda-j-richardson/'),
('/lawmillion-attorney-profile-making.html',   '/for-attorneys/create-profile/'),
('/lawmillion-attorney-login.html',            '/for-attorneys/login/'),
('/lawmillion-lawyer-dashboard.html',          '/for-attorneys/dashboard/'),
('/lawmillion-blog-list.html',                 '/blog/'),
('/lawmillion-blog-detail.html',               '/blog/tcja-sunset-2026-tax-cuts-jobs-act-guide/'),
('/employment-law',                            '/practice-areas/employment-law/'),
('/personal-injury',                           '/practice-areas/personal-injury/'),
('/family-law',                                '/practice-areas/family-law/'),
('/bankruptcy',                                '/practice-areas/bankruptcy/'),
('/business-law',                              '/practice-areas/business-law/'),
('/civil-rights',                              '/practice-areas/civil-rights/'),
('/dui-traffic',                               '/practice-areas/dui-traffic/'),
('/environmental-law',                         '/practice-areas/environmental-law/'),
('/estate-planning',                           '/practice-areas/estate-planning/'),
('/immigration',                               '/practice-areas/immigration/'),
('/intellectual-property',                     '/practice-areas/intellectual-property/'),
('/medical-malpractice',                       '/practice-areas/medical-malpractice/'),
('/real-estate',                               '/practice-areas/real-estate/'),
('/social-security',                           '/practice-areas/social-security-disability/'),
('/tax-law',                                   '/practice-areas/tax-law/');

SET FOREIGN_KEY_CHECKS = 1;
