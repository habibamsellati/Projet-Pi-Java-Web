-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 22 fév. 2026 à 19:22
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `piprojet1`
--

-- --------------------------------------------------------

--
-- Structure de la table `action_historique`
--

CREATE TABLE `action_historique` (
  `id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_user_email` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `admin_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `article`
--

CREATE TABLE `article` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` longtext NOT NULL,
  `date` date NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL,
  `categorie` varchar(100) DEFAULT NULL,
  `artisan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `likes` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `article`
--

INSERT INTO `article` (`id`, `titre`, `contenu`, `date`, `image`, `prix`, `categorie`, `artisan_id`, `user_id`, `likes`) VALUES
(1, 'pppppppppppp¨H', 'njez\"vflcqsKJidR', '2026-02-15', NULL, 5050.00, 'Decoration', 3, 3, 0),
(3, 'kjbe;ZF?/QS§XCJ/Kdf', 'jkbdsv<esf:dbcxn,.FTEZ', '2026-02-16', '12326b71-ade4-41f0-95ca-2cd287a15d11-6992e06d81f75.jpg', 500.00, 'Decoration', 3, 3, 0),
(4, 'klgerqjdv xuh', 'z -bry\"y <\"(v(tgvr', '2026-02-21', NULL, 700.00, 'Textile', 3, 3, 1),
(5, 'dkndcZMBJ§ZV', 'KEJFZJezge', '2026-02-21', '611010928-1167907675330380-6695940799423015096-n-69999bc4ed5fd.jpg', 900.00, 'Artisanat', 3, 3, 1),
(6, 'plastique', ',lk FHI%RVIOHEri', '2026-02-21', 'art-from-beach-debris-69999bde1f389.jpg', 900.00, 'Decoration', 3, 3, 0),
(7, 'test3', 'lmojiùphfvGRN%EKIP', '2026-02-21', 'Art-collection-69999bf225c21.jpg', 800.00, 'Artisanat', 3, 3, 0),
(8, 'M.JB§VH', 'FTDRNSTERQZGSHWDXJFC', '2026-02-21', 'Art-collection-1-69999c0d02bf9.jpg', 600.00, 'Artisanat', 3, 3, 0);

-- --------------------------------------------------------

--
-- Structure de la table `commande`
--

CREATE TABLE `commande` (
  `id` int(11) NOT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `datecommande` datetime NOT NULL,
  `statut` varchar(255) NOT NULL,
  `total` double NOT NULL,
  `adresselivraison` varchar(255) NOT NULL,
  `modepaiement` varchar(255) NOT NULL,
  `client_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commande`
--

INSERT INTO `commande` (`id`, `numero`, `datecommande`, `statut`, `total`, `adresselivraison`, `modepaiement`, `client_id`) VALUES
(1, '94916746', '2026-02-15 22:28:53', 'en_attente', 10050, 'vfgteyèj\"z', 'especes', 1),
(2, '94916746', '2026-02-16 09:33:33', 'en_attente', 5050, 'ariana', 'especes', 9),
(3, '9491674', '2026-02-16 10:18:12', 'en_attente', 500, 'vfgteyèj\"z', 'especes', 9),
(4, '97967146', '2026-02-20 21:10:49', 'en_attente', 500, 'zaghouan', 'especes', 2),
(5, '54454545', '2026-02-20 22:35:20', 'en_attente', 5050, '54', 'especes', 2),
(6, '9796714', '2026-02-21 12:44:28', 'en_attente', 5050, 'zaghouan', 'especes', 2),
(7, '97967146', '2026-02-22 17:11:43', 'en_attente', 700, 'zaghouan', 'especes', 2);

-- --------------------------------------------------------

--
-- Structure de la table `commande_article`
--

CREATE TABLE `commande_article` (
  `commande_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commande_article`
--

INSERT INTO `commande_article` (`commande_id`, `article_id`) VALUES
(1, 1),
(2, 1),
(3, 3),
(4, 3),
(5, 1),
(6, 1),
(7, 4);

-- --------------------------------------------------------

--
-- Structure de la table `commentaire`
--

CREATE TABLE `commentaire` (
  `id` int(11) NOT NULL,
  `contenu` varchar(255) NOT NULL,
  `datepub` datetime NOT NULL,
  `article_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `likes` int(11) NOT NULL DEFAULT 0,
  `dislikes` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commentaire`
--

INSERT INTO `commentaire` (`id`, `contenu`, `datepub`, `article_id`, `user_id`, `parent_id`, `likes`, `dislikes`) VALUES
(1, 'salut frere😍😍', '2026-02-18 19:10:23', 3, 2, NULL, 0, 0),
(2, 'bonjour', '2026-02-19 20:36:27', 3, 3, 1, 0, 0),
(3, 'bonjour', '2026-02-19 20:37:44', 3, 2, NULL, 0, 0),
(5, 'salut😀', '2026-02-21 12:45:56', 3, 2, NULL, 0, 0),
(6, 'frgdvsf', '2026-02-21 14:15:58', 4, 2, NULL, 5, 3),
(7, 'cdkofrigtk,!vf<gùknbf❤️', '2026-02-21 14:24:50', 4, 2, NULL, 0, 1);

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20260201120619', '2026-02-18 18:59:02', 4),
('DoctrineMigrations\\Version20260202105846', NULL, NULL),
('DoctrineMigrations\\Version20260202115219', NULL, NULL),
('DoctrineMigrations\\Version20260202120612', NULL, NULL),
('DoctrineMigrations\\Version20260202195906', NULL, NULL),
('DoctrineMigrations\\Version20260202201405', NULL, NULL),
('DoctrineMigrations\\Version20260202204305', NULL, NULL),
('DoctrineMigrations\\Version20260202204943', NULL, NULL),
('DoctrineMigrations\\Version20260202210342', NULL, NULL),
('DoctrineMigrations\\Version20260202210600', NULL, NULL),
('DoctrineMigrations\\Version20260207120000', NULL, NULL),
('DoctrineMigrations\\Version20260214120000', NULL, NULL),
('DoctrineMigrations\\Version20260214143226', NULL, NULL),
('DoctrineMigrations\\Version20260215163453', NULL, NULL),
('DoctrineMigrations\\Version20260215170000', NULL, NULL),
('DoctrineMigrations\\Version20260215180000', NULL, NULL),
('DoctrineMigrations\\Version20260215190000', NULL, NULL),
('DoctrineMigrations\\Version20260217112000', NULL, NULL),
('DoctrineMigrations\\Version20260217130000', NULL, NULL),
('DoctrineMigrations\\Version20260217143000', NULL, NULL),
('DoctrineMigrations\\Version20260217235900', '2026-02-18 18:59:14', 156),
('DoctrineMigrations\\Version20260218001000', '2026-02-18 18:59:20', 84),
('DoctrineMigrations\\Version20260218120000', '2026-02-18 19:09:17', 142),
('DoctrineMigrations\\Version20260219214623', NULL, NULL),
('DoctrineMigrations\\Version20260219214657', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `evenement`
--

CREATE TABLE `evenement` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `artisan` varchar(255) DEFAULT NULL,
  `description` longtext NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `lieu` varchar(255) NOT NULL,
  `capacite` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `type_art` varchar(255) DEFAULT NULL,
  `statut` varchar(255) NOT NULL,
  `theme` varchar(255) DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `evenement`
--

INSERT INTO `evenement` (`id`, `nom`, `artisan`, `description`, `date_debut`, `date_fin`, `lieu`, `capacite`, `created_at`, `type_art`, `statut`, `theme`, `prix`, `image`) VALUES
(1, 'kkdihcsfezT4J', 'DEFzb', 'dcx vsnkgr', '2026-02-17 23:15:00', '2026-02-22 23:15:00', 'rades', 25, '2026-02-21 21:59:20', NULL, 'brouillon', NULL, NULL, NULL),
(2, 'ddge,lkc\'klzexf', 'xscnear', 'gvrfaeroiù\"', '2026-02-22 23:18:00', '2026-02-26 23:18:00', 'k,kxijhscuo je', 6, '2026-02-21 21:59:20', NULL, 'brouillon', NULL, NULL, NULL),
(3, 'atelier ceramique', 'ouihibi ranim', 'Découvrez l\'essence même du geste artistique à travers cet atelier de céramique. Entre tradition et innovation, nous vous proposons un voyage au cœur de la création pure, où chaque détail raconte une histoire de passion et d\'excellence.', '2026-02-21 22:11:00', '2026-02-25 22:09:00', 'ariana', 12, '2026-02-21 22:10:13', 'Céramique', 'brouillon', 'traditionnelle', 55.00, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `evenement_image`
--

CREATE TABLE `evenement_image` (
  `id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `evenement_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `evenement_prediction`
--

CREATE TABLE `evenement_prediction` (
  `id` int(11) NOT NULL,
  `taux_predicted` double NOT NULL,
  `date_prediction` datetime NOT NULL,
  `evenement_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `livraison`
--

CREATE TABLE `livraison` (
  `id` int(11) NOT NULL,
  `datelivraison` datetime NOT NULL,
  `addresslivraison` varchar(255) NOT NULL,
  `statutlivraison` varchar(20) NOT NULL,
  `note_livreur` int(11) DEFAULT NULL,
  `livreur_id` int(11) DEFAULT NULL,
  `lat` double DEFAULT NULL,
  `lng` double DEFAULT NULL,
  `commande_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `livraison`
--

INSERT INTO `livraison` (`id`, `datelivraison`, `addresslivraison`, `statutlivraison`, `note_livreur`, `livreur_id`, `lat`, `lng`, `commande_id`) VALUES
(1, '2026-02-15 22:29:00', 'sds', 'en_attente', NULL, NULL, NULL, NULL, NULL),
(6, '2026-02-15 23:22:00', 'hhhhhhhhhhhh', 'livre', NULL, 10, NULL, NULL, NULL),
(7, '2026-02-15 23:22:00', 'hhhhhhhhhhhh', 'en_cours', NULL, 10, NULL, NULL, NULL),
(8, '2026-02-16 09:34:00', ';njgf,tdshwsxdf;g:hl', 'livre', NULL, 10, NULL, NULL, NULL),
(10, '2026-02-16 10:20:00', 'pppppppppppppppp', 'en_attente', NULL, 10, NULL, NULL, NULL),
(13, '2026-02-20 22:47:00', 'dfqsdfq', 'livre', NULL, 10, NULL, NULL, 2),
(14, '2026-02-20 22:47:00', 'manouba', 'livre', NULL, 10, NULL, NULL, 5),
(15, '2026-02-20 23:05:00', 'ennaser ariana', 'livre', NULL, 10, 36.8605339, 10.164253, 4);

-- --------------------------------------------------------

--
-- Structure de la table `produit`
--

CREATE TABLE `produit` (
  `id` int(11) NOT NULL,
  `nomproduit` varchar(150) NOT NULL,
  `typemateriau` varchar(100) DEFAULT NULL,
  `etat` varchar(80) DEFAULT NULL,
  `quantite` int(11) NOT NULL,
  `origine` varchar(120) DEFAULT NULL,
  `impactecologique` double DEFAULT NULL,
  `dateajout` datetime DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `added_by_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `produit`
--

INSERT INTO `produit` (`id`, `nomproduit`, `typemateriau`, `etat`, `quantite`, `origine`, `impactecologique`, `dateajout`, `description`, `added_by_id`, `image`) VALUES
(1, 'Carton', 'Durable', NULL, 6, 'rfevgth(byjè', 12, '2026-02-15 22:31:08', 'x,kdfa gtrnj!\" T.csw', 1, NULL),
(2, 'Carton', 'Durable', 'Moyen', 9, 'c FGRNNBF', 15, '2026-02-15 22:31:31', '..KCL SFJE', 1, NULL),
(3, 'Carton', 'Durable', 'Moyen', 9, 'c FGRNNBF', 15, '2026-02-15 22:31:33', '..KCL SFJE', 1, NULL),
(4, 'Carton', 'Durable', 'Bon', 6, 'XSC?L?VD.§C<', 14, '2026-02-16 09:35:02', 'DRIGJKMSV?X%O<PVJKGQE', 9, NULL),
(6, 'Verre', 'Écologique', 'Moyen', 7, 'c FGRNNBF', 16, '2026-02-16 10:11:50', ',ksjjsvc\nxwhjn:v<dmhiO', 9, NULL),
(7, 'Verre', 'Écologique', 'Mauvais', 100, 'bouteille', 70, '2026-02-21 21:15:05', 'verre collecter des ancienne bouteille', 2, NULL),
(8, 'Métal', 'Naturel', 'Moyen', 100, 'cannette', 70, '2026-02-21 21:28:22', 'article de decoration maison', 2, NULL),
(9, 'Plastique', 'Écologique', 'Bon', 5, 'bouteille d\'eau', 70, '2026-02-21 21:41:28', 'article decoratif', 2, '/uploads/ai_images/ai_1771706486_154886.jpg'),
(10, 'Bois', 'Naturel', 'Bon', 74, 'bouteille', 70, '2026-02-21 21:51:13', 'produit en bois pour decoration', 2, '/uploads/ai_images/ai_1771707069_164991.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `proposition`
--

CREATE TABLE `proposition` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `datesoumision` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `produit_id` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `prix_propose` double DEFAULT NULL,
  `client_phone` varchar(20) DEFAULT NULL,
  `statut` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `proposition`
--

INSERT INTO `proposition` (`id`, `titre`, `description`, `datesoumision`, `user_id`, `produit_id`, `image`, `prix_propose`, `client_phone`, `statut`) VALUES
(1, 'yjukJFQEHDCSVRF', 'tgza uék,dvn lgrVFBEBJK Cv', '2026-02-15 23:16:52', 3, 2, NULL, NULL, NULL, ''),
(2, 'LKQEbt!ln', 'wswdxfcgbhj,lk;m:ù', '2026-02-15 23:17:10', 3, 1, NULL, NULL, NULL, ''),
(3, 'decor maison en verre', 'decor maison en verre couleur rouge', '2026-02-21 21:21:23', 2, 7, NULL, 45, '+21697967146', 'en_attente'),
(4, 'decor maison en verre', 'decor maison en verre', '2026-02-21 21:31:08', 2, 6, NULL, NULL, '+21697967146', 'en_attente'),
(5, 'decor maison en plastique', 'decor maison en plastique', '2026-02-21 21:43:43', 2, 9, '/uploads/ai_images/ai_1771706597_894088.jpg', 57.75, '+21699377945', 'en_attente'),
(6, 'decor maison en verre', 'decor maison en plastique', '2026-02-21 21:44:21', 2, 3, '/uploads/ai_images/ai_1771706655_343021.jpg', 47.25, '99377945', 'terminee'),
(7, 'decor maison en bois', 'decor maison en bois', '2026-02-21 21:52:27', 2, 10, '/uploads/ai_images/ai_1771707105_991791.jpg', 126, '+21699377945', 'en_attente');

-- --------------------------------------------------------

--
-- Structure de la table `reclamation`
--

CREATE TABLE `reclamation` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `descripition` longtext DEFAULT NULL,
  `datecreation` datetime NOT NULL,
  `statut` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_notification_sent` datetime DEFAULT NULL,
  `video_call_link` varchar(500) DEFAULT NULL,
  `video_call_scheduled_at` datetime DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reclamation`
--

INSERT INTO `reclamation` (`id`, `titre`, `descripition`, `datecreation`, `statut`, `user_id`, `last_notification_sent`, `video_call_link`, `video_call_scheduled_at`, `image`) VALUES
(1, 'Probleme lie au site', 'Dear Support Team,\n\nI hope this message finds you well. I am writing to formally report and seek urgent assistance regarding a recurring and ongoing issue with my home internet service associated with Account #9930412. The problem began on February 10, 2026, and has persisted consistently since that date without any lasting resolution.\n\nSpecifically, my internet connection has been extremely unstable, with frequent service interruptions occurring multiple times throughout the day. These disruptions vary in duration—sometimes lasting only a few minutes, while at other times extending much longer. Regardless of the length, the interruptions significantly affect my ability to use the service reliably.\n\nDespite repeatedly restarting the router, performing power cycles, checking all cable connections, and ensuring that the equipment is properly positioned and ventilated, the issue continues to occur. I have also attempted to minimize network congestion by disconnecting non-essential devices, but this has not improved the stability of the connection. The problem affects both wired and wireless connections, which suggests that it may not be limited to a Wi-Fi signal issue.\n\nThis instability has caused considerable inconvenience. I rely heavily on a stable internet connection for work-related responsibilities, virtual meetings, file transfers, and communication. Additionally, other members of my household depend on the service for educational purposes, streaming, and general daily activities. The ongoing disruptions have resulted in missed meetings, interrupted downloads, buffering during important sessions, and overall reduced productivity.\n\nGiven that the issue has persisted for an extended period, I kindly request a thorough investigation into the matter. I would appreciate it if you could:\n\nRun a remote diagnostic test on my line to identify any signal irregularities or outages in my area.\n\nConfirm whether there are known service disruptions or maintenance activities affecting my neighborhood.\n\nArrange for a technician visit, if necessary, to inspect the physical connection, modem/router, and any external cabling.\n\nAdvise whether a modem/router replacement or firmware update may be required.\n\nPlease also let me know if there are any additional troubleshooting steps I should perform on my end while this issue is being reviewed.\n\nGiven the prolonged service disruption, I would also like to inquire about the possibility of receiving a service credit or adjustment to my billing statement for the affected period.\n\nI would greatly appreciate prompt attention to this matter, as the reliability of my internet service is essential to my daily responsibilities. Please feel free to contact me at your earliest convenience via phone or email if further information is required.\n\nThank you in advance for your assistance. I look forward to your prompt response and a swift resolution to this issue.\n\nSincerely,', '2026-02-15 22:27:40', 'en_attente', 3, '2026-02-20 22:32:48', NULL, NULL, NULL),
(2, 'Probleme lie au site', 'ghujfsvhiuheqthrizv', '2026-02-15 23:17:56', 'en_attente', 3, '2026-02-20 22:32:54', NULL, NULL, NULL),
(3, 'Probleme de commande', 'dcs', '2026-02-15 23:18:06', 'en_attente', 3, '2026-02-20 22:32:55', NULL, NULL, NULL),
(4, 'Probleme lie au site', 'jkfdgr:mjfdmhobk<qer', '2026-02-16 09:34:00', 'en_attente', 9, '2026-02-20 22:32:56', 'reclamation-4-0661751ef1b31219', '2026-02-20 21:28:12', NULL),
(5, 'Probleme de commande', 'hdeZFJKDS§CLFQER', '2026-02-16 09:34:09', 'en_attente', 9, '2026-02-20 22:32:58', 'reclamation-5-6adad808a76fcb55', '2026-02-20 21:30:16', NULL),
(6, 'Probleme de commande', 'hdeZFJKDS§CLFQER', '2026-02-16 09:34:10', 'en_attente', 9, '2026-02-20 22:32:58', 'reclamation-6-d6bf3e4290d340d8', '2026-02-20 21:17:07', NULL),
(7, 'Reclamation technique', 'Dear Support Team,\n\nI am contacting you regarding a recurring issue with my home internet service (Account #9930412). Since February 10, 2026, the connection has been unstable, with frequent interruptions throughout the day.\n\nDespite restarting the router', '2026-02-20 20:53:27', 'en_attente', 2, NULL, 'https://meet.jit.si/reclamation-7-222f8c3f5adc0c2c', '2026-02-20 20:59:12', NULL),
(8, 'Reclamation technique', 'f sdgsdfg sdfg sdfg sdfgsdfgdsf fsd', '2026-02-20 21:24:15', 'en_attente', 2, NULL, 'reclamation-8-0e46b1483c8209db', '2026-02-20 21:24:31', NULL),
(9, 'Reclamation technique', 'Dear Support Team,\n\nI hope this message finds you well. I am writing to formally report and seek urgent assistance regarding a recurring and ongoing issue with my home internet service associated with Account #9930412. The problem began on February 10, 2026, and has persisted consistently since that date without any lasting resolution.\n\nSpecifically, my internet connection has been extremely unstable, with frequent service interruptions occurring multiple times throughout the day. These disruptions vary in duration—sometimes lasting only a few minutes, while at other times extending much longer. Regardless of the length, the interruptions significantly affect my ability to use the service reliably.\n\nDespite repeatedly restarting the router, performing power cycles, checking all cable connections, and ensuring that the equipment is properly positioned and ventilated, the issue continues to occur. I have also attempted to minimize network congestion by disconnecting non-essential devices, but this has not improved the stability of the connection. The problem affects both wired and wireless connections, which suggests that it may not be limited to a Wi-Fi signal issue.\n\nThis instability has caused considerable inconvenience. I rely heavily on a stable internet connection for work-related responsibilities, virtual meetings, file transfers, and communication. Additionally, other members of my household depend on the service for educational purposes, streaming, and general daily activities. The ongoing disruptions have resulted in missed meetings, interrupted downloads, buffering during important sessions, and overall reduced productivity.\n\nGiven that the issue has persisted for an extended period, I kindly request a thorough investigation into the matter. I would appreciate it if you could:\n\nRun a remote diagnostic test on my line to identify any signal irregularities or outages in my area.\n\nConfirm whether there are known service disruptions or maintenance activities affecting my neighborhood.\n\nArrange for a technician visit, if necessary, to inspect the physical connection, modem/router, and any external cabling.\n\nAdvise whether a modem/router replacement or firmware update may be required.\n\nPlease also let me know if there are any additional troubleshooting steps I should perform on my end while this issue is being reviewed.\n\nGiven the prolonged service disruption, I would also like to inquire about the possibility of receiving a service credit or adjustment to my billing statement for the affected period.\n\nI would greatly appreciate prompt attention to this matter, as the reliability of my internet service is essential to my daily responsibilities. Please feel free to contact me at your earliest convenience via phone or email if further information is required.\n\nThank you in advance for your assistance. I look forward to your prompt response and a swift resolution to this issue.\n\nSincerely,', '2026-02-21 22:44:20', 'en_attente', 2, NULL, NULL, NULL, 'Artcollection1-699a2735d35be.jpg'),
(10, 'Reclamation technique', 'http://127.0.0.1:8000/reclamation/newhttp://127.0.0.1:8000/reclamation/n\newhttp://127.0.0.1:8000/reclamation/newhttp://127.0.0.1:8000/reclamation/ne\nwhttp://127.0.0.1:8000/reclamation/newhttp://127.0.0.1:8000/reclamation$/newhttp://127.0.0.1:8000/reclamation/newhttp://127.0.0.1:8000/reclamation/newhttp://127.0.0.1:8000/reclamation/newhttp://127.0.0.1:8000/reclamatio$\nn/newhttp://127.0.0.1:8000/reclamation/newhttp://127.0.0.1:8000/reclamation/newhttp://127.0.0.1:8000/reclamation/newhttp://127.0.0.1:8000/reclamat\ni\non/newhttp://127.0.0.1:8000/reclamation/newhttp://127.0.0.1:8000/reclama\ntion/newhttp://127.0.0.1:8000/reclamation/newhttp://127.0.0.1:8000/reclamation/newhttp://127.0.0.1:8000/reclamation/newhttp://127.0.0.1:8000/reclamation/newhttp://127.0.0.1:8000/reclamation/new', '2026-02-21 22:45:22', 'en_attente', 2, NULL, NULL, NULL, 'CulturaInquietaenlaConferenciadelosocanosdeONUenLisboaconunainstalacinartsticamedioambiental-CulturaInquieta-699a27733ae4a.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `reponse_reclamation`
--

CREATE TABLE `reponse_reclamation` (
  `id` int(11) NOT NULL,
  `contenu` varchar(255) NOT NULL,
  `datereponse` datetime NOT NULL,
  `reclamation_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reservation`
--

CREATE TABLE `reservation` (
  `id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `date_reservation` datetime NOT NULL,
  `statut` varchar(20) NOT NULL,
  `evenement_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nb_places` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reservation`
--

INSERT INTO `reservation` (`id`, `created_at`, `date_reservation`, `statut`, `evenement_id`, `user_id`, `nb_places`) VALUES
(1, '2026-02-16 09:52:35', '2026-02-28 09:52:00', 'confirme', 1, NULL, 1),
(2, '2026-02-16 09:52:48', '2026-02-27 09:52:00', 'annule', 2, NULL, 1),
(3, '2026-02-16 09:53:36', '2026-02-28 09:53:00', 'annule', 1, NULL, 1),
(4, '2026-02-21 22:12:45', '2026-02-21 22:12:00', 'confirme', 3, 2, 1);

-- --------------------------------------------------------

--
-- Structure de la table `suivi_livraison`
--

CREATE TABLE `suivi_livraison` (
  `id` int(11) NOT NULL,
  `datesuivi` datetime NOT NULL,
  `etat` varchar(255) NOT NULL,
  `localisation` varchar(255) NOT NULL,
  `commentaire` longtext DEFAULT NULL,
  `livraison_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `suivi_livraison`
--

INSERT INTO `suivi_livraison` (`id`, `datesuivi`, `etat`, `localisation`, `commentaire`, `livraison_id`) VALUES
(1, '2026-02-15 23:00:00', 'En cours', 'sdf', 'y(hgbfresdfr', 1),
(2, '2026-02-15 23:01:00', 'En cours', 'qrgthbzyjnèi\"', 'thabyjnuh,:oàm', 1);

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `motdepasse` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `datecreation` datetime NOT NULL,
  `statut` varchar(255) NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `reset_token_hash` varchar(128) DEFAULT NULL,
  `reset_token_created_at` datetime DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `reset_token_request_ip` varchar(45) DEFAULT NULL,
  `oauth_provider` varchar(30) DEFAULT NULL,
  `oauth_provider_id` varchar(255) DEFAULT NULL,
  `sexe` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `nom`, `prenom`, `email`, `motdepasse`, `role`, `datecreation`, `statut`, `deleted_at`, `reset_token_hash`, `reset_token_created_at`, `reset_token_expires_at`, `reset_token_request_ip`, `oauth_provider`, `oauth_provider_id`, `sexe`, `avatar`, `telephone`) VALUES
(1, 'livreur ', 'bouhmid', 'sbaiemna04@gmail.com', '$2y$10$u6iebD112XWAZMOQ51b3NezEIecF7ZTz4o/SDsZKttcJ55MZAhR9O', 'ADMIN', '2026-02-15 21:36:37', 'actif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'issra', 'benghrib', 'issrabenghrib04@gmail.com', '$2y$10$Y3pQRQn.OMp/q8lWjG43Ue38U0g1kd3SMjLIrUruIdd6RdZpF2cWa', 'CLIENT', '2026-02-15 22:22:26', 'actif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'ranim', 'ouihibi', 'ranimouihibi@gmail.com', '$2y$10$u6iebD112XWAZMOQ51b3NezEIecF7ZTz4o/SDsZKttcJ55MZAhR9O', 'ARTISANT', '2026-02-15 22:23:34', 'actif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'mareim', 'ferchichi', 'maryemf239@gmail.com', '$2y$10$/dZuQTf.N7sF9a.iB2PflOg5a9WLmlVzfns/FfUQE7W.h5rEUrxlm', 'ADMIN', '2026-02-15 22:33:36', 'actif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'Article', 'Responsable', 'article', '$2y$10$em1ruXqipLweliQUjg7GJuOxHHSkT9jvboGD/ijGqNbXffh8d0E42', 'RESPONSABLE', '2026-02-15 22:37:12', 'actif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'Produit Recyclable', 'Responsable', 'produit', '$2y$10$eDslG24CV.Wr.rvxd7DPmuzphTv2JzZdMOHac4VlSs4eFXXL4DFfa', 'RESPONSABLE', '2026-02-15 23:00:20', 'actif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Reclamation', 'Responsable', 'reclamation', '$2y$10$nfNexj3hoWUJz87k6lATR.A2ORwcGOIQfW2BICXO9pj5phhPmK3le', 'RESPONSABLE', '2026-02-15 23:01:58', 'actif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'saja', 'bengh', 'sajaabenghrib@gmail.com', '$2y$10$HJDyl3pQStpeEfaJxbUSeO2ZbikHm3HUWdvk8tw0oGKtm25WD1JIe', 'CLIENT', '2026-02-15 23:08:57', 'inactif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'saja', 'benghrib', 'sajaben6@gmail.com', '$2y$10$PBSNrnDMSF8u7GGaY02n1OxjJuSYaGoDMUxAsDRTzxRcYs7gS5IRu', 'CLIENT', '2026-02-15 23:11:49', 'actif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'boubou', 'sbai', 'iyedbenmabrouk309@gmail.com', '$2y$10$u6iebD112XWAZMOQ51b3NezEIecF7ZTz4o/SDsZKttcJ55MZAhR9O', 'LIVREUR', '2026-02-15 23:21:17', 'actif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'habiba', 'msms', 'msellatihabiba7@gmail.com', '$2y$10$0U4B74uQkdgfbLHqTlFnq.fuqU6QX1LDsUUrrCoJiKOGp93pBW5Uq', 'ADMIN', '2026-02-16 10:39:43', 'inactif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, 'Commande', 'Responsable', 'commande', '$2y$10$8utAiJrllK7sh7RhZbnf7.h6lRD3/RPTDATtjVlv4k0YvFhnuosye', 'RESPONSABLE', '2026-02-21 12:57:07', 'actif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `action_historique`
--
ALTER TABLE `action_historique`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_8E1DBF26642B8210` (`admin_id`);

--
-- Index pour la table `article`
--
ALTER TABLE `article`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_23A0E665ED3C7B7` (`artisan_id`),
  ADD KEY `IDX_23A0E66A76ED395` (`user_id`);

--
-- Index pour la table `commande`
--
ALTER TABLE `commande`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_6EEAA67D19EB6921` (`client_id`);

--
-- Index pour la table `commande_article`
--
ALTER TABLE `commande_article`
  ADD PRIMARY KEY (`commande_id`,`article_id`),
  ADD KEY `IDX_F4817CC682EA2E54` (`commande_id`),
  ADD KEY `IDX_F4817CC67294869C` (`article_id`);

--
-- Index pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_67F068BC7294869C` (`article_id`),
  ADD KEY `IDX_67F068BCA76ED395` (`user_id`),
  ADD KEY `IDX_67F068BC727ACA70` (`parent_id`);

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Index pour la table `evenement`
--
ALTER TABLE `evenement`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `evenement_image`
--
ALTER TABLE `evenement_image`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_5697A8A7FD02F13` (`evenement_id`);

--
-- Index pour la table `evenement_prediction`
--
ALTER TABLE `evenement_prediction`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_C743D386FD02F13` (`evenement_id`);

--
-- Index pour la table `livraison`
--
ALTER TABLE `livraison`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_A60C9F1F82EA2E54` (`commande_id`),
  ADD KEY `IDX_A60C9F1FF8646701` (`livreur_id`);

--
-- Index pour la table `produit`
--
ALTER TABLE `produit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_29A5EC2755B127A4` (`added_by_id`);

--
-- Index pour la table `proposition`
--
ALTER TABLE `proposition`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_C7CDC353A76ED395` (`user_id`),
  ADD KEY `IDX_C7CDC353F347EFB` (`produit_id`);

--
-- Index pour la table `reclamation`
--
ALTER TABLE `reclamation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_CE606404A76ED395` (`user_id`);

--
-- Index pour la table `reponse_reclamation`
--
ALTER TABLE `reponse_reclamation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_C7CB51012D6BA2D9` (`reclamation_id`),
  ADD KEY `IDX_C7CB5101642B8210` (`admin_id`);

--
-- Index pour la table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_42C84955FD02F13` (`evenement_id`),
  ADD KEY `IDX_42C84955A76ED395` (`user_id`);

--
-- Index pour la table `suivi_livraison`
--
ALTER TABLE `suivi_livraison`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_CFAC64718E54FB25` (`livraison_id`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `action_historique`
--
ALTER TABLE `action_historique`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `article`
--
ALTER TABLE `article`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `commande`
--
ALTER TABLE `commande`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `commentaire`
--
ALTER TABLE `commentaire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `evenement`
--
ALTER TABLE `evenement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `evenement_image`
--
ALTER TABLE `evenement_image`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `evenement_prediction`
--
ALTER TABLE `evenement_prediction`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `livraison`
--
ALTER TABLE `livraison`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `produit`
--
ALTER TABLE `produit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `proposition`
--
ALTER TABLE `proposition`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `reclamation`
--
ALTER TABLE `reclamation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `reponse_reclamation`
--
ALTER TABLE `reponse_reclamation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reservation`
--
ALTER TABLE `reservation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `suivi_livraison`
--
ALTER TABLE `suivi_livraison`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `action_historique`
--
ALTER TABLE `action_historique`
  ADD CONSTRAINT `FK_8E1DBF26642B8210` FOREIGN KEY (`admin_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `article`
--
ALTER TABLE `article`
  ADD CONSTRAINT `FK_23A0E665ED3C7B7` FOREIGN KEY (`artisan_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_23A0E66A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `commande`
--
ALTER TABLE `commande`
  ADD CONSTRAINT `FK_6EEAA67D19EB6921` FOREIGN KEY (`client_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `commande_article`
--
ALTER TABLE `commande_article`
  ADD CONSTRAINT `FK_F4817CC67294869C` FOREIGN KEY (`article_id`) REFERENCES `article` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_F4817CC682EA2E54` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD CONSTRAINT `FK_67F068BC727ACA70` FOREIGN KEY (`parent_id`) REFERENCES `commentaire` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_67F068BC7294869C` FOREIGN KEY (`article_id`) REFERENCES `article` (`id`),
  ADD CONSTRAINT `FK_67F068BCA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `evenement_image`
--
ALTER TABLE `evenement_image`
  ADD CONSTRAINT `FK_5697A8A7FD02F13` FOREIGN KEY (`evenement_id`) REFERENCES `evenement` (`id`);

--
-- Contraintes pour la table `evenement_prediction`
--
ALTER TABLE `evenement_prediction`
  ADD CONSTRAINT `FK_C743D386FD02F13` FOREIGN KEY (`evenement_id`) REFERENCES `evenement` (`id`);

--
-- Contraintes pour la table `livraison`
--
ALTER TABLE `livraison`
  ADD CONSTRAINT `FK_A60C9F1F82EA2E54` FOREIGN KEY (`commande_id`) REFERENCES `commande` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_A60C9F1FF8646701` FOREIGN KEY (`livreur_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `produit`
--
ALTER TABLE `produit`
  ADD CONSTRAINT `FK_29A5EC2755B127A4` FOREIGN KEY (`added_by_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `proposition`
--
ALTER TABLE `proposition`
  ADD CONSTRAINT `FK_C7CDC353A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_C7CDC353F347EFB` FOREIGN KEY (`produit_id`) REFERENCES `produit` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `reclamation`
--
ALTER TABLE `reclamation`
  ADD CONSTRAINT `FK_CE606404A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `reponse_reclamation`
--
ALTER TABLE `reponse_reclamation`
  ADD CONSTRAINT `FK_C7CB51012D6BA2D9` FOREIGN KEY (`reclamation_id`) REFERENCES `reclamation` (`id`),
  ADD CONSTRAINT `FK_C7CB5101642B8210` FOREIGN KEY (`admin_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `FK_42C84955A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_42C84955FD02F13` FOREIGN KEY (`evenement_id`) REFERENCES `evenement` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `suivi_livraison`
--
ALTER TABLE `suivi_livraison`
  ADD CONSTRAINT `FK_CFAC64718E54FB25` FOREIGN KEY (`livraison_id`) REFERENCES `livraison` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
