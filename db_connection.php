<?php
// Arquivo: db_connection.php

// Dados de conexão com o banco de dados
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // <--- ALTERE para o seu usuário do MySQL
define('DB_PASSWORD', '');     // <--- ALTERE para a sua senha do MySQL (geralmente vazio para XAMPP/WAMP padrão)
define('DB_NAME', 'belvestit_db'); // <--- ALTERE para o nome do seu banco de dados (certifique-se que é 'belvestit_db')

// Tentar conectar ao banco de dados
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verificar a conexão
if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

// Opcional: Definir o charset para UTF-8 para evitar problemas com caracteres especiais
$conn->set_charset("utf8mb4");

// Iniciar a sessão PHP, essencial para o carrinho e autenticação
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Função utilitária para redirecionamento
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Função para verificar se o usuário está logado e tem a role necessária
function check_login_and_role($required_role = null) {
    if (!isset($_SESSION['user_id'])) {
        redirect('admin-login.php'); // Não logado, redireciona para o login de admin
    }

    // Se uma role específica for necessária e o usuário não tiver, redireciona
    if ($required_role && $_SESSION['role'] !== $required_role) {
        // Redireciona para a página principal (loja) ou para o perfil, se não tiver a role adequada
        redirect('index.php');
    }
}
?>