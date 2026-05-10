-- 010_video_embeds.sql
-- Generic table for admin-curated video embeds (TikTok / YouTube / Facebook).
-- Admin pastes a public URL; the public landing page renders each as an iframe.
-- No API keys, no scraping — pure URL-to-embed transformation in the client.

CREATE TABLE IF NOT EXISTS video_embeds (
  id INT AUTO_INCREMENT PRIMARY KEY,
  platform VARCHAR(20) NOT NULL,
  section VARCHAR(50) NOT NULL,
  video_url TEXT NOT NULL,
  title VARCHAR(200) NULL,
  caption VARCHAR(500) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_video_embeds_section ON video_embeds(section, is_active, is_archived, sort_order);

-- Seed: the YouTube video the school shared as the initial "Latest on YouTube".
INSERT INTO video_embeds (platform, section, video_url, title, sort_order)
SELECT 'youtube', 'youtube_latest', 'https://youtu.be/AVYytcgF85Q', 'Latest from the school', 10
WHERE NOT EXISTS (
  SELECT 1 FROM video_embeds WHERE section='youtube_latest' AND video_url LIKE '%AVYytcgF85Q%'
);
