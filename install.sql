-- Datenbank: `db`
-- --------------------------------------------------------

-- Tabellenstruktur für Tabelle `documents`
CREATE TABLE `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `file_path` text NOT NULL,
  `text_content` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename` (`filename`),
  FULLTEXT KEY `text_content` (`text_content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Tabellenstruktur für Tabelle `tags`
CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag_name` (`tag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Daten für Tabelle `tags`
INSERT INTO `tags` (`id`, `tag_name`) VALUES
(1, 'Hetzner'),
(2, 'Mahnung'),
(3, 'Miete'),
(4, 'o2'),
(5, 'Provider'),
(6, 'Rechnung'),
(7, 'Server'),
(8, 'Shopify'),
(9, 'Steuer'),
(10, 'Strato'),
(11, 'Telekom'),
(12, 'Telekommunikation'),
(13, 'Versicherungen'),
(14, 'Vodafone');

-- --------------------------------------------------------

-- Tabellenstruktur für Tabelle `document_tags`
CREATE TABLE `document_tags` (
  `document_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`document_id`, `tag_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `document_tags_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE,
  CONSTRAINT `document_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

-- Tabellenstruktur für Tabelle `users`
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(100) NOT NULL,
  `user_password` varchar(100) NOT NULL,
  `app_name` varchar(255) NOT NULL,
  `app_copyright` varchar(255) NOT NULL,
  `ocr_path` varchar(255) NOT NULL,
  `user_status` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `api_token` varchar(64) NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`user_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

COMMIT;