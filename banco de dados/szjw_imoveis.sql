-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 28/05/2026 às 14:50
-- Versão do servidor: 5.7.44
-- Versão do PHP: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `szjw_fazevedo`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `contratos`
--

CREATE TABLE `contratos` (
  `id` int(11) NOT NULL,
  `imovel_id` int(11) DEFAULT NULL,
  `inquilino_id` int(11) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `dia_vencimento` int(11) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `tipo_contrato` enum('RESIDENCIAL','NAO_RESIDENCIAL') NOT NULL DEFAULT 'RESIDENCIAL',
  `finalidade` varchar(255) DEFAULT NULL,
  `prazo_meses` int(11) DEFAULT NULL,
  `indice_reajuste` varchar(50) DEFAULT NULL,
  `tipo_garantia` varchar(30) DEFAULT NULL,
  `valor_caucao` decimal(10,2) DEFAULT NULL,
  `multa_percentual` decimal(5,2) NOT NULL DEFAULT '10.00',
  `juros_percentual` decimal(5,2) NOT NULL DEFAULT '1.00'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `imoveis`
--

CREATE TABLE `imoveis` (
  `id` int(11) NOT NULL,
  `proprietario_id` int(11) DEFAULT NULL,
  `descricao` varchar(200) DEFAULT NULL,
  `endereco` varchar(200) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `valor_aluguel` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `cidade` varchar(200) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `inquilinos`
--

CREATE TABLE `inquilinos` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) DEFAULT NULL,
  `cnpj` varchar(18) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `tipo_pessoa` enum('PF','PJ') NOT NULL DEFAULT 'PF',
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT '1',
  `representante_nome` varchar(120) DEFAULT NULL,
  `representante_cpf` varchar(14) DEFAULT NULL,
  `representante_documento` varchar(20) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL,
  `contrato_id` int(11) DEFAULT NULL,
  `mes_referencia` date DEFAULT NULL,
  `multa` decimal(10,2) DEFAULT '0.00',
  `juros` decimal(10,2) DEFAULT '0.00',
  `valor_total` decimal(10,2) DEFAULT NULL,
  `data_vencimento` date DEFAULT NULL,
  `valor_pago` decimal(10,2) DEFAULT NULL,
  `data_pagamento` date DEFAULT NULL,
  `observacao` varchar(255) DEFAULT NULL,
  `status` enum('PENDENTE','PAGO','ATRASADO') DEFAULT NULL,
  `criado_por` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `proprietarios`
--

CREATE TABLE `proprietarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `perfil` enum('ADMIN','OPERADOR','CONSULTA') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Índices de tabela `contratos`
--
ALTER TABLE `contratos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `imovel_id` (`imovel_id`),
  ADD KEY `inquilino_id` (`inquilino_id`);

--
-- Índices de tabela `imoveis`
--
ALTER TABLE `imoveis`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `inquilinos`
--
ALTER TABLE `inquilinos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `proprietarios`
--
ALTER TABLE `proprietarios`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT de tabela `contratos`
--
ALTER TABLE `contratos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `imoveis`
--
ALTER TABLE `imoveis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `inquilinos`
--
ALTER TABLE `inquilinos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `proprietarios`
--
ALTER TABLE `proprietarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
