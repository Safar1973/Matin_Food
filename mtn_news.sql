CREATE DATABASE IF NOT EXISTS mtn_news
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

USE mtn_news;

CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

CREATE TABLE news_categories (
    news_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (news_id, category_id)
);

INSERT INTO categories (name) VALUES
('سياسة'),
('اقتصاد'),
('رياضة'),
('تكنولوجيا');

INSERT INTO news (title, content, image) VALUES
('خبر تجريبي', 'هذا خبر تجريبي لمشروع MTN News', 'news.jpg');
