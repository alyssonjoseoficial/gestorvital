-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 04/04/2026 às 18:04
-- Versão do servidor: 5.7.23-23
-- Versão do PHP: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `alyss340_clinica_medica`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `agenda_medicos`
--

CREATE TABLE `agenda_medicos` (
  `id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `turno` enum('manha','tarde') NOT NULL,
  `limite_pacientes` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `agenda_medicos`
--

INSERT INTO `agenda_medicos` (`id`, `medico_id`, `data`, `turno`, `limite_pacientes`) VALUES
(1, 3, '2025-08-14', 'manha', 1),
(2, 3, '2025-08-14', 'tarde', 1),
(3, 1, '2025-08-14', 'manha', 5),
(4, 1, '2025-08-14', 'tarde', 4),
(9, 3, '2025-08-19', 'manha', 1),
(10, 3, '2025-08-19', 'tarde', 0),
(13, 3, '2025-08-18', 'manha', 8),
(14, 3, '2025-08-18', 'tarde', 8);

-- --------------------------------------------------------

--
-- Estrutura para tabela `atestados`
--

CREATE TABLE `atestados` (
  `id` int(11) NOT NULL,
  `consulta_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `cid` varchar(20) DEFAULT NULL,
  `dias_repouso` int(11) NOT NULL,
  `motivo_repouso` text NOT NULL,
  `data_atestado` date NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `atestados`
--

INSERT INTO `atestados` (`id`, `consulta_id`, `paciente_id`, `medico_id`, `cid`, `dias_repouso`, `motivo_repouso`, `data_atestado`, `criado_em`, `atualizado_em`) VALUES
(1, 11, 3, 3, 'A09-s', 5, 'Dores na coluna. A l5 está muito inflamada e precisa de repouso total nesse periodo.', '2025-08-14', '2025-08-15 02:05:00', '2025-08-15 02:05:44'),
(2, 15, 2, 3, 'A09-s', 5, 'A coisa tá feia', '2025-08-19', '2025-08-20 00:09:45', '2025-08-20 00:09:45');

-- --------------------------------------------------------

--
-- Estrutura para tabela `consultas`
--

CREATE TABLE `consultas` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `data_consulta` date NOT NULL,
  `turno` enum('manha','tarde','encaixe') NOT NULL,
  `status` enum('agendada','realizada','cancelada') DEFAULT 'agendada',
  `status_pagamento` varchar(50) DEFAULT 'pendente',
  `observacoes` text,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `hora_consulta` time DEFAULT NULL,
  `servico_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `consultas`
--

INSERT INTO `consultas` (`id`, `paciente_id`, `medico_id`, `data_consulta`, `turno`, `status`, `status_pagamento`, `observacoes`, `criado_em`, `hora_consulta`, `servico_id`) VALUES
(2, 2, 3, '2025-08-13', 'manha', 'realizada', 'pago', '', '2025-08-12 13:17:41', NULL, NULL),
(3, 3, 3, '2025-08-12', 'manha', 'realizada', 'pago', '', '2025-08-12 13:26:15', NULL, NULL),
(4, 4, 3, '2025-08-12', 'manha', 'realizada', 'pago', '', '2025-08-12 14:32:53', NULL, NULL),
(7, 2, 3, '2025-08-15', 'manha', 'realizada', 'pago', '', '2025-08-13 23:11:31', NULL, NULL),
(11, 3, 3, '2025-08-19', 'manha', 'realizada', 'pago', '', '2025-08-14 13:53:40', NULL, 3),
(13, 1, 3, '2025-08-19', 'manha', 'realizada', 'pago', '', '2025-08-14 23:02:03', '15:00:00', 5),
(15, 2, 3, '2025-08-19', 'manha', 'realizada', 'pago', '', '2025-08-14 23:52:42', '11:40:00', 3),
(16, 1, 3, '2025-08-19', 'manha', 'realizada', 'pago', '', '2025-08-14 23:53:25', '09:20:00', 3);

-- --------------------------------------------------------

--
-- Estrutura para tabela `convenios`
--

CREATE TABLE `convenios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cobertura` text,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `descricao` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `convenios`
--

INSERT INTO `convenios` (`id`, `nome`, `cobertura`, `criado_em`, `descricao`) VALUES
(1, 'Hapvida', NULL, '2025-08-12 01:51:44', 'tudo certo'),
(2, 'Unimed', NULL, '2025-08-14 13:31:23', 'tudo certo');

-- --------------------------------------------------------

--
-- Estrutura para tabela `despesas`
--

CREATE TABLE `despesas` (
  `id` int(11) NOT NULL,
  `data_despesa` date NOT NULL,
  `descricao` varchar(255) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `despesas`
--

INSERT INTO `despesas` (`id`, `data_despesa`, `descricao`, `valor`, `categoria`, `criado_em`) VALUES
(1, '2025-08-15', 'Material de limpeza', 320.00, 'contas fixas', '2025-08-15 13:45:53'),
(2, '2025-08-15', 'Vale transporte', 350.00, 'contas fixas', '2025-08-15 13:49:37'),
(3, '2025-08-15', 'Seringas', 278.90, 'insumos medicos', '2025-08-15 13:50:15'),
(4, '2025-08-30', 'Taxa de Agua', 300.00, 'contas fixas', '2025-08-30 23:01:30'),
(5, '2026-03-06', 'Papel fotógrafo ', 120.00, 'outros', '2026-03-06 13:47:22');

-- --------------------------------------------------------

--
-- Estrutura para tabela `evolucao_prontuarios`
--

CREATE TABLE `evolucao_prontuarios` (
  `id` int(11) NOT NULL,
  `prontuario_id` int(11) NOT NULL,
  `texto_evolucao` text NOT NULL,
  `data_evolucao` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura para tabela `exames`
--

CREATE TABLE `exames` (
  `id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `data_exame` date NOT NULL,
  `resultado` text,
  `status` enum('Agendado','Realizado','Com Laudo','Cancelado') NOT NULL DEFAULT 'Agendado',
  `data_criacao` datetime DEFAULT CURRENT_TIMESTAMP,
  `data_atualizacao` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `nome_arquivo` varchar(255) DEFAULT NULL,
  `servico_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `exames`
--

INSERT INTO `exames` (`id`, `paciente_id`, `medico_id`, `data_exame`, `resultado`, `status`, `data_criacao`, `data_atualizacao`, `nome_arquivo`, `servico_id`) VALUES
(1, 3, 3, '2025-08-20', 'Tudo certo com ela gracas', 'Realizado', '2025-08-17 19:39:03', '2025-08-23 13:13:23', 'laudo_68a3ea87a00159.73613465.jpg', 6),
(4, 2, 2, '2025-08-21', 'Está com leve arritimia.', 'Realizado', '2025-08-21 20:13:29', '2025-08-23 13:12:56', 'laudo_68a7a846c8a283.24586615.jpg', 6),
(6, 3, 3, '2025-08-21', 'A doida é doida', 'Realizado', '2025-08-21 20:17:01', '2025-08-23 13:13:09', 'laudo_68a7a905d45813.82513300.jpg', 4),
(7, 3, 3, '2025-08-22', '', 'Realizado', '2025-08-23 13:10:44', '2026-03-24 08:45:59', NULL, 4),
(8, 2, 1, '2026-03-24', NULL, 'Agendado', '2026-03-24 08:54:49', '2026-03-24 08:54:49', NULL, 6);

-- --------------------------------------------------------

--
-- Estrutura para tabela `identidade_clinica`
--

CREATE TABLE `identidade_clinica` (
  `id` int(11) NOT NULL,
  `nome_clinica` varchar(255) NOT NULL,
  `url_logo` varchar(255) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ultimo_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `cor_primaria` varchar(7) DEFAULT '#007bff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `identidade_clinica`
--

INSERT INTO `identidade_clinica` (`id`, `nome_clinica`, `url_logo`, `endereco`, `telefone`, `email`, `ultimo_update`, `cor_primaria`) VALUES
(1, 'Clínica Médica', 'uploads/68b0e3109da15-1756422928.png', 'Rua Santa Luiza, 43 - Centro - Itabaiana/SE', '79 9 xxxx-xxxx', 'clinica_medica@gmail.com', '2025-09-03 15:28:14', '#027402');

-- --------------------------------------------------------

--
-- Estrutura para tabela `logs_auditoria`
--

CREATE TABLE `logs_auditoria` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `usuario_nome` varchar(255) NOT NULL,
  `nivel_acesso` varchar(50) NOT NULL,
  `acao` varchar(255) NOT NULL,
  `modulo` varchar(100) NOT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `data_hora` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `logs_auditoria`
--

INSERT INTO `logs_auditoria` (`id`, `usuario_id`, `usuario_nome`, `nivel_acesso`, `acao`, `modulo`, `registro_id`, `data_hora`) VALUES
(1, 1, 'Administrador', 'administrador', 'Editou a despesa (ID 1): Valor de R$ 127,90 para R$ 320,00', 'Despesas', 1, '2025-08-23 14:55:19'),
(2, 8, 'alyssonMaster', 'master', 'Criou a identidade da clínica com o nome \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 16:29:14'),
(3, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:00:14'),
(4, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:00:25'),
(5, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:03:23'),
(6, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:03:27'),
(7, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:04:32'),
(8, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:04:34'),
(9, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:06:21'),
(10, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:06:33'),
(11, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:06:36'),
(12, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:20:50'),
(13, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:23:01'),
(14, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:23:05'),
(15, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:24:20'),
(16, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:24:25'),
(17, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:25:14'),
(18, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:25:27'),
(19, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:26:47'),
(20, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:27:09'),
(21, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:27:11'),
(22, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:27:48'),
(23, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:27:50'),
(24, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:28:21'),
(25, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:28:23'),
(26, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:29:08'),
(27, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:29:17'),
(28, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:29:43'),
(29, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:30:49'),
(30, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 18:32:55'),
(31, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 19:01:28'),
(32, 1, 'Administrador', 'administrador', 'Editou paciente: Emilinha Matos', 'Pacientes', 2, '2025-08-23 19:43:56'),
(33, 1, 'Administrador', 'administrador', 'Editou o convênio (ID 1): Descrição de \'rrrr\' para \'tudo certo\'', 'Convênios', 1, '2025-08-23 19:48:14'),
(34, 1, 'Administrador', 'administrador', 'Editou o convênio (ID 2): Descrição de \'ggg\' para \'tudo certo\'', 'Convênios', 2, '2025-08-23 19:48:35'),
(35, 1, 'Administrador', 'administrador', 'Editou serviço: Clinica Geral', 'Serviços', 5, '2025-08-23 19:48:56'),
(36, 1, 'Administrador', 'administrador', 'Editou serviço: Clinica Geral', 'Serviços', 5, '2025-08-23 19:50:48'),
(37, 1, 'Administrador', 'administrador', 'Editou serviço: Gastroduodenoscopia', 'Serviços', 6, '2025-08-23 19:51:00'),
(38, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 19:54:09'),
(39, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 19:54:39'),
(40, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 19:56:50'),
(41, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 19:57:45'),
(42, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 20:03:27'),
(43, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 20:05:25'),
(44, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 20:08:06'),
(45, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Boa Vida\'', 'Identidade da Clínica', 1, '2025-08-23 20:08:28'),
(46, 8, 'alyssonMaster', 'master', 'Atualizou a identidade da clínica para \'Clínica Médica\'', 'Identidade da Clínica', 1, '2025-08-27 12:37:08'),
(47, 1, 'Administrador', 'administrador', 'Registrou despesa de R$ 300,00 com a descrição: \'Taxa de Agua\'', 'Despesas', 4, '2025-08-30 20:01:30'),
(48, 5, 'Dra Virginia', 'administrador', 'Editou usuário', 'Usuários', 1, '2026-03-06 10:31:53'),
(49, 5, 'Dra Virginia', 'administrador', 'Editou usuário', 'Usuários', 1, '2026-03-06 10:32:00'),
(50, 1, 'Administrador', 'administrador', 'Cadastrou novo usuário', 'Usuários', 11, '2026-03-06 10:34:50'),
(51, 11, 'André Galdino', 'administrador', 'Excluiu o exame (ID 5) de \'Gastroduodenoscopia\' para o paciente \'Jair Bolsonaro\'', 'Exames', 5, '2026-03-06 10:42:48'),
(52, 11, 'André Galdino', 'administrador', 'Excluiu paciente: Jair Bolsonaro', 'Pacientes', 5, '2026-03-06 10:43:02'),
(53, 11, 'André Galdino', 'administrador', 'Registrou despesa de R$ 120,00 com a descrição: \'Papel fotógrafo \'', 'Despesas', 5, '2026-03-06 10:47:22'),
(54, 11, 'André Galdino', 'administrador', 'Editou o exame (ID 7): Paciente de \'Julia Franciara Santos\' para \'Julia Franciara Santos\', Médico de \'Dra Virginia\' para \'Dra Virginia\', Serviço de \'Exame de sangue\' para \'Exame de sangue\', Resultado (conteúdo alterado), Status de \'Agendado\' para \'Realiza', 'Exames', 7, '2026-03-24 08:45:59'),
(55, 11, 'André Galdino', 'administrador', 'Cadastrou exame (ID 8) de \'Gastroduodenoscopia\' para o paciente \'Emilinha Matos\' com o médico \'Dr. Santana\' em 24/03/2026', 'Exames', 8, '2026-03-24 08:54:49');

-- --------------------------------------------------------

--
-- Estrutura para tabela `medicos`
--

CREATE TABLE `medicos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `crm` varchar(20) NOT NULL,
  `especialidade` varchar(100) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `horario_atendimento` text,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `usuario_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `medicos`
--

INSERT INTO `medicos` (`id`, `nome`, `crm`, `especialidade`, `telefone`, `email`, `horario_atendimento`, `criado_em`, `usuario_id`) VALUES
(1, 'Dr. Santana', '433-4', 'Urologista', '(79) 9 88219787', 'dr_santana@gmail.com', '08:00 as 12:00', '2025-08-12 01:54:53', NULL),
(2, 'Dra Amanda Alcantara', '561-4', 'Ginecologista', '79 9 88561223', 'dra_amanda@gmail.com', '08:00 as 12:00', '2025-08-12 02:09:09', NULL),
(3, 'Dra Virginia', '443-0', 'Ginecologista', '79 9 88561223', 'dra_virginia@gmail.com', '08:00 as 12:00', '2025-08-12 02:20:41', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `medico_usuario`
--

CREATE TABLE `medico_usuario` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `medico_usuario`
--

INSERT INTO `medico_usuario` (`id`, `usuario_id`, `medico_id`, `criado_em`) VALUES
(3, 5, 3, '2025-08-12 12:22:50');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pacientes`
--

CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cpf` varchar(14) NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `endereco` text,
  `convenio_id` int(11) DEFAULT NULL,
  `observacoes` text,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `pacientes`
--

INSERT INTO `pacientes` (`id`, `nome`, `cpf`, `data_nascimento`, `telefone`, `email`, `endereco`, `convenio_id`, `observacoes`, `criado_em`) VALUES
(1, 'Sergio Honorino', '12312332144', '1989-01-31', '(79) 9 98748631', 'sergio@gmail.com', 'Rua da paz 76', 1, '', '2025-08-12 01:53:10'),
(2, 'Emilinha Matos', '77788755434', '2002-04-12', '79 9 88204565', 'emilinha@gmail.com', 'rua da curva 54', 2, '', '2025-08-12 13:17:12'),
(3, 'Julia Franciara Santos', '66766512132', '1993-08-19', '79 9 98768879', 'julia_franciara@hotmail.com', 'Av Galvao', 1, '', '2025-08-12 13:25:24'),
(4, 'Sando Magia', '89009032211', '1993-03-23', '(79) 9 98748631', 'magia@gmail.com', 'avenida da luz 45', 1, '', '2025-08-12 14:32:16');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL,
  `consulta_id` int(11) DEFAULT NULL,
  `valor` decimal(10,2) NOT NULL,
  `metodo_pagamento` varchar(50) NOT NULL,
  `data_pagamento` date NOT NULL,
  `observacoes` text,
  `exame_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `pagamentos`
--

INSERT INTO `pagamentos` (`id`, `consulta_id`, `valor`, `metodo_pagamento`, `data_pagamento`, `observacoes`, `exame_id`) VALUES
(1, 4, 140.00, 'dinheiro', '2025-08-12', '', NULL),
(2, 3, 140.00, 'pix', '2025-08-12', '', NULL),
(3, 2, 140.00, 'pix', '2025-08-15', '', NULL),
(5, 7, 140.00, 'dinheiro', '2025-08-12', '', NULL),
(6, 11, 149.00, 'cartao_debito', '2025-08-19', '', NULL),
(7, 16, 129.00, 'cartao_credito', '2025-08-19', '', NULL),
(8, 15, 400.00, 'pix', '2025-08-19', '', NULL),
(12, NULL, 234.00, 'Dinheiro', '2025-08-21', NULL, 1),
(13, NULL, 400.00, 'PIX', '2025-08-21', NULL, 3),
(14, NULL, 400.00, 'PIX', '2025-08-21', NULL, 3),
(15, NULL, 400.00, 'Cartão de Crédito', '2025-08-21', NULL, 4),
(16, NULL, 300.00, 'Cartão de Débito', '2025-08-21', NULL, 5),
(17, NULL, 900.00, 'Cartão de Crédito', '2025-08-21', NULL, 6),
(18, 13, 400.00, 'dinheiro', '2025-08-23', '', NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `prontuarios`
--

CREATE TABLE `prontuarios` (
  `id` int(11) NOT NULL,
  `consulta_id` int(11) DEFAULT NULL,
  `medico_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `data_consulta` datetime NOT NULL,
  `queixa_principal` text,
  `historia_doenca_atual` text,
  `antecedentes_pessoais` text,
  `antecedentes_familiares` text,
  `exame_fisico` text,
  `exames_complementares` text,
  `diagnostico` text,
  `prescricao` text,
  `orientacoes` text,
  `observacoes` text,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `prontuarios`
--

INSERT INTO `prontuarios` (`id`, `consulta_id`, `medico_id`, `paciente_id`, `data_consulta`, `queixa_principal`, `historia_doenca_atual`, `antecedentes_pessoais`, `antecedentes_familiares`, `exame_fisico`, `exames_complementares`, `diagnostico`, `prescricao`, `orientacoes`, `observacoes`, `criado_em`, `atualizado_em`) VALUES
(4, NULL, 3, 1, '2025-08-12 14:24:00', 'Dores na coluna', 'problemas na lombar', 'gasq', 'hewg', 'aet', 'ryqa', '6yeg', 'xrt4tq', '34qsd', 'q34yddddd', '2025-08-12 12:24:29', '2025-08-12 17:18:04'),
(5, NULL, 3, 3, '2025-08-12 15:36:00', 'tasdasd', 'asdaetasd', 'asdgasdga', 'hsdhfsdfhs', 'hwra', 'erhs', 'thhrega', 'wryyasdg', 'asggrgg', 'traasdasdga', '2025-08-12 13:37:05', '2025-08-12 13:37:21'),
(7, NULL, 3, 3, '2025-08-12 16:18:00', 'dsafsdf', 'dga', 'thsddfs', 'fhsdfa', 'asw4s', 'asdgassf', 'asdgasxcca', 'g4tasdzxcvae', 'tfghgjdfg', 'dhdtuara43', '2025-08-12 14:21:56', '2025-08-12 14:21:56'),
(8, 3, 3, 3, '2025-08-12 16:21:00', 'sdfasd', 'sadfasdfdsfas', 'sadf', 'sad', 'dsq4wesa', 'q4tasdfasd', '7ityhjg', 'o8tiyjdfg', '658yvvxz', '98ywe5rddddd', '2025-08-12 14:22:05', '2025-08-14 11:54:54'),
(9, 4, 3, 4, '2025-08-12 16:33:00', '67545', 'sd34q5', 'q4', '', 'rwertwertw', 'ertwerw', 'ewrtwer', '', '', '', '2025-08-12 14:33:45', '2025-08-12 14:34:01'),
(10, 11, 3, 3, '2025-08-19 00:00:00', 'dores nas costas', 'Começou em 2024', 'Minha mãe', 'minha avo', 'exames de alongamento', 'Raio-X', 'bucite', 'Buceticina 20 gm', 'Tomar 2 x ao dia', '', '2025-08-15 01:40:58', '2025-08-15 01:40:58'),
(11, 15, 3, 2, '2025-08-19 00:00:00', 'Dores de cabeças constantess', 'Nenhum', 'Não sei', 'Sem antecedentes para o problema', 'nada', 'hummm', 'Achei um afundamento craniano', 'Morfina para aliviar a dor', 'Buscar um especialista', '', '2025-08-15 14:47:09', '2025-08-15 14:47:09');

-- --------------------------------------------------------

--
-- Estrutura para tabela `receitas`
--

CREATE TABLE `receitas` (
  `id` int(11) NOT NULL,
  `consulta_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `medico_id` int(11) NOT NULL,
  `receita` text NOT NULL,
  `instrucoes_gerais` text,
  `data_receita` date NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `receitas`
--

INSERT INTO `receitas` (`id`, `consulta_id`, `paciente_id`, `medico_id`, `receita`, `instrucoes_gerais`, `data_receita`, `criado_em`, `atualizado_em`) VALUES
(1, 11, 3, 3, 'Pelas penas ela disse que\r\nas coisa não passam\r\nso passaria se abrisse\r\ncomo nunca abre \r\njamais tera como entrar', 'Tudo faz parte do casamento.', '2025-08-14', '2025-08-15 01:56:43', '2025-08-15 01:56:43'),
(2, 15, 2, 3, 'Diprofilatogeno 40mg\r\n4x ao dia\r\npor 30 dias', 'Sempre pela manha tomar o primeiro em jejum.', '2025-08-20', '2025-08-20 22:26:19', '2025-08-20 22:26:19');

-- --------------------------------------------------------

--
-- Estrutura para tabela `servicos`
--

CREATE TABLE `servicos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `preco` decimal(10,2) NOT NULL,
  `tipo` enum('Consulta','Exame') NOT NULL,
  `descricao` text,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `servicos`
--

INSERT INTO `servicos` (`id`, `nome`, `preco`, `tipo`, `descricao`, `data_criacao`) VALUES
(2, 'Exame de Lãmina', 130.00, 'Exame', '', '2025-08-21 23:40:22'),
(3, 'Consulta Oftalmológica', 145.00, 'Consulta', '', '2025-08-21 23:40:22'),
(4, 'Exame de sangue', 90.00, 'Exame', '', '2025-08-21 23:56:10'),
(5, 'Clinica Geral', 200.00, 'Consulta', 'Clinico geral', '2025-08-21 23:57:24'),
(6, 'Gastroduodenoscopia', 250.00, 'Exame', '', '2025-08-21 23:59:09');

-- --------------------------------------------------------

--
-- Estrutura para tabela `solicitacoes_exame`
--

CREATE TABLE `solicitacoes_exame` (
  `id` int(11) NOT NULL,
  `consulta_id` int(11) NOT NULL,
  `descricao` text NOT NULL,
  `data_solicitacao` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `solicitacoes_exame`
--

INSERT INTO `solicitacoes_exame` (`id`, `consulta_id`, `descricao`, `data_solicitacao`) VALUES
(1, 11, 'raiox', '2025-08-15'),
(2, 11, 'Tomografia', '2025-08-15'),
(4, 15, 'topografia', '2025-08-15'),
(5, 15, 'topografia', '2025-08-15'),
(6, 15, 'Raio-X', '2025-08-17');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel_acesso` enum('administrador','recepcao','medico','financeiro','master') NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `nivel_acesso`, `criado_em`) VALUES
(1, 'Administrador', 'admin@clinica.com', '$2y$10$.Bavg06jnApACaHQN5HT3uFhp.a1/jSmABusug44EmHmawczDaHBe', 'administrador', '2025-08-12 00:00:33'),
(2, 'Dr. Santana', 'santana@gmail.com', '$2y$10$fKndLby0mjZTqzg/Ips8wOJrgq1ZWIcZLUFLx61T5zRWsV/34aQNa', 'medico', '2025-08-12 01:26:11'),
(3, 'Sandra Cardoso', 'sandra@gmail.com', '$2y$10$xNwhhWTPUWpEdwzrfK/.Muq24D7BRHY.wFWFarsBV0gnfKLEKU77e', 'recepcao', '2025-08-12 01:26:46'),
(4, 'Dra Amanda Alcantara', 'dra_amanda@gmail.com', '$2y$10$s41qNEMUZeHwSvNEUAt3dudNsgRhOT7YKqCIY86F98aIRpZ9orT0S', 'administrador', '2025-08-12 02:09:09'),
(5, 'Dra Virginia', 'dra_virginia@gmail.com', '$2y$10$o1j0NErCtRpqJ84ZXkkv5uB2j0zp67x3U26tk.SQSBsZbKuKVfmZu', 'administrador', '2025-08-12 02:20:41'),
(6, 'Etelvina', 'etelvina@clinica.com', '$2y$10$Cvwogf6rqDE/yQrhsaSPNeunAnOaMs02uvrDNLuudXmiHAqBIaXgS', 'financeiro', '2025-08-17 15:25:24'),
(7, 'Davi Gabriel', 'davigabriel@clinica.com', '$2y$10$ObXONXZxidPSa2XN/vhihOYa42AYfmGZpi.xwOIXHf7tmA/SIYMny', 'financeiro', '2025-08-17 15:27:16'),
(8, 'alyssonMaster', 'alyssonjoseoficial@gmail.com', '$2y$10$MBoP1EWqv.arEzbfcJSG6eqMTyQr3SCA2RSgBnYVC1Z8nlSxX4J9C', 'master', '2025-08-23 19:02:24'),
(11, 'André Galdino', 'andregaldino@gmail.com', '$2y$10$JzeRGmckOlcVKinmks82OOisxZnksYeupKXnbMbk26VdURWN2ml1y', 'administrador', '2026-03-06 13:34:50');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `agenda_medicos`
--
ALTER TABLE `agenda_medicos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_medico_data_turno` (`medico_id`,`data`,`turno`);

--
-- Índices de tabela `atestados`
--
ALTER TABLE `atestados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consulta_id` (`consulta_id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `medico_id` (`medico_id`);

--
-- Índices de tabela `consultas`
--
ALTER TABLE `consultas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `medico_id` (`medico_id`),
  ADD KEY `fk_consulta_servico` (`servico_id`);

--
-- Índices de tabela `convenios`
--
ALTER TABLE `convenios`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `despesas`
--
ALTER TABLE `despesas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `evolucao_prontuarios`
--
ALTER TABLE `evolucao_prontuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prontuario_id` (`prontuario_id`);

--
-- Índices de tabela `exames`
--
ALTER TABLE `exames`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `medico_id` (`medico_id`),
  ADD KEY `fk_exame_servico` (`servico_id`);

--
-- Índices de tabela `identidade_clinica`
--
ALTER TABLE `identidade_clinica`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `logs_auditoria`
--
ALTER TABLE `logs_auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `medicos`
--
ALTER TABLE `medicos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `medico_usuario`
--
ALTER TABLE `medico_usuario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `medico_id` (`medico_id`);

--
-- Índices de tabela `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD KEY `fk_paciente_convenio` (`convenio_id`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pagamento_consulta` (`consulta_id`);

--
-- Índices de tabela `prontuarios`
--
ALTER TABLE `prontuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consulta_id` (`consulta_id`),
  ADD KEY `medico_id` (`medico_id`),
  ADD KEY `paciente_id` (`paciente_id`);

--
-- Índices de tabela `receitas`
--
ALTER TABLE `receitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consulta_id` (`consulta_id`),
  ADD KEY `paciente_id` (`paciente_id`),
  ADD KEY `medico_id` (`medico_id`);

--
-- Índices de tabela `servicos`
--
ALTER TABLE `servicos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uc_nome` (`nome`);

--
-- Índices de tabela `solicitacoes_exame`
--
ALTER TABLE `solicitacoes_exame`
  ADD PRIMARY KEY (`id`),
  ADD KEY `consulta_id` (`consulta_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agenda_medicos`
--
ALTER TABLE `agenda_medicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de tabela `atestados`
--
ALTER TABLE `atestados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `consultas`
--
ALTER TABLE `consultas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `convenios`
--
ALTER TABLE `convenios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `despesas`
--
ALTER TABLE `despesas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `evolucao_prontuarios`
--
ALTER TABLE `evolucao_prontuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `exames`
--
ALTER TABLE `exames`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `identidade_clinica`
--
ALTER TABLE `identidade_clinica`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `logs_auditoria`
--
ALTER TABLE `logs_auditoria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT de tabela `medicos`
--
ALTER TABLE `medicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `medico_usuario`
--
ALTER TABLE `medico_usuario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `prontuarios`
--
ALTER TABLE `prontuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `receitas`
--
ALTER TABLE `receitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `servicos`
--
ALTER TABLE `servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `solicitacoes_exame`
--
ALTER TABLE `solicitacoes_exame`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `agenda_medicos`
--
ALTER TABLE `agenda_medicos`
  ADD CONSTRAINT `agenda_medicos_ibfk_1` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `atestados`
--
ALTER TABLE `atestados`
  ADD CONSTRAINT `atestados_ibfk_1` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `atestados_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  ADD CONSTRAINT `atestados_ibfk_3` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`);

--
-- Restrições para tabelas `consultas`
--
ALTER TABLE `consultas`
  ADD CONSTRAINT `consultas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consultas_ibfk_2` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_consulta_servico` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `evolucao_prontuarios`
--
ALTER TABLE `evolucao_prontuarios`
  ADD CONSTRAINT `evolucao_prontuarios_ibfk_1` FOREIGN KEY (`prontuario_id`) REFERENCES `prontuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `exames`
--
ALTER TABLE `exames`
  ADD CONSTRAINT `exames_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  ADD CONSTRAINT `exames_ibfk_2` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`),
  ADD CONSTRAINT `fk_exame_servico` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `logs_auditoria`
--
ALTER TABLE `logs_auditoria`
  ADD CONSTRAINT `logs_auditoria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `medicos`
--
ALTER TABLE `medicos`
  ADD CONSTRAINT `fk_usuario_medico` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `medico_usuario`
--
ALTER TABLE `medico_usuario`
  ADD CONSTRAINT `medico_usuario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medico_usuario_ibfk_2` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pacientes`
--
ALTER TABLE `pacientes`
  ADD CONSTRAINT `fk_paciente_convenio` FOREIGN KEY (`convenio_id`) REFERENCES `convenios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `fk_pagamento_consulta` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `prontuarios`
--
ALTER TABLE `prontuarios`
  ADD CONSTRAINT `prontuarios_ibfk_1` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prontuarios_ibfk_2` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prontuarios_ibfk_3` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `receitas`
--
ALTER TABLE `receitas`
  ADD CONSTRAINT `receitas_ibfk_1` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `receitas_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`),
  ADD CONSTRAINT `receitas_ibfk_3` FOREIGN KEY (`medico_id`) REFERENCES `medicos` (`id`);

--
-- Restrições para tabelas `solicitacoes_exame`
--
ALTER TABLE `solicitacoes_exame`
  ADD CONSTRAINT `solicitacoes_exame_ibfk_1` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
