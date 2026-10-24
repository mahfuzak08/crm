-- 001: Companies, users, RBAC, auth support, settings, sequences, master data, audit, notifications

CREATE TABLE IF NOT EXISTS companies (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    code            VARCHAR(30)  NULL,
    address         TEXT NULL,
    phone           VARCHAR(30)  NULL,
    email           VARCHAR(150) NULL,
    website         VARCHAR(200) NULL,
    tax_info        VARCHAR(255) NULL,
    logo            VARCHAR(100) NULL,
    currency        VARCHAR(10)  NOT NULL DEFAULT 'BDT',
    currency_symbol VARCHAR(10)  NOT NULL DEFAULT '৳',
    timezone        VARCHAR(64)  NOT NULL DEFAULT 'Asia/Dhaka',
    date_format     VARCHAR(20)  NOT NULL DEFAULT 'd M Y',
    invoice_footer  TEXT NULL,
    receipt_footer  TEXT NULL,
    theme_color     CHAR(7)      NOT NULL DEFAULT '#0f766e',
    status          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME NULL,
    UNIQUE KEY uq_companies_code (code),
    KEY idx_companies_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id          INT UNSIGNED NOT NULL,
    name                VARCHAR(150) NOT NULL,
    email               VARCHAR(150) NULL,
    phone               VARCHAR(30)  NULL,
    username            VARCHAR(50)  NOT NULL,
    password            VARCHAR(255) NOT NULL,
    status              ENUM('active','inactive') NOT NULL DEFAULT 'active',
    avatar              VARCHAR(100) NULL,
    session_version     INT UNSIGNED NOT NULL DEFAULT 1,
    last_login_at       DATETIME NULL,
    last_login_ip       VARCHAR(45) NULL,
    password_changed_at DATETIME NULL,
    created_by          INT UNSIGNED NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          DATETIME NULL,
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_phone (phone),
    KEY idx_users_company (company_id, status),
    CONSTRAINT fk_users_company FOREIGN KEY (company_id) REFERENCES companies(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- company_id NULL = global/system role available to every company
CREATE TABLE IF NOT EXISTS roles (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id  INT UNSIGNED NULL,
    name        VARCHAR(80)  NOT NULL,
    slug        VARCHAR(80)  NOT NULL,
    description VARCHAR(255) NULL,
    is_system   TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_roles_company_slug (company_id, slug),
    CONSTRAINT fk_roles_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name   VARCHAR(100) NOT NULL,
    module VARCHAR(50)  NOT NULL,
    label  VARCHAR(150) NOT NULL,
    UNIQUE KEY uq_permissions_name (name),
    KEY idx_permissions_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id       INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, role_id),
    KEY idx_ur_role (role_id),
    CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login history + throttling source
CREATE TABLE IF NOT EXISTS login_attempts (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    identifier  VARCHAR(150) NOT NULL,
    ip          VARCHAR(45)  NOT NULL,
    user_agent  VARCHAR(255) NULL,
    success     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_la_ident (identifier, ip, created_at),
    KEY idx_la_ip (ip, created_at),
    KEY idx_la_user (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at    DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pr_token (token_hash),
    KEY idx_pr_user (user_id),
    CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-company key/value settings
CREATE TABLE IF NOT EXISTS settings (
    company_id INT UNSIGNED NOT NULL,
    skey       VARCHAR(100) NOT NULL,
    svalue     TEXT NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (company_id, skey),
    CONSTRAINT fk_settings_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Concurrency-safe document numbering (row locked with SELECT ... FOR UPDATE)
CREATE TABLE IF NOT EXISTS number_sequences (
    company_id    INT UNSIGNED NOT NULL,
    seq_key       VARCHAR(30)  NOT NULL,
    prefix        VARCHAR(20)  NOT NULL,
    next_number   INT UNSIGNED NOT NULL DEFAULT 1,
    number_length TINYINT UNSIGNED NOT NULL DEFAULT 6,
    yearly_reset  TINYINT(1) NOT NULL DEFAULT 0,
    current_year  SMALLINT UNSIGNED NULL,
    PRIMARY KEY (company_id, seq_key),
    CONSTRAINT fk_seq_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generic lookup lists (lead sources/statuses, payment methods, departments, ...)
CREATE TABLE IF NOT EXISTS master_data (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    type       VARCHAR(40)  NOT NULL,
    name       VARCHAR(100) NOT NULL,
    code       VARCHAR(30)  NULL,
    color      CHAR(7)      NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    is_system  TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_md_name (company_id, type, name),
    KEY idx_md_type (company_id, type, is_active, sort_order),
    KEY idx_md_code (company_id, type, code),
    CONSTRAINT fk_md_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NULL,
    user_id    INT UNSIGNED NULL,
    action     VARCHAR(50) NOT NULL,
    module     VARCHAR(50) NOT NULL,
    record_id  BIGINT UNSIGNED NULL,
    old_values LONGTEXT NULL,
    new_values LONGTEXT NULL,
    ip         VARCHAR(45)  NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_company (company_id, created_at),
    KEY idx_audit_user (user_id, created_at),
    KEY idx_audit_module (module, record_id),
    KEY idx_audit_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NULL,
    channel    VARCHAR(20)  NOT NULL DEFAULT 'system',
    recipient  VARCHAR(150) NULL,
    title      VARCHAR(200) NOT NULL,
    body       TEXT NULL,
    link       VARCHAR(255) NULL,
    status     ENUM('queued','sent','failed','read') NOT NULL DEFAULT 'queued',
    error      VARCHAR(255) NULL,
    read_at    DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notif_user (user_id, status, created_at),
    KEY idx_notif_company (company_id, created_at),
    CONSTRAINT fk_notif_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
