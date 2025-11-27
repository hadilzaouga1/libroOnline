-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mar. 18 nov. 2025 à 18:22
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_biblio`
--

-- --------------------------------------------------------

--
-- Structure de la table `books`
--

CREATE TABLE IF NOT EXISTS `books` (
  `id` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,3) NOT NULL,
  `available` tinyint(1) DEFAULT '1',
  `cover` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `language` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT 'FR',
  `gender` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_available` (`available`),
  KEY `idx_author` (`author`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `books`
--

INSERT INTO `books` (`id`, `title`, `author`, `category`, `price`, `available`, `cover`, `description`, `language`, `gender`, `created_at`, `updated_at`) VALUES
('b1', 'Le Petit Prince', 'Antoine de Saint-Exupéry', 'Classique', 72.500, 1, 'https://covers.openlibrary.org/b/id/11153254-L.jpg', 'Un conte poétique et philosophique.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b10', 'Crime et Châtiment', 'Fiodor Dostoïevski', 'Roman Classique', 42.500, 1, 'https://covers.openlibrary.org/b/id/844781-L.jpg', 'L\'histoire psychologique d\'un étudiant poussé au meurtre.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b11', 'Le Nom de la Rose', 'Umberto Eco', 'Policier Historique', 54.950, 1, 'https://covers.openlibrary.org/b/id/728555-L.jpg', 'Une enquête captivante dans une abbaye bénédictine au XIVe siècle.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b12', 'La Métamorphose', 'Franz Kafka', 'Absurde', 34.500, 1, 'https://covers.openlibrary.org/b/id/8993214-L.jpg', 'Le récit troublant de la transformation de Gregor Samsa en insecte géant.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b13', 'Cosmos', 'Carl Sagan', 'Science', 60.000, 1, 'https://covers.openlibrary.org/b/id/10292791-L.jpg', 'Une vulgarisation scientifique poétique et éclairante sur l\'univers.', 'EN', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b14', 'Le Portrait de Dorian Gray', 'Oscar Wilde', 'Roman Classique', 50.000, 1, 'https://covers.openlibrary.org/b/id/10667086-L.jpg', 'Un homme vend son âme pour que son portrait vieillisse à sa place.', 'EN', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b15', 'Fondation', 'Isaac Asimov', 'Science-Fiction', 64.950, 1, 'https://covers.openlibrary.org/b/id/9042253-L.jpg', 'Le début de la saga épique de la chute et du renouveau d\'un empire galactique.', 'EN', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b16', 'L\'Étranger', 'Albert Camus', 'Philosophie', 47.500, 1, 'https://covers.openlibrary.org/b/id/10700085-L.jpg', 'Un roman clé de l\'absurde et de l\'existentialisme.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b17', 'Le Guide du voyageur galactique', 'Douglas Adams', 'Science-Fiction Comique', 50.000, 1, 'https://covers.openlibrary.org/b/id/8573617-L.jpg', 'Les aventures loufoques d\'Arthur Dent après la destruction de la Terre.', 'EN', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b18', 'Harry Potter à l\'école des sorciers', 'J.K. Rowling', 'Jeunesse', 35.000, 1, 'https://covers.openlibrary.org/b/id/10237703-L.jpg', 'Le début de la saga du jeune sorcier à Poudlard.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b19', 'Germinal', 'Émile Zola', 'Roman', 67.500, 1, 'https://covers.openlibrary.org/b/id/840428-L.jpg', 'Tableau réaliste de la vie des mineurs dans le Nord de la France.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b2', 'Les Misérables', 'Victor Hugo', 'Roman Historique', 99.500, 1, 'https://covers.openlibrary.org/b/id/240726-L.jpg', 'Épopée sociale et fresque humaine sur la misère du XIXe siècle.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b20', 'La Théorie du tout', 'Stephen Hawking', 'Science', 49.500, 1, 'https://covers.openlibrary.org/b/id/8604321-L.jpg', 'Explication concise et accessible des grandes questions de la cosmologie.', 'EN', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b21', 'Le Meurtre de Roger Ackroyd', 'Agatha Christie', 'Policier', 52.500, 1, 'https://covers.openlibrary.org/b/id/8299187-L.jpg', 'Un des romans les plus célèbres d\'Hercule Poirot.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b22', 'L\'Art de la Guerre', 'Sun Tzu', 'Stratégie', 39.950, 1, 'https://covers.openlibrary.org/b/id/8348912-L.jpg', 'Traité militaire fondamental de la Chine ancienne.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b23', 'Le Penseur', 'René Descartes', 'Philosophie', 49.500, 0, 'https://covers.openlibrary.org/b/id/9842525-L.jpg', 'Méditations métaphysiques posant la base de la philosophie moderne.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b24', 'Les Fleurs du Mal', 'Charles Baudelaire', 'Poésie', 55.000, 1, 'https://covers.openlibrary.org/b/id/10183748-L.jpg', 'Recueil de poèmes majeur de la littérature française.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b25', 'Code Complete', 'Steve McConnell', 'Informatique', 75.000, 1, 'https://covers.openlibrary.org/b/id/1098670-L.jpg', 'Guide pratique et exhaustif pour la construction de logiciels de haute qualité.', 'EN', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b26', 'La Route', 'Cormac McCarthy', 'Dystopie', 74.500, 1, 'https://covers.openlibrary.org/b/id/9521360-L.jpg', 'Voyage poignant d\'un père et son fils dans un monde post-apocalyptique.', 'EN', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b27', 'La Vie mode d\'emploi', 'Georges Perec', 'Roman Expérimental', 137.500, 1, 'https://covers.openlibrary.org/b/id/9833777-L.jpg', 'Un puzzle littéraire décrivant la vie des habitants d\'un immeuble parisien.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b28', 'Homo Deus', 'Yuval Noah Harari', 'Essai', 70.000, 1, 'https://covers.openlibrary.org/b/id/10786522-L.jpg', 'Exploration de l\'avenir de l\'humanité face aux technologies et à l\'immortalité.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b29', 'Vingt mille lieues sous les mers', 'Jules Verne', 'Aventure', 54.500, 1, 'https://covers.openlibrary.org/b/id/10793739-L.jpg', 'Le voyage du professeur Aronnax à bord du Nautilus du Capitaine Nemo.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b3', 'Candide', 'Voltaire', 'Philosophie', 43.750, 0, 'https://covers.openlibrary.org/b/id/10982248-L.jpg', 'Satire philosophique contre l\'optimisme béat.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b30', 'Dictionnaire philosophique', 'Voltaire', 'Philosophie', 72.500, 1, 'https://covers.openlibrary.org/b/id/9839447-L.jpg', 'Recueil d\'articles abordant la religion, la morale et la politique.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b4', 'Introduction à l\'algorithmique', 'Thomas H. Cormen', 'Informatique', 225.000, 1, 'https://covers.openlibrary.org/b/id/8091016-L.jpg', 'Référence fondamentale en théorie des algorithms et des structures de données.', 'EN', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b5', '1984', 'George Orwell', 'Science-Fiction', 36.000, 1, 'https://covers.openlibrary.org/b/id/826282-L.jpg', 'Un classique de la dystopie, explorant la surveillance et la manipulation.', 'EN', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b6', 'Orgueil et Préjugés', 'Jane Austen', 'Roman Classique', 44.500, 1, 'https://covers.openlibrary.org/b/id/8690956-L.jpg', 'Un portrait spirituel des mœurs de la gentry anglaise du XIXe siècle.', 'EN', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b7', 'Sapiens : Une brève histoire de l\'humanité', 'Yuval Noah Harari', 'Essai', 175.000, 1, 'https://covers.openlibrary.org/b/id/10705703-L.jpg', 'Une exploration fascinante de l\'histoire humaine, de l\'âge de pierre à l\'ère moderne.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b8', 'Le Seigneur des Anneaux', 'J.R.R. Tolkien', 'Fantaisie', 42.500, 0, 'https://covers.openlibrary.org/b/id/10398679-L.jpg', 'L\'épopée fondatrice de la fantasy moderne.', 'FR', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05'),
('b9', 'Dune', 'Frank Herbert', 'Science-Fiction', 105.000, 1, 'https://covers.openlibrary.org/b/id/12836262-L.jpg', 'Un chef-d\'œuvre d\'écologie, de politique et de religion sur la planète Arrakis.', 'EN', NULL, '2025-11-18 18:21:05', '2025-11-18 18:21:05');

-- --------------------------------------------------------

--
-- Structure de la table `cart`
--

CREATE TABLE IF NOT EXISTS `cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `book_id` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('buy','borrow') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int DEFAULT '1',
  `price` decimal(10,3) DEFAULT NULL,
  `date_borrow` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `book_id` (`book_id`),
  KEY `idx_user_email` (`user_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reviews`
--

CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `book_id` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` int NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `date` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_email` (`user_email`),
  KEY `idx_book_id` (`book_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `transactions`
--

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_amount` decimal(10,3) NOT NULL,
  `promo_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('completed','pending','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'completed',
  `transaction_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_email` (`user_email`),
  KEY `idx_transaction_date` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(1, 'Administrateur', 'admin@libroonline.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '55 14 13 55', 'Tunis', '2025-11-18 18:21:05', '2025-11-18 18:21:05');

-- --------------------------------------------------------

--
-- Structure de la table `user_library`
--

CREATE TABLE IF NOT EXISTS `user_library` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `book_id` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('purchase','borrow') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` bigint NOT NULL,
  `expiry_date` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_email` (`user_email`),
  KEY `idx_book_id` (`book_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `wishlist`
--

CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `book_id` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wishlist` (`user_email`,`book_id`),
  KEY `book_id` (`book_id`),
  KEY `idx_user_email` (`user_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE SET NULL;

--
-- Contraintes pour la table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_library`
--
ALTER TABLE `user_library`
  ADD CONSTRAINT `user_library_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_library_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_email`) REFERENCES `users` (`email`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;