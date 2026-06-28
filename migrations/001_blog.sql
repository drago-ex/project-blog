--
--  Blog
-- -----
CREATE TABLE blog_article (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(255) NOT NULL,
  title VARCHAR(255) NOT NULL,
  perex TEXT NULL,
  content LONGTEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'draft',
  comments_enabled TINYINT(1) NOT NULL DEFAULT 1,
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT uq_blog_article_slug UNIQUE (slug),
  CONSTRAINT chk_blog_article_status CHECK (status IN ('draft', 'published'))
)
  ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE blog_comment (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  article_id INT UNSIGNED NOT NULL,
  author_name VARCHAR(255) NOT NULL,
  author_email VARCHAR(255) NULL,
  content TEXT NOT NULL,
  approved TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT fk_blog_comment_article
    FOREIGN KEY (article_id) REFERENCES blog_article(id)
    ON DELETE CASCADE
)
  ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (name, value)
SELECT 'blog.comments_enabled', '1'
WHERE NOT EXISTS (
  SELECT 1 FROM settings WHERE name = 'blog.comments_enabled'
);
