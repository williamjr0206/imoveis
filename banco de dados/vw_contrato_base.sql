-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 18/02/2026 às 10:03
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
-- Estrutura para view `vw_contrato_base`
--

CREATE VIEW `vw_contrato_base`  AS SELECT `c`.`id` AS `contrato_id`, `c`.`data_inicio` AS `data_inicio`, `c`.`data_fim` AS `data_fim`, `c`.`dia_vencimento` AS `dia_vencimento`, `c`.`prazo_meses` AS `prazo_meses`, `c`.`finalidade` AS `finalidade`, `c`.`indice_reajuste` AS `indice_reajuste`, `c`.`tipo_contrato` AS `tipo_contrato`, `c`.`tipo_garantia` AS `tipo_garantia`, `c`.`valor_caucao` AS `valor_caucao`, `i`.`descricao` AS `imovel_descricao`, `i`.`endereco` AS `endereco`, `i`.`cidade` AS `cidade`, `i`.`estado` AS `estado`, `i`.`valor_aluguel` AS `valor_aluguel`, `p`.`nome` AS `locador_nome`, `p`.`cpf` AS `locador_cpf`, `iq`.`nome` AS `inquilino_nome`, `iq`.`tipo_pessoa` AS `tipo_pessoa`, `iq`.`cpf` AS `cpf`, `iq`.`cnpj` AS `cnpj`, `iq`.`endereco` AS `inquilino_endereco`, `iq`.`representante_nome` AS `representante_nome`, `iq`.`representante_cpf` AS `representante_cpf` FROM (((`contratos` `c` join `imoveis` `i` on((`i`.`id` = `c`.`imovel_id`))) join `proprietarios` `p` on((`p`.`id` = `i`.`proprietario_id`))) join `inquilinos` `iq` on((`iq`.`id` = `c`.`inquilino_id`))) ;

--
-- VIEW `vw_contrato_base`
-- Dados: Nenhum
--

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
