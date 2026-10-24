-- 004: Employees, salary, expenses

CREATE TABLE IF NOT EXISTS employees (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id     INT UNSIGNED NOT NULL,
    employee_code  VARCHAR(30)  NOT NULL,
    user_id        INT UNSIGNED NULL,
    name           VARCHAR(150) NOT NULL,
    phone          VARCHAR(30)  NULL,
    email          VARCHAR(150) NULL,
    address        TEXT NULL,
    department_id  INT UNSIGNED NULL,
    designation_id INT UNSIGNED NULL,
    joining_date   DATE NULL,
    salary         DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'basic salary',
    status         ENUM('active','inactive','resigned') NOT NULL DEFAULT 'active',
    photo          VARCHAR(100) NULL,
    notes          TEXT NULL,
    created_by     INT UNSIGNED NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at     DATETIME NULL,
    UNIQUE KEY uq_employees_code (company_id, employee_code),
    KEY idx_employees_company (company_id, status, deleted_at),
    KEY idx_employees_dept (company_id, department_id),
    KEY idx_employees_phone (company_id, phone),
    CONSTRAINT fk_emp_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_emp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_emp_dept FOREIGN KEY (department_id) REFERENCES master_data(id),
    CONSTRAINT fk_emp_desig FOREIGN KEY (designation_id) REFERENCES master_data(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS salary_components (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id    INT UNSIGNED NOT NULL,
    name          VARCHAR(100) NOT NULL,
    type          ENUM('allowance','deduction','bonus','commission') NOT NULL,
    calc_type     ENUM('fixed','percent') NOT NULL DEFAULT 'fixed' COMMENT 'percent = % of basic',
    default_value DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    is_active     TINYINT(1) NOT NULL DEFAULT 1,
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sc_name (company_id, name),
    CONSTRAINT fk_sc_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-employee salary structure (overrides component default value)
CREATE TABLE IF NOT EXISTS employee_salary (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id  INT UNSIGNED NOT NULL,
    component_id INT UNSIGNED NOT NULL,
    value        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    UNIQUE KEY uq_es (employee_id, component_id),
    CONSTRAINT fk_es_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_es_component FOREIGN KEY (component_id) REFERENCES salary_components(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS salary_payments (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id        INT UNSIGNED NOT NULL,
    payment_number    VARCHAR(30) NOT NULL,
    employee_id       INT UNSIGNED NOT NULL,
    salary_month      CHAR(7) NOT NULL COMMENT 'YYYY-MM',
    basic             DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    allowances        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    bonus             DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    commission        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    deductions        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    net_salary        DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    details           LONGTEXT NULL COMMENT 'JSON component breakdown',
    payment_date      DATE NOT NULL,
    payment_method_id INT UNSIGNED NULL,
    reference         VARCHAR(150) NULL,
    notes             TEXT NULL,
    status            ENUM('paid','cancelled') NOT NULL DEFAULT 'paid',
    created_by        INT UNSIGNED NULL,
    cancelled_at      DATETIME NULL,
    cancelled_by      INT UNSIGNED NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_salpay_number (company_id, payment_number),
    KEY idx_salpay_emp (company_id, employee_id, salary_month, status),
    KEY idx_salpay_date (company_id, payment_date, status),
    CONSTRAINT fk_salpay_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_salpay_emp FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_salpay_method FOREIGN KEY (payment_method_id) REFERENCES master_data(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS expenses (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id        INT UNSIGNED NOT NULL,
    expense_number    VARCHAR(30) NOT NULL,
    category_id       INT UNSIGNED NOT NULL,
    project_id        INT UNSIGNED NULL,
    expense_date      DATE NOT NULL,
    amount            DECIMAL(15,2) NOT NULL,
    payment_method_id INT UNSIGNED NULL,
    reference         VARCHAR(150) NULL,
    description       TEXT NULL,
    attachment        VARCHAR(100) NULL,
    attachment_name   VARCHAR(200) NULL,
    attachment_mime   VARCHAR(100) NULL,
    status            ENUM('pending','approved','cancelled') NOT NULL DEFAULT 'approved',
    created_by        INT UNSIGNED NULL,
    approved_by       INT UNSIGNED NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at        DATETIME NULL,
    UNIQUE KEY uq_expenses_number (company_id, expense_number),
    KEY idx_expenses_date (company_id, expense_date, status),
    KEY idx_expenses_category (company_id, category_id),
    KEY idx_expenses_project (company_id, project_id),
    CONSTRAINT fk_exp_company FOREIGN KEY (company_id) REFERENCES companies(id),
    CONSTRAINT fk_exp_category FOREIGN KEY (category_id) REFERENCES master_data(id),
    CONSTRAINT fk_exp_project FOREIGN KEY (project_id) REFERENCES projects(id),
    CONSTRAINT fk_exp_method FOREIGN KEY (payment_method_id) REFERENCES master_data(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_migrations (
    migration  VARCHAR(100) PRIMARY KEY,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
