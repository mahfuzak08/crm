-- 005: Fund contributions — money put into the company/project by directors or others
-- (capital, loans, other). Not income: kept out of profit/loss, shown as cash inflow.

CREATE TABLE IF NOT EXISTS fund_contributions (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id        INT UNSIGNED NOT NULL,
    fund_number       VARCHAR(30)  NOT NULL,
    user_id           INT UNSIGNED NULL COMMENT 'contributor when a director/user',
    contributor_name  VARCHAR(150) NOT NULL COMMENT 'snapshot or free text for outside contributors',
    contributor_phone VARCHAR(30)  NULL,
    fund_type         ENUM('capital','loan','other') NOT NULL DEFAULT 'capital',
    project_id        INT UNSIGNED NULL,
    purpose           VARCHAR(255) NULL,
    contribution_date DATE NOT NULL,
    amount            DECIMAL(15,2) NOT NULL,
    payment_method_id INT UNSIGNED NULL,
    reference         VARCHAR(150) NULL,
    notes             TEXT NULL,
    attachment        VARCHAR(100) NULL,
    attachment_name   VARCHAR(200) NULL,
    attachment_mime   VARCHAR(100) NULL,
    status            ENUM('received','cancelled') NOT NULL DEFAULT 'received',
    created_by        INT UNSIGNED NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at        DATETIME NULL,
    UNIQUE KEY uq_funds_number (company_id, fund_number),
    KEY idx_funds_date (company_id, contribution_date, status),
    KEY idx_funds_user (company_id, user_id),
    KEY idx_funds_project (company_id, project_id),
    CONSTRAINT fk_funds_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_funds_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_funds_project FOREIGN KEY (project_id) REFERENCES projects(id),
    CONSTRAINT fk_funds_method FOREIGN KEY (payment_method_id) REFERENCES master_data(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permissions (name, module, label) VALUES
    ('funds.view', 'funds', 'View fund contributions'),
    ('funds.create', 'funds', 'Record fund contributions'),
    ('funds.edit', 'funds', 'Edit fund contributions'),
    ('funds.delete', 'funds', 'Cancel fund contributions')
ON DUPLICATE KEY UPDATE module = VALUES(module), label = VALUES(label);

-- Grant to existing default roles (new installs get these from app/config/permissions.php)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.module = 'funds'
WHERE r.company_id IS NULL AND (r.slug IN ('admin', 'accountant') OR (r.slug = 'director' AND p.name = 'funds.view'));
