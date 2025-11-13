
-- Clean tables (adjust FK order if needed)
TRUNCATE TABLE vacation_requests RESTART IDENTITY CASCADE;
TRUNCATE TABLE users RESTART IDENTITY CASCADE;

-- Insert a manager (password: manager123)
INSERT INTO users (name, email, employee_code, password_hash, role, current_jti, created_at)
VALUES (
  'Manager One',
  'manager@example.com',
  '0000001',
  crypt('manager123', gen_salt('bf', 10)),
  'manager',
  NULL,
  NOW()
);

-- Insert an employee (password: employee123)
INSERT INTO users (name, email, employee_code, password_hash, role, current_jti, created_at)
VALUES (
  'Employee One',
  'employee@example.com',
  '0000002',
  crypt('employee123', gen_salt('bf', 10)),
  'employee',
  NULL,
  NOW()
);