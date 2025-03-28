-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 12, 2025 at 02:11 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `php_project`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `rental_length` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `product_id`, `quantity`, `rental_length`, `total_price`, `created_at`) VALUES
(1, 2, 9, 1, 2, 30000.00, '2025-03-09 23:19:47'),
(2, 2, 1, 1, 1, 12000.00, '2025-03-09 23:23:08'),
(3, 2, 14, 1, 1, 14000.00, '2025-03-11 22:35:10'),
(4, 3, 16, 4, 4, 144000.00, '2025-03-11 22:37:35'),
(5, 3, 3, 1, 2, 36000.00, '2025-03-12 00:29:30'),
(6, 3, 5, 3, 4, 144000.00, '2025-03-12 00:29:30'),
(7, 3, 5, 1, 1, 12000.00, '2025-03-12 00:30:52'),
(8, 3, 2, 1, 1, 15000.00, '2025-03-12 00:30:52'),
(9, 3, 2, 1, 1, 15000.00, '2025-03-12 00:33:13'),
(10, 3, 8, 1, 1, 12000.00, '2025-03-12 00:33:13'),
(11, 3, 17, 1, 1, 10000.00, '2025-03-12 00:48:28'),
(12, 3, 17, 1, 1, 10000.00, '2025-03-12 00:50:44'),
(13, 3, 3, 1, 1, 18000.00, '2025-03-12 00:51:03');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `product_description` text DEFAULT NULL,
  `product_image` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `product_image2` varchar(255) DEFAULT NULL,
  `product_image3` varchar(255) DEFAULT NULL,
  `product_image4` varchar(255) DEFAULT NULL,
  `product_quantity` int(11) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `product_price`, `product_description`, `product_image`, `category`, `created_at`, `product_image2`, `product_image3`, `product_image4`, `product_quantity`) VALUES
(1, 'Sofa', 12000.00, 'This is a modern sectional sofa set designed for a spacious living room. The L-shaped design provides ample seating space, making it perfect for large families or gatherings. The deep gray upholstery gives a contemporary and sophisticated look, while the matching ottoman adds comfort and functionality.\n\nThe plush cushions and cozy fabric ensure a relaxing experience. The living room features neutral tones, complementing the sleek gray area rug and minimalistic decor. The gallery below showcases different sectional sofa styles, offering variations in color, fabric, and arrangement to suit different aesthetics.\n\nThis setup is ideal for anyone looking for luxury, comfort, and modern elegance in their home. 🏡✨\n\n\n\n\n\n\n\n', 'sofaa.jpeg', 'sofas', '2025-02-19 16:27:10', 'sofa23.jpeg', 'sofa24.jpeg', 'sofa25.jpeg', 2),
(2, 'Coffee Table', 15000.00, 'Elegant dining table for a modern look.', 'table.jpeg', 'coffeetables', '2025-02-19 19:11:43', 'cof1.jpeg', 'cof2.jpeg', 'cof3.jpeg', 8),
(3, 'Accent Chair', 18000.00, 'Modern Accent Chair', 'acc16.jpeg', 'accentchair', '2025-02-19 19:11:43', 'acc15.jpeg', 'acc17.jpeg', 'acc18.jpeg', 8),
(5, 'Round Dining Table', 12000.00, 'Comfortable luxury sofa for your living room.', 'round.jpeg', 'round', '2025-02-19 19:22:48', 'round5.jpeg', 'round6.jpeg', 'round7.jpeg', 6),
(6, 'Rectangle Dining Table', 15000.00, 'Elegant dining table for a modern look.', 'rec.jpeg', 'rectangle', '2025-02-19 19:22:48', 'ree1.jpeg', 'ree2.jpeg', 'ree3.jpeg', 10),
(7, 'Square Dining Table\n', 18000.00, 'King-size bed with a soft mattress.', 'sq.jpeg', 'square', '2025-02-19 19:22:48', 'sg2.jpeg', 'square1.jpeg', 'square2.jpeg', 10),
(8, 'Bedframe', 12000.00, 'Comfortable luxury sofa for your living room.', 'frames.jpeg', 'bedframe', '2025-02-19 19:37:24', 'frame4.jpeg', 'frame5.jpeg', 'frame6.jpeg', 1),
(9, 'Dresser', 15000.00, 'Elegant dining table for a modern look.', 'dr.jpeg', 'dresser', '2025-02-19 19:37:24', 'dr1.jpeg', 'dr11.jpeg', 'dr111.jpeg', 9),
(10, 'Side Table', 18000.00, 'King-size bed with a soft mattress.', 'side.jpeg', 'sidetable', '2025-02-19 19:37:24', 'side1.jpeg', 'side11.jpeg', 'side111.jpeg', 10),
(11, 'Office Chair', 15000.00, 'Elegant dining table for a modern look.', 'chairs.jpeg', 'officechair', '2025-02-19 19:37:24', 'chair1.jpeg', 'chair2.jpeg', 'chair3.jpeg', 10),
(12, 'Desk', 18000.00, 'King-size bed with a soft mattress.', 'des1.jpeg', 'desk', '2025-02-19 19:37:24', 'des2.jpeg', 'des3.jpeg', 'des2.jpeg', 1),
(13, 'Corner Desk', 18000.00, 'King-size bed with a soft mattress.', 'cr7.jpeg', 'cornerdesk', '2025-02-19 19:37:24', 'cr7.jpeg', 'cr8.jpeg', 'cr9.jpeg', 10),
(14, 'Sofa', 14000.00, 'Comfortable luxury sofas for your living room.', 'sofa1.jpeg', 'sofas', '2025-02-21 20:39:21', 'sofat.jpeg', 'sofatt.jpeg', 'sofattt.jpeg', 9),
(15, 'Sofa', 13000.00, 'Comfortable luxury sofas for your living room.', 'sofa2.jpeg', 'sofas', '2025-02-21 20:39:53', 'sofad.jpeg', 'sofadd.jpeg', 'sofaddd.jpeg', 10),
(16, 'Coffee Table', 9000.00, 'Elegant dining table for a modern look.', 'coffee2.jpeg', 'coffeetables', '2025-02-22 10:35:16', 'cof4.jpeg', 'cof5.jpeg', 'cof6.jpeg', 6),
(17, 'Coffee Table', 10000.00, 'Elegant dining table for a modern look.', 'coffee3.jpeg', 'coffeetables', '2025-02-22 10:35:16', 'cof7.jpeg', 'cof8.jpeg', 'cof9.jpeg', 8),
(18, 'Accent Chair', 5000.00, 'Modern Accent Chair', 'acc4.jpeg', 'accentchair', '2025-02-22 10:38:48', 'acc12.jpeg', 'acc13.jpeg', 'acc14.jpeg', 10),
(19, 'Accent Chair', 7000.00, 'Modern Accent Chair', 'acc2.jpeg', 'accentchair', '2025-02-22 10:38:48', 'acc9.jpeg', 'acc10.jpeg', 'acc11.jpeg', 10),
(20, 'Round Dining Table', 20000.00, 'Round Dining Table', 'round3.jpeg', 'round', '2025-02-22 10:52:46', 'round8.jpeg', 'round9.jpeg', 'round11.jpeg', 10),
(21, 'Round Dining Table', 20000.00, 'Round Dining Table', 'round2.jpg', 'round', '2025-02-22 10:52:46', 'r1.jpeg', 'r2.jpeg', 'r3.jpeg', 10),
(22, 'Rectangle Dining Table', 20000.00, 'Rectangle Dining Table', 'rec2.jpeg', 'rectangle', '2025-02-22 10:55:22', 'ree4.jpeg', 'ree5.jpeg', 'ree6.jpeg', 10),
(23, 'Rectangle Dining Table', 20000.00, 'Rectangle Dining Table', 'rec3.jpeg', 'rectangle', '2025-02-22 10:55:22', 'ree7.jpeg', 'ree8.jpeg', 'ree9.jpeg', 10),
(24, 'Square Dining Table\r\n', 20000.00, 'Square Dining Table\r\n', 'square3.jpeg', 'square', '2025-02-22 10:58:39', 'sq5.jpeg', 'sq6.jpeg', 'sq7.jpeg', 10),
(25, 'Square Dining Table\r\n', 20000.00, 'Square Dining Table\r\n', 'sg3.jpeg', 'square', '2025-02-22 10:58:39', 'sg4.jpeg', 'sg5.jpeg', 'sg6.jpeg', 10),
(26, 'Bedframe', 12000.00, 'Bedframe', 'bed3.jpeg', 'bedframe', '2025-02-22 11:14:54', 'frame1.jpeg', 'frame2.jpeg', 'frame3.jpeg', 10),
(27, 'Bedframe', 12000.00, 'Bedframe', 'bed2.jpeg', 'bedframe', '2025-02-22 11:14:54', 'frame7.jpeg', 'frame8.jpeg', 'frame9.jpeg', 10),
(28, 'Dresser', 12000.00, 'Dresser', 'dr2.jpeg', 'dresser', '2025-02-22 11:18:34', 'dr22.jpeg', 'dr222.jpeg', 'dr2222.jpeg', 10),
(29, 'Dresser', 12000.00, 'Dresser', 'dr3.jpeg', 'dresser', '2025-02-22 11:18:34', 'dr2.jpeg', 'dr22.jpeg', 'dr222.jpeg', 10),
(30, 'Side Table', 10000.00, 'Side Table', 'side2.jpeg', 'sidetable', '2025-02-22 11:21:06', 'side5.jpeg', 'side6.jpeg', 'side7.jpeg', 10),
(31, 'Side Table', 10000.00, 'Side Table', 'sid3.jpeg', 'sidetable', '2025-02-22 11:21:06', 'side8.jpeg', 'side9.jpeg', 'side10.jpeg', 10),
(32, 'Office Chair', 5000.00, 'Office Chair', 'office2.jpeg', 'officechair', '2025-02-22 11:24:21', 'chair4.jpeg', 'chair5.jpeg', 'chair6.jpeg', 10),
(33, 'Office Chair', 5000.00, 'Office Chair', 'office3.jpeg', 'officechair', '2025-02-22 11:24:21', 'o1.jpeg', 'o2.jpeg', 'o3.jpeg', 10),
(34, 'Desk', 30000.00, 'Desk', 'desk3.jpeg', 'desk', '2025-02-22 11:26:41', 'desk1.jpeg', 'desk11.jpeg', 'desk111.jpeg', 10),
(35, 'Desk', 25000.00, 'Desk', 'des5.jpeg', 'desk', '2025-02-22 11:26:41', 'des4.jpeg', 'des6.jpeg', 'des7.jpeg', 10),
(36, 'Corner Desk', 20000.00, 'Corner Desk', 'cr2.jpeg', 'cornerdesk', '2025-02-22 11:28:37', 'corner1.jpeg', 'corner3.jpeg', 'corner2.jpeg', 10),
(37, 'Corner Desk', 20000.00, 'Corner Desk', 'cr3.jpeg\r\n', 'cornerdesk', '2025-02-22 11:28:37', 'cr6.jpeg', 'cr5.jpeg', 'cr6.jpeg', 10),
(38, 'Sofa', 12000.00, 'Good sofa', 'sofa11.jpeg', 'sofas', '2025-03-04 22:20:55', 'sofaj.jpeg', 'sofajj.jpeg', 'sofajjj.jpeg', 10),
(39, 'Coffee table', 10000.00, 'GOOD QUALITY ', 'cof10.jpeg', 'coffeetables', '2025-03-05 17:47:00', 'cof11.jpeg', 'cof12.jpeg', 'cof13.jpeg', 10),
(40, 'Accent Chair', 8000.00, 'GOOD QUALITY', 'acc5.jpeg', 'accentchair', '2025-03-05 23:42:10', 'acc6.jpeg', 'acc7.jpeg', 'acc8.jpeg', 10),
(41, 'Round Dining Table ', 18000.00, 'good quality ', 'r4.jpeg', 'round', '2025-03-06 23:09:32', 'r5.jpeg', 'r6.jpeg', 'r7.jpeg', 10),
(42, 'Rectangle Dining Table ', 130000.00, 'good quality ', 'ree11.jpeg', 'rectangle', '2025-03-06 23:23:24', 'ree12.jpeg', 'ree13.jpeg', 'ree14.jpeg', 10),
(43, 'Square Dining Table ', 20000.00, 'good quality', 'sq8.jpeg', 'square', '2025-03-06 23:42:43', 'sq9.jpeg', 'sq10.jpeg', 'sq11.jpeg', 10),
(44, 'Bedframe', 7000.00, 'good', 'frame10.jpeg', 'bedframe', '2025-03-06 23:58:42', 'frame11.jpeg', 'frame12.jpeg', 'frame13.jpeg', 10),
(45, 'Dresser ', 12000.00, 'good one', 'dr5.jpeg', 'dresser', '2025-03-08 11:06:22', 'dr33.jpeg', 'dr333.jpeg', 'dr4.jpeg', 10),
(46, 'Side Table ', 10000.00, 'good quality ', 'sidee12.jpeg', 'sidetable', '2025-03-08 11:29:46', 'side13.jpeg', 'side14.jpeg', 'side15.jpeg', 10),
(47, 'Office Chair ', 6000.00, 'GOOD ', 'chair7.jpeg', 'officechair', '2025-03-08 12:04:18', 'chair8.jpeg', 'chair9.jpeg', 'chair10.jpeg', 10),
(48, 'Office Desk ', 18000.00, 'good ', 'des8.jpeg', 'desk', '2025-03-08 12:33:47', 'des9.jpeg', 'des10.jpeg', 'des11.jpeg', 10),
(49, 'Corner Desk', 20000.00, 'good ', 'cr10.jpeg', 'cornerdesk', '2025-03-08 12:50:10', 'cr11.jpeg', 'cr12.jpeg', 'cr13.jpeg', 10);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `user_password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `user_name`, `user_email`, `user_password`) VALUES
(1, 'radia', 'bendjima', 'radia2004'),
(2, 'rodina', 'marie@gmail.com', 'marie2010'),
(3, 'radia', 'bendima@gmail.com', 'bendjima2004');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `user_email` (`user_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
