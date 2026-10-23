--  Privileges of the blog administration for drago-ex/project-permission
-- ---------------------------------------------------------------------
-- Imported by hand into the tables of project-permission (see the README), so roles can be given these privileges
-- in the administration. They are what the Authorization trait checks: a page needs "{action}-read",
-- a component's signals "{component}-read" (sorting, paging) or "{component}-write".
INSERT IGNORE INTO resources (resource, privilege, description) VALUES
    ('Backend:Blog', 'default-read', 'View articles'),
    ('Backend:Blog', 'articles-read', 'Sort and filter articles'),
    ('Backend:Blog', 'articles-write', 'Manage articles'),
    ('Backend:Blog', 'categories-read', 'View categories'),
    ('Backend:Blog', 'categories-write', 'Manage categories'),
    ('Backend:Blog', 'comments-read', 'View comments'),
    ('Backend:Blog', 'comments-write', 'Approve and delete comments');
