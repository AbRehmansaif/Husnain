USE donornexa;
SET NAMES utf8mb4;
SET time_zone = '+05:00';

INSERT INTO cities (id, city_name, province) VALUES
    (1, 'Lahore', 'Punjab'),
    (2, 'Karachi', 'Sindh'),
    (3, 'Islamabad', 'ICT'),
    (4, 'Peshawar', 'Khyber Pakhtunkhwa'),
    (5, 'Quetta', 'Balochistan')
ON DUPLICATE KEY UPDATE province = VALUES(province);

-- Local demonstration password for all seeded accounts: password
INSERT INTO users (id, name, email, password_hash, role, status) VALUES
    (1, 'DonorNexa Administrator', 'admin@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'admin', 'active'),
    (2, 'Ayesha Khan', 'ayesha.khan@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (3, 'Hamza Malik', 'hamza.malik@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (4, 'Zara Ahmed', 'zara.ahmed@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (5, 'Usman Ali', 'usman.ali@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (6, 'Hira Shah', 'hira.shah@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (7, 'Bilal Hussain', 'bilal.hussain@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (8, 'Sana Iqbal', 'sana.iqbal@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (9, 'Omar Farooq', 'omar.farooq@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (10, 'Mariam Raza', 'mariam.raza@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (11, 'Danish Qureshi', 'danish.qureshi@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (12, 'Noor Siddiqui', 'noor.siddiqui@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (13, 'Saad Tariq', 'saad.tariq@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (14, 'Amina Sheikh', 'amina.sheikh@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (15, 'Rehan Butt', 'rehan.butt@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (16, 'Laiba Noor', 'laiba.noor@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (17, 'Fahad Akram', 'fahad.akram@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (18, 'Iqra Javed', 'iqra.javed@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (19, 'Taha Mir', 'taha.mir@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (20, 'Mehak Aslam', 'mehak.aslam@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (21, 'Raza Jamil', 'raza.jamil@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (22, 'Amna Khalid', 'amna.khalid@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (23, 'Shahzaib Khan', 'shahzaib.khan@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (24, 'Areeba Fatima', 'areeba.fatima@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (25, 'Noman Rafi', 'noman.rafi@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (26, 'Sobia Younis', 'sobia.younis@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (27, 'Arham Zafar', 'arham.zafar@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (28, 'Nida Saleem', 'nida.saleem@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (29, 'Sameer Abbasi', 'sameer.abbasi@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (30, 'Hafsa Qazi', 'hafsa.qazi@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (31, 'Ali Raza', 'ali.raza@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'donor', 'active'),
    (32, 'Fatima Memorial Hospital', 'fmh@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'hospital', 'active'),
    (33, 'Jinnah Medical Centre', 'jmc@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'hospital', 'active'),
    (34, 'Shifa Community Hospital', 'shifa@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'hospital', 'active'),
    (35, 'Hayatabad Medical Complex', 'hmc@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'hospital', 'active'),
    (36, 'Civil Hospital Quetta', 'chq@donornexa.test', '$2y$12$w197dqbSORkY50n0.KiLJuvNHO0PQKwuw1Odo19a2mvoaKwTjxzzW', 'hospital', 'active')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO donors (user_id, blood_group, dob, weight, city_id, phone, cnic, last_donation_date, is_available) VALUES
    (2, 'O-', '1996-04-12', 62, 1, '0300-1000002', '35202-1000002-1', NULL, 0),
    (3, 'O+', '1993-11-03', 74, 1, '0300-1000003', '35202-1000003-2', NULL, 1),
    (4, 'A+', '1998-06-22', 58, 2, '0300-1000004', '35202-1000004-3', NULL, 1),
    (5, 'B+', '1991-02-14', 81, 3, '0300-1000005', '35202-1000005-4', NULL, 1),
    (6, 'A-', '1997-09-10', 56, 1, '0300-1000006', '35202-1000006-5', NULL, 1),
    (7, 'B-', '1989-12-01', 69, 2, '0300-1000007', '35202-1000007-6', NULL, 1),
    (8, 'AB+', '1995-08-16', 63, 3, '0300-1000008', '35202-1000008-7', NULL, 1),
    (9, 'O+', '2000-03-28', 60, 4, '0300-1000009', '35202-1000009-8', NULL, 1),
    (10, 'A+', '1992-07-07', 72, 5, '0300-1000010', '35202-1000010-9', NULL, 1),
    (11, 'O-', '1999-01-19', 55, 2, '0300-1000011', '35202-1000011-0', NULL, 1),
    (12, 'B+', '1987-05-23', 78, 1, '0300-1000012', '35202-1000012-1', NULL, 1),
    (13, 'AB-', '1994-10-30', 66, 3, '0300-1000013', '35202-1000013-2', NULL, 1),
    (14, 'A-', '1996-03-11', 53, 4, '0300-1000014', '35202-1000014-3', NULL, 1),
    (15, 'O+', '1990-12-25', 84, 5, '0300-1000015', '35202-1000015-4', NULL, 1),
    (16, 'B-', '2001-02-08', 57, 1, '0300-1000016', '35202-1000016-5', NULL, 1),
    (17, 'A+', '1993-09-17', 76, 2, '0300-1000017', '35202-1000017-6', NULL, 1),
    (18, 'AB+', '1988-06-04', 68, 3, '0300-1000018', '35202-1000018-7', NULL, 1),
    (19, 'O-', '1997-11-20', 59, 4, '0300-1000019', '35202-1000019-8', NULL, 1),
    (20, 'B+', '1995-01-31', 71, 5, '0300-1000020', '35202-1000020-9', NULL, 1),
    (21, 'A-', '1990-08-09', 64, 1, '0300-1000021', '35202-1000021-0', NULL, 1),
    (22, 'O+', '1998-04-26', 61, 2, '0300-1000022', '35202-1000022-1', NULL, 1),
    (23, 'AB-', '1986-12-13', 80, 3, '0300-1000023', '35202-1000023-2', NULL, 1),
    (24, 'B-', '1999-05-06', 54, 4, '0300-1000024', '35202-1000024-3', NULL, 1),
    (25, 'A+', '1991-10-02', 73, 5, '0300-1000025', '35202-1000025-4', NULL, 1),
    (26, 'O-', '1994-02-21', 67, 1, '0300-1000026', '35202-1000026-5', NULL, 1),
    (27, 'B+', '1996-07-18', 82, 2, '0300-1000027', '35202-1000027-6', NULL, 1),
    (28, 'A-', '1989-04-05', 58, 3, '0300-1000028', '35202-1000028-7', NULL, 1),
    (29, 'O+', '1997-12-29', 70, 4, '0300-1000029', '35202-1000029-8', NULL, 1),
    (30, 'AB+', '1992-03-15', 63, 5, '0300-1000030', '35202-1000030-9', NULL, 1),
    (31, 'A+', '2000-09-24', 56, 1, '0300-1000031', '35202-1000031-0', NULL, 1)
ON DUPLICATE KEY UPDATE blood_group = VALUES(blood_group);

INSERT INTO hospitals
    (user_id, hospital_name, licence_no, city_id, contact_phone, verification_status, trust_score)
VALUES
    (32, 'Fatima Memorial Hospital', 'FMH-LHR-2024', 1, '0301-2000032', 'verified', 96),
    (33, 'Jinnah Medical Centre', 'JMC-KHI-2024', 2, '0301-2000033', 'verified', 92),
    (34, 'Shifa Community Hospital', 'SCH-ISB-2024', 3, '0301-2000034', 'verified', 98),
    (35, 'Hayatabad Medical Complex', 'HMC-PEW-2024', 4, '0301-2000035', 'verified', 89),
    (36, 'Civil Hospital Quetta', 'CHQ-QTA-2024', 5, '0301-2000036', 'verified', 94)
ON DUPLICATE KEY UPDATE hospital_name = VALUES(hospital_name);

INSERT INTO blood_requests
    (id, hospital_id, blood_group, units_needed, units_reserved, units_fulfilled, urgency, required_by, ward, notes, status)
VALUES
    (1, 32, 'O-', 3, 0, 0, 'critical', DATE_ADD(NOW(), INTERVAL 3 HOUR), 'Emergency ward', 'Urgent transfusion coordination.', 'open'),
    (2, 33, 'A+', 2, 0, 0, 'urgent', DATE_ADD(NOW(), INTERVAL 7 HOUR), 'Surgical recovery', NULL, 'open'),
    (3, 34, 'B+', 2, 0, 0, 'normal', DATE_ADD(NOW(), INTERVAL 22 HOUR), 'Maternity unit', NULL, 'open'),
    (4, 32, 'O+', 3, 1, 0, 'critical', DATE_ADD(NOW(), INTERVAL 4 HOUR), 'Emergency ward', 'One donor has accepted; two units remain.', 'partially_fulfilled'),
    (5, 35, 'A-', 2, 0, 0, 'urgent', DATE_ADD(NOW(), INTERVAL 9 HOUR), 'Trauma care', NULL, 'open'),
    (6, 36, 'AB+', 1, 0, 0, 'normal', DATE_ADD(NOW(), INTERVAL 30 HOUR), 'Medical ward', NULL, 'open'),
    (7, 33, 'O+', 2, 0, 0, 'critical', DATE_ADD(NOW(), INTERVAL 2 HOUR), 'Emergency ward', 'Please respond as soon as possible.', 'open'),
    (8, 34, 'B-', 1, 0, 0, 'urgent', DATE_ADD(NOW(), INTERVAL 10 HOUR), 'Surgical ward', NULL, 'open'),
    (9, 35, 'A+', 2, 0, 0, 'normal', DATE_ADD(NOW(), INTERVAL 36 HOUR), 'Maternity unit', NULL, 'open'),
    (10, 36, 'O-', 2, 0, 0, 'urgent', DATE_ADD(NOW(), INTERVAL 12 HOUR), 'Emergency ward', NULL, 'open'),
    (11, 32, 'AB-', 1, 0, 0, 'normal', DATE_ADD(NOW(), INTERVAL 48 HOUR), 'Medical ward', NULL, 'open'),
    (12, 33, 'B+', 2, 0, 0, 'urgent', DATE_ADD(NOW(), INTERVAL 11 HOUR), 'Surgical recovery', NULL, 'open'),
    (13, 34, 'A-', 1, 0, 0, 'normal', DATE_ADD(NOW(), INTERVAL 40 HOUR), 'Trauma care', NULL, 'open'),
    (14, 35, 'O+', 3, 0, 0, 'critical', DATE_ADD(NOW(), INTERVAL 5 HOUR), 'Emergency ward', NULL, 'open'),
    (15, 36, 'AB+', 1, 0, 0, 'normal', DATE_ADD(NOW(), INTERVAL 50 HOUR), 'Medical ward', NULL, 'open'),
    (16, 32, 'B-', 2, 0, 0, 'urgent', DATE_ADD(NOW(), INTERVAL 14 HOUR), 'Surgical ward', NULL, 'open'),
    (17, 33, 'A+', 1, 0, 0, 'normal', DATE_ADD(NOW(), INTERVAL 64 HOUR), 'Maternity unit', NULL, 'open'),
    (18, 34, 'O+', 1, 0, 1, 'urgent', DATE_SUB(NOW(), INTERVAL 2 DAY), 'Surgical recovery', NULL, 'fulfilled'),
    (19, 35, 'A+', 1, 0, 1, 'normal', DATE_SUB(NOW(), INTERVAL 1 DAY), 'Medical ward', NULL, 'fulfilled'),
    (20, 36, 'B+', 1, 0, 1, 'urgent', DATE_SUB(NOW(), INTERVAL 3 DAY), 'Emergency ward', NULL, 'fulfilled')
ON DUPLICATE KEY UPDATE ward = VALUES(ward);

INSERT INTO request_matches (id, request_id, donor_id, score, status, responded_at) VALUES
    (1, 4, 2, 93.50, 'accepted', NOW()),
    (2, 18, 3, 87.00, 'donated', DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (3, 19, 4, 81.50, 'donated', DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (4, 20, 5, 78.25, 'donated', DATE_SUB(NOW(), INTERVAL 3 DAY)),
    (5, 1, 6, 91.25, 'notified', NULL),
    (6, 2, 7, 82.00, 'notified', NULL),
    (7, 3, 8, 74.50, 'notified', NULL),
    (8, 5, 9, 72.75, 'notified', NULL)
ON DUPLICATE KEY UPDATE score = VALUES(score);

INSERT INTO donations (id, match_id, units, donated_on, verified_by) VALUES
    (1, 2, 1, DATE_SUB(NOW(), INTERVAL 2 DAY), 34),
    (2, 3, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), 35),
    (3, 4, 1, DATE_SUB(NOW(), INTERVAL 3 DAY), 36)
ON DUPLICATE KEY UPDATE units = VALUES(units);

INSERT INTO notifications (user_id, message) VALUES
    (2, 'A O+ critical blood request is available in Lahore. Your acceptance is confirmed.'),
    (6, 'A O- critical blood request is available in Lahore.'),
    (7, 'A A+ urgent blood request is available in Karachi.'),
    (8, 'A B+ normal blood request is available in Islamabad.'),
    (9, 'A A- urgent blood request is available in Peshawar.');

INSERT INTO blood_inventory (hospital_id, blood_group, units_available) VALUES
    (34, 'O+', 1),
    (35, 'A+', 1),
    (36, 'B+', 1)
ON DUPLICATE KEY UPDATE units_available = VALUES(units_available);

ALTER TABLE users AUTO_INCREMENT = 37;
ALTER TABLE blood_requests AUTO_INCREMENT = 21;
ALTER TABLE request_matches AUTO_INCREMENT = 9;
ALTER TABLE donations AUTO_INCREMENT = 4;
