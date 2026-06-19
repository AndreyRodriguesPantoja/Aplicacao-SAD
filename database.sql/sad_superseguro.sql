-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2026 at 11:33 PM
-- Server version: 8.0.46
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sad_superseguro`
--

-- --------------------------------------------------------

--
-- Table structure for table `apolices`
--

CREATE TABLE `apolices` (
  `id` int UNSIGNED NOT NULL,
  `numero_apolice` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` int UNSIGNED NOT NULL,
  `analista_id` int UNSIGNED DEFAULT NULL,
  `tipo_seguro` enum('Vida','Saude','Auto','Residencial') COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor_cobertura` decimal(14,2) NOT NULL DEFAULT '0.00',
  `valor_premio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `status` enum('ativa','suspensa','cancelada','expirada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativa',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `apolices`
--

INSERT INTO `apolices` (`id`, `numero_apolice`, `usuario_id`, `analista_id`, `tipo_seguro`, `valor_cobertura`, `valor_premio`, `data_inicio`, `data_fim`, `status`, `observacoes`, `created_at`, `updated_at`) VALUES
(1, 'AP-VID-2025-00001', 1, 1, 'Vida', 100000.00, 300.00, '2025-01-10', '2026-01-10', 'ativa', 'Cobertura básica de vida. Risco baixo, aprovação automática.', '2026-06-18 15:53:19', '2026-06-18 15:53:19'),
(2, 'AP-SAU-2025-00002', 2, 2, 'Saude', 80000.00, 420.00, '2025-03-01', '2026-03-01', 'expirada', 'Plano saúde individual. Risco moderado — prêmio com agravamento de 20%.', '2026-06-18 15:53:19', '2026-06-19 03:27:47'),
(3, 'AP-AUT-2024-00003', 3, 2, 'Auto', 40000.00, 520.00, '2024-06-15', '2025-06-15', 'expirada', 'Seguro auto veículo 2019. Expirada — cliente notificado para renovação.', '2026-06-18 15:53:19', '2026-06-18 15:53:19'),
(4, 'AP-RES-2025-00004', 4, 1, 'Residencial', 60000.00, 240.00, '2025-05-01', '2026-05-01', 'suspensa', 'Pendente envio de laudo de vistoria do imóvel.', '2026-06-18 15:53:19', '2026-06-18 15:53:19'),
(5, 'AP-VID-2026-00005', 5, 1, 'Vida', 150000.00, 390.00, '2026-01-15', '2027-01-15', 'cancelada', 'Cobertura premium. Cliente perfil jovem, baixo risco.', '2026-06-18 15:53:19', '2026-06-19 03:27:54'),
(6, 'AP-AUT-2026-99793', 4, 1, 'Auto', 1000000.00, 11200.00, '2026-06-18', '2027-06-18', 'ativa', 'Teste', '2026-06-19 03:27:20', '2026-06-19 03:27:20'),
(7, 'AP-VID-2026-90023', 1, 2, 'Vida', 1000000.00, 3000.00, '2026-06-25', '2027-06-25', 'ativa', 'Teste', '2026-06-19 17:10:43', '2026-06-19 17:10:43');

-- --------------------------------------------------------

--
-- Table structure for table `clientes`
--

CREATE TABLE `clientes` (
  `Id` int UNSIGNED NOT NULL,
  `usuario_id` int UNSIGNED DEFAULT NULL,
  `Ins_Age` tinyint UNSIGNED NOT NULL,
  `BMI` decimal(5,2) NOT NULL,
  `Response` tinyint UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clientes`
--

INSERT INTO `clientes` (`Id`, `usuario_id`, `Ins_Age`, `BMI`, `Response`, `created_at`) VALUES
(1, 1, 35, 22.50, 2, '2026-06-18 15:53:19'),
(2, 2, 52, 28.50, 5, '2026-06-18 15:53:19'),
(3, 3, 63, 32.00, 8, '2026-06-18 15:53:19'),
(4, 4, 58, 35.00, 8, '2026-06-18 15:53:19'),
(5, 5, 29, 21.00, 2, '2026-06-18 15:53:19'),
(6, 1, 35, 23.10, 2, '2026-06-18 15:53:19'),
(7, 2, 52, 27.80, 5, '2026-06-18 15:53:19');

-- --------------------------------------------------------

--
-- Table structure for table `funcionarios`
--

CREATE TABLE `funcionarios` (
  `id` int UNSIGNED NOT NULL,
  `perfil_id` tinyint UNSIGNED NOT NULL,
  `nome` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` char(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_acesso` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `funcionarios`
--

INSERT INTO `funcionarios` (`id`, `perfil_id`, `nome`, `cpf`, `email`, `usuario`, `senha`, `telefone`, `ativo`, `ultimo_acesso`, `created_at`, `updated_at`) VALUES
(1, 3, 'Ricardo Mendes Souza', '00011122233', 'ricardo.gerente@superseguro.com', 'gerente', '$2y$12$Kj7gPVX4DqZ1oN8WrL9s8.hTlVwY3XmQzRpU5cN6oE4vJbI2FdAKq', '(91) 98001-0001', 1, '2026-06-19 00:20:59', '2026-06-18 15:53:19', '2026-06-19 03:20:59'),
(2, 2, 'Fernanda Costa Lima', '00011122244', 'fernanda.analista@superseguro.com', 'analista1', '123', '(91) 98001-0002', 1, '2026-06-19 14:10:08', '2026-06-18 15:53:19', '2026-06-19 17:10:08'),
(3, 2, 'Bruno Alves Rodrigues', '00011122255', 'bruno.analista@superseguro.com', 'analista2', '$2y$12$Kj7gPVX4DqZ1oN8WrL9s8.hTlVwY3XmQzRpU5cN6oE4vJbI2FdAKq', '(91) 98001-0003', 1, NULL, '2026-06-18 15:53:19', '2026-06-18 15:53:19');

-- --------------------------------------------------------

--
-- Table structure for table `historico_status`
--

CREATE TABLE `historico_status` (
  `id` int UNSIGNED NOT NULL,
  `apolice_id` int UNSIGNED NOT NULL,
  `funcionario_id` int UNSIGNED DEFAULT NULL,
  `status_anterior` enum('ativa','suspensa','cancelada','expirada') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_novo` enum('ativa','suspensa','cancelada','expirada') COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo` text COLLATE utf8mb4_unicode_ci,
  `alterado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `historico_status`
--

INSERT INTO `historico_status` (`id`, `apolice_id`, `funcionario_id`, `status_anterior`, `status_novo`, `motivo`, `alterado_em`) VALUES
(1, 3, 2, 'ativa', 'expirada', 'Vigência encerrada em 15/06/2025. Sistema automático.', '2026-06-18 15:53:19'),
(2, 4, 1, 'ativa', 'suspensa', 'Laudo de vistoria do imóvel não enviado pelo cliente no prazo.', '2026-06-18 15:53:19');

-- --------------------------------------------------------

--
-- Table structure for table `perfis`
--

CREATE TABLE `perfis` (
  `id` tinyint UNSIGNED NOT NULL,
  `nome` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `perfis`
--

INSERT INTO `perfis` (`id`, `nome`, `descricao`, `created_at`) VALUES
(1, 'cliente', 'Acesso ao portal do cliente: visualizar apólices próprias e dados pessoais', '2026-06-18 15:53:18'),
(2, 'analista', 'Acesso ao painel interno: analisar risco, criar e gerenciar apólices', '2026-06-18 15:53:18'),
(3, 'gerente', 'Acesso completo: dashboard, relatórios, gestão de usuários e apólices', '2026-06-18 15:53:18');

-- --------------------------------------------------------

--
-- Table structure for table `sessoes`
--

CREATE TABLE `sessoes` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_conta` enum('usuario','funcionario') COLLATE utf8mb4_unicode_ci NOT NULL,
  `conta_id` int UNSIGNED NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criada_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expira_em` datetime NOT NULL,
  `ativa` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int UNSIGNED NOT NULL,
  `perfil_id` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `usuario` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` char(11) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rg` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `genero` enum('masculino','feminino','outro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `datanascimento` date NOT NULL,
  `pais` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Brasil',
  `estado` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cidade` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rua` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numeroresi` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `salario` decimal(12,2) NOT NULL DEFAULT '0.00',
  `valorapli` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_acesso` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `perfil_id`, `usuario`, `senha`, `nome`, `cpf`, `rg`, `email`, `telefone`, `genero`, `datanascimento`, `pais`, `estado`, `cidade`, `rua`, `numeroresi`, `salario`, `valorapli`, `ativo`, `ultimo_acesso`, `created_at`, `updated_at`) VALUES
(1, 1, 'joao.silva', '$2y$12$Kj7gPVX4DqZ1oN8WrL9s8.hTlVwY3XmQzRpU5cN6oE4vJbI2FdAKq', 'João Silva', '12345678901', '1234567', 'joao.silva@email.com', '(91) 99001-0001', 'masculino', '1989-03-15', 'Brasil', 'Pará', 'Belém', 'Av. Nazaré', '120', 5200.00, 50000.00, 1, NULL, '2026-06-18 15:53:19', '2026-06-18 15:53:19'),
(2, 1, 'maria.santos', '$2y$12$Kj7gPVX4DqZ1oN8WrL9s8.hTlVwY3XmQzRpU5cN6oE4vJbI2FdAKq', 'Maria Santos', '23456789012', '2345678', 'maria.santos@email.com', '(91) 99001-0002', 'feminino', '1972-07-22', 'Brasil', 'Pará', 'Belém', 'Trav. Padre Eutiquio', '45', 8100.00, 80000.00, 1, NULL, '2026-06-18 15:53:19', '2026-06-18 15:53:19'),
(3, 1, 'carlos.lima', '$2y$12$Kj7gPVX4DqZ1oN8WrL9s8.hTlVwY3XmQzRpU5cN6oE4vJbI2FdAKq', 'Carlos Lima', '34567890123', '3456789', 'carlos.lima@email.com', '(91) 99001-0003', 'masculino', '1961-11-08', 'Brasil', 'Pará', 'Santarém', 'Rua Floriano Peixoto', '77', 3400.00, 30000.00, 1, NULL, '2026-06-18 15:53:19', '2026-06-18 15:53:19'),
(4, 1, 'ana.oliveira', '$2y$12$Kj7gPVX4DqZ1oN8WrL9s8.hTlVwY3XmQzRpU5cN6oE4vJbI2FdAKq', 'Ana Oliveira', '45678901234', '4567890', 'ana.oliveira@email.com', '(91) 99001-0004', 'feminino', '1967-05-30', 'Brasil', 'Pará', 'Marabá', 'Rua Independência', '200', 4600.00, 45000.00, 1, NULL, '2026-06-18 15:53:19', '2026-06-18 15:53:19'),
(5, 1, 'paulo.ferreira', '$2y$12$Kj7gPVX4DqZ1oN8WrL9s8.hTlVwY3XmQzRpU5cN6oE4vJbI2FdAKq', 'Paulo Ferreira', '56789012345', '5678901', 'paulo.ferreira@email.com', '(91) 99001-0005', 'masculino', '1995-02-14', 'Brasil', 'Pará', 'Belém', 'Av. Almirante Barroso', '350', 6700.00, 60000.00, 1, NULL, '2026-06-18 15:53:19', '2026-06-18 15:53:19'),
(6, 1, '02287849211', '1234', 'Davi', '02287849211', '837729', 'davis-carvalho@hotmail.com', '91988647119', 'masculino', '2002-07-19', 'Brazil', 'Pará', 'Belém', 'Rua nossa senhora de fátiam', '621', 5500.00, 123.00, 1, NULL, '2026-06-18 15:53:33', '2026-06-18 16:06:13');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_apolices`
-- (See below for the actual view)
--
CREATE TABLE `vw_apolices` (
`analista_id` int unsigned
,`analista_nome` varchar(120)
,`cliente_cpf` char(11)
,`cliente_email` varchar(120)
,`cliente_nome` varchar(120)
,`cliente_telefone` varchar(20)
,`created_at` timestamp
,`data_fim` date
,`data_inicio` date
,`id` int unsigned
,`media_risco` decimal(6,2)
,`numero_apolice` varchar(30)
,`observacoes` text
,`status` enum('ativa','suspensa','cancelada','expirada')
,`tipo_seguro` enum('Vida','Saude','Auto','Residencial')
,`usuario_id` int unsigned
,`valor_cobertura` decimal(14,2)
,`valor_premio` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_clientes`
-- (See below for the actual view)
--
CREATE TABLE `vw_clientes` (
`apolices_ativas` bigint
,`ativo` tinyint(1)
,`cidade` varchar(80)
,`cpf` char(11)
,`created_at` timestamp
,`datanascimento` date
,`email` varchar(120)
,`estado` varchar(60)
,`genero` enum('masculino','feminino','outro')
,`id` int unsigned
,`media_risco` decimal(6,2)
,`nome` varchar(120)
,`salario` decimal(12,2)
,`telefone` varchar(20)
,`valorapli` decimal(12,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_funcionarios`
-- (See below for the actual view)
--
CREATE TABLE `vw_funcionarios` (
`ativo` tinyint(1)
,`cpf` char(11)
,`created_at` timestamp
,`email` varchar(120)
,`id` int unsigned
,`nome` varchar(120)
,`perfil` varchar(30)
,`telefone` varchar(20)
,`ultimo_acesso` datetime
,`usuario` varchar(60)
);

-- --------------------------------------------------------

--
-- Structure for view `vw_apolices`
--
DROP TABLE IF EXISTS `vw_apolices`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_apolices`  AS SELECT `a`.`id` AS `id`, `a`.`numero_apolice` AS `numero_apolice`, `a`.`tipo_seguro` AS `tipo_seguro`, `a`.`valor_cobertura` AS `valor_cobertura`, `a`.`valor_premio` AS `valor_premio`, `a`.`data_inicio` AS `data_inicio`, `a`.`data_fim` AS `data_fim`, `a`.`status` AS `status`, `a`.`observacoes` AS `observacoes`, `a`.`created_at` AS `created_at`, `u`.`id` AS `usuario_id`, `u`.`nome` AS `cliente_nome`, `u`.`cpf` AS `cliente_cpf`, `u`.`email` AS `cliente_email`, `u`.`telefone` AS `cliente_telefone`, `f`.`id` AS `analista_id`, `f`.`nome` AS `analista_nome`, round(coalesce((select avg(`c`.`Response`) from `clientes` `c` where (`c`.`usuario_id` = `u`.`id`)),0),2) AS `media_risco` FROM ((`apolices` `a` join `usuarios` `u` on((`u`.`id` = `a`.`usuario_id`))) left join `funcionarios` `f` on((`f`.`id` = `a`.`analista_id`))) ;

-- --------------------------------------------------------

--
-- Structure for view `vw_clientes`
--
DROP TABLE IF EXISTS `vw_clientes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_clientes`  AS SELECT `u`.`id` AS `id`, `u`.`nome` AS `nome`, `u`.`cpf` AS `cpf`, `u`.`email` AS `email`, `u`.`telefone` AS `telefone`, `u`.`genero` AS `genero`, `u`.`datanascimento` AS `datanascimento`, `u`.`cidade` AS `cidade`, `u`.`estado` AS `estado`, `u`.`salario` AS `salario`, `u`.`valorapli` AS `valorapli`, `u`.`ativo` AS `ativo`, `u`.`created_at` AS `created_at`, (select count(0) from `apolices` `a` where ((`a`.`usuario_id` = `u`.`id`) and (`a`.`status` = 'ativa'))) AS `apolices_ativas`, (select round(avg(`c`.`Response`),2) from `clientes` `c` where (`c`.`usuario_id` = `u`.`id`)) AS `media_risco` FROM `usuarios` AS `u` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_funcionarios`
--
DROP TABLE IF EXISTS `vw_funcionarios`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_funcionarios`  AS SELECT `f`.`id` AS `id`, `f`.`nome` AS `nome`, `f`.`cpf` AS `cpf`, `f`.`email` AS `email`, `f`.`usuario` AS `usuario`, `f`.`telefone` AS `telefone`, `f`.`ativo` AS `ativo`, `f`.`ultimo_acesso` AS `ultimo_acesso`, `f`.`created_at` AS `created_at`, `p`.`nome` AS `perfil` FROM (`funcionarios` `f` join `perfis` `p` on((`p`.`id` = `f`.`perfil_id`))) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `apolices`
--
ALTER TABLE `apolices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_apolice` (`numero_apolice`),
  ADD KEY `idx_ap_usuario` (`usuario_id`),
  ADD KEY `idx_ap_analista` (`analista_id`),
  ADD KEY `idx_ap_status` (`status`),
  ADD KEY `idx_ap_tipo` (`tipo_seguro`),
  ADD KEY `idx_ap_vigencia` (`data_inicio`,`data_fim`),
  ADD KEY `idx_ap_usuario_status` (`usuario_id`,`status`);

--
-- Indexes for table `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `idx_cli_usuario` (`usuario_id`),
  ADD KEY `idx_cli_risco` (`Response`),
  ADD KEY `idx_cli_idade` (`Ins_Age`),
  ADD KEY `idx_cli_created` (`created_at`);

--
-- Indexes for table `funcionarios`
--
ALTER TABLE `funcionarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `idx_func_perfil` (`perfil_id`),
  ADD KEY `idx_func_usuario` (`usuario`),
  ADD KEY `idx_func_email` (`email`);

--
-- Indexes for table `historico_status`
--
ALTER TABLE `historico_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hist_apolice` (`apolice_id`),
  ADD KEY `idx_hist_funcionario` (`funcionario_id`);

--
-- Indexes for table `perfis`
--
ALTER TABLE `perfis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Indexes for table `sessoes`
--
ALTER TABLE `sessoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ses_conta` (`tipo_conta`,`conta_id`),
  ADD KEY `idx_ses_expira` (`expira_em`),
  ADD KEY `idx_ses_ativa` (`ativa`),
  ADD KEY `idx_ses_conta_ativa` (`tipo_conta`,`conta_id`,`ativa`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_usr_perfil` (`perfil_id`),
  ADD KEY `idx_usr_email` (`email`),
  ADD KEY `idx_usr_cpf` (`cpf`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `apolices`
--
ALTER TABLE `apolices`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `clientes`
--
ALTER TABLE `clientes`
  MODIFY `Id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `funcionarios`
--
ALTER TABLE `funcionarios`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `historico_status`
--
ALTER TABLE `historico_status`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `perfis`
--
ALTER TABLE `perfis`
  MODIFY `id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `apolices`
--
ALTER TABLE `apolices`
  ADD CONSTRAINT `fk_ap_analista` FOREIGN KEY (`analista_id`) REFERENCES `funcionarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ap_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_cli_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `funcionarios`
--
ALTER TABLE `funcionarios`
  ADD CONSTRAINT `fk_func_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `perfis` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `historico_status`
--
ALTER TABLE `historico_status`
  ADD CONSTRAINT `fk_hist_apolice` FOREIGN KEY (`apolice_id`) REFERENCES `apolices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hist_funcionario` FOREIGN KEY (`funcionario_id`) REFERENCES `funcionarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usr_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `perfis` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
