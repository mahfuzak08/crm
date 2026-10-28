-- 006: Board of directors + director-referred share sales.
-- A director is picked from an existing customer or employee (or carried over from a
-- user with the Director role). On a referred sale the company books the package price
-- (office copy) while the customer copy shows the price the director sold at.

CREATE TABLE IF NOT EXISTS directors (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id   INT UNSIGNED NOT NULL,
    customer_id  INT UNSIGNED NULL,
    employee_id  INT UNSIGNED NULL,
    user_id      INT UNSIGNED NULL,
    name         VARCHAR(150) NOT NULL COMMENT 'snapshot, the live name comes from the linked record',
    phone        VARCHAR(30)  NULL,
    email        VARCHAR(150) NULL,
    designation  VARCHAR(80)  NULL COMMENT 'e.g. Chairman, Managing Director, Director',
    joined_on    DATE NULL,
    sort_order   INT NOT NULL DEFAULT 0,
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    notes        TEXT NULL,
    created_by   INT UNSIGNED NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_directors_customer (company_id, customer_id),
    UNIQUE KEY uq_directors_employee (company_id, employee_id),
    UNIQUE KEY uq_directors_user (company_id, user_id),
    KEY idx_directors_company (company_id, status, sort_order),
    CONSTRAINT fk_directors_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_directors_customer FOREIGN KEY (customer_id) REFERENCES customers(id),
    CONSTRAINT fk_directors_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_directors_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE share_sales
    ADD COLUMN director_id         INT UNSIGNED NULL COMMENT 'referring director' AFTER sales_person_id,
    ADD COLUMN customer_unit_price DECIMAL(15,2) NULL COMMENT 'unit price the director sold at (customer copy)' AFTER director_id,
    ADD COLUMN customer_total      DECIMAL(15,2) NULL COMMENT 'grand total shown to the customer' AFTER customer_unit_price,
    ADD COLUMN director_profit     DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'customer_total - grand_total' AFTER customer_total,
    ADD KEY idx_sales_director (company_id, director_id),
    ADD CONSTRAINT fk_sales_director FOREIGN KEY (director_id) REFERENCES directors(id);

-- Carry over users that already hold the Director role (linked to their customer record when one exists).
INSERT IGNORE INTO directors (company_id, user_id, customer_id, name, phone, email, designation, status)
SELECT u.company_id, u.id,
       (SELECT c.id FROM customers c WHERE c.company_id = u.company_id AND c.user_id = u.id AND c.deleted_at IS NULL ORDER BY c.id LIMIT 1),
       u.name, u.phone, u.email, 'Director', IF(u.status = 'active', 'active', 'inactive')
FROM users u
WHERE u.deleted_at IS NULL
  AND EXISTS (SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE ur.user_id = u.id AND r.slug = 'director');

INSERT INTO permissions (name, module, label) VALUES
    ('directors.view', 'directors', 'View board of directors & referral profit'),
    ('directors.manage', 'directors', 'Add / edit / remove directors')
ON DUPLICATE KEY UPDATE module = VALUES(module), label = VALUES(label);

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.module = 'directors'
WHERE r.company_id IS NULL AND (r.slug = 'admin' OR (r.slug IN ('director', 'manager', 'accountant') AND p.name = 'directors.view'));
