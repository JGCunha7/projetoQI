-- Arquivo: database.sql
-- Base de Dados: belvestit_db (Você deve criar esta base de dados primeiro, se ainda não o fez)

-- Tabela de Usuários
-- IF NOT EXISTS previne erro se a tabela já existir e você não a deletou manualmente
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee', 'standard') DEFAULT 'standard' NOT NULL,
    email VARCHAR(100) UNIQUE,
    full_name VARCHAR(100),
    address VARCHAR(255),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Categorias
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserir algumas categorias padrão (APENAS SE A TABELA ESTIVER VAZIA - INSERT IGNORE)
-- INSERT IGNORE INTO categories (name) VALUES
-- ('Camisetas'),
-- ('Calças'),
-- ('Vestidos'),
-- ('Casacos'),
-- ('Botas'),
-- ('Sapatos'),
-- ('Acessórios'),
-- ('Moda Masculina'),
-- ('Moda Feminina'),
-- ('Moda Infantil');


-- Tabela de Produtos (Com FOREIGN KEY para categories)
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    category_id INT, -- Chave estrangeira para a tabela categories
    stock_quantity INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    -- ON DELETE SET NULL: se uma categoria for excluída, os produtos associados a ela terão category_id NULL
);