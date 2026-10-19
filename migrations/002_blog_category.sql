--
--  Blog categories, an article has at most one
-- -----
CREATE TABLE blog_category (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL,

  CONSTRAINT uq_blog_category_slug UNIQUE (slug)
)
  ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

ALTER TABLE blog_article
  ADD COLUMN category_id INT UNSIGNED NULL AFTER id,
  ADD CONSTRAINT fk_blog_article_category
    FOREIGN KEY (category_id) REFERENCES blog_category(id)
    ON DELETE SET NULL;
