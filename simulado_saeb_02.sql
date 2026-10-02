-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema industria_db
-- -----------------------------------------------------

CREATE SCHEMA IF NOT EXISTS industria_db DEFAULT CHARACTER SET utf8;
USE industria_db;

-- -----------------------------------------------------
-- Remover tabelas existentes
-- -----------------------------------------------------

DROP TABLE IF EXISTS industria_db.movimentacoes;
DROP TABLE IF EXISTS industria_db.insumos;
DROP TABLE IF EXISTS industria_db.usuarios;

-- -----------------------------------------------------
-- Table industria_db.usuarios
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS industria_db.usuarios (
  id INT NOT NULL AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  cargo VARCHAR(50) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,

  PRIMARY KEY (id),
  UNIQUE INDEX email_UNIQUE (email ASC)
)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table industria_db.insumos
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS industria_db.insumos (
  id INT NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(45) NOT NULL,
  nome VARCHAR(150) NOT NULL,
  categoria VARCHAR(100) NOT NULL,
  unidade_medida VARCHAR(20) NOT NULL,
  custo DECIMAL(10,2) NOT NULL,
  estoque_atual INT NOT NULL DEFAULT 0,
  estoque_minimo INT NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,

  PRIMARY KEY (id),
  UNIQUE INDEX codigo_UNIQUE (codigo ASC)
)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table industria_db.movimentacoes
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS industria_db.movimentacoes (
  id INT NOT NULL AUTO_INCREMENT,
  tipo INT NOT NULL COMMENT '1- entrada 2- saída',
  data_movimentacao DATE NOT NULL,
  quantidade INT NOT NULL,
  saldo_anterior INT NOT NULL,
  saldo_posterior INT NOT NULL,
  observacao VARCHAR(255) NULL,
  usuarios_id INT NOT NULL,
  insumos_id INT NOT NULL,

  PRIMARY KEY (id),

  INDEX fk_movimentacoes_usuarios_idx (usuarios_id ASC),
  INDEX fk_movimentacoes_insumos_idx (insumos_id ASC),

  CONSTRAINT fk_movimentacoes_usuarios
    FOREIGN KEY (usuarios_id)
    REFERENCES industria_db.usuarios (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,

  CONSTRAINT fk_movimentacoes_insumos
    FOREIGN KEY (insumos_id)
    REFERENCES industria_db.insumos (id)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
)
ENGINE = InnoDB;

-- -----------------------------------------------------
-- INSERÇÕES - USUÁRIOS
-- -----------------------------------------------------

INSERT INTO usuarios
  (id, nome, email, senha, cargo, ativo)
VALUES
  (1, 'Arthur Santos', 'controle.almox@fabrica.net', '123', 'Almoxarife', 1),
  (2, 'Israel Tassi', 'diretoria.operacional@fabrica.net', '123', 'Gerente', 1),
  (3, 'Carlos Ferreira', 'materiais.producao@fabrica.net', '123', 'Almoxarife', 1);

-- -----------------------------------------------------
-- INSERÇÕES - INSUMOS
-- -----------------------------------------------------

INSERT INTO insumos
  (id, codigo, nome, categoria, unidade_medida, custo,
   estoque_atual, estoque_minimo, ativo)
VALUES
  (1, 'INS001', 'Chapa de Aço', 'Aço', 'unidade',
   350.00, 50, 10, 1),

  (2, 'INS002', 'Perfil Metálico', 'Estruturas Metálicas', 'unidade',
   120.00, 30, 8, 1),

  (3, 'INS003', 'Parafuso Sextavado', 'Fixadores', 'unidade',
   2.50, 100, 20, 1),

  (4, 'INS004', 'Tinta Industrial', 'Pintura', 'litro',
   45.00, 15, 5, 1),

  (5, 'INS005', 'Componente Eletrônico', 'Eletrônicos', 'unidade',
   85.00, 12, 5, 1);

-- -----------------------------------------------------
-- INSERÇÕES - MOVIMENTAÇÕES
-- -----------------------------------------------------

INSERT INTO movimentacoes
  (tipo, data_movimentacao, quantidade, saldo_anterior,
   saldo_posterior, observacao, usuarios_id, insumos_id)
VALUES

  -- Entrada de chapas de aço
  (1, '2026-09-25', 20, 30, 50,
   'Recebimento de compra de chapas de aço', 1, 1),

  -- Saída de perfil metálico
  (2, '2026-09-26', 10, 40, 30,
   'Envio de material para linha de produção', 3, 2),

  -- Saída de parafusos
  (2, '2026-09-27', 30, 130, 100,
   'Envio de parafusos para produção', 1, 3),

  -- Entrada de tinta
  (1, '2026-09-28', 5, 10, 15,
   'Recebimento de tinta industrial', 2, 4),

  -- Saída de componentes eletrônicos
  (2, '2026-09-29', 8, 20, 12,
   'Envio de componentes para produção', 3, 5);

-- -----------------------------------------------------
-- Finalização
-- -----------------------------------------------------

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;