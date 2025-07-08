<?php
// Arquivo: cliente-cadastro.php
require_once 'db_connection.php';

$message = '';
$message_type = ''; // 'success' or 'error'

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['newUsername'] ?? '';
    $password = $_POST['newPassword'] ?? '';
    $confirm_password = $_POST['confirmPassword'] ?? '';

    // Validação de entrada
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $message = "Por favor, preencha todos os campos.";
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = "As senhas não coincidem.";
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = "A senha deve ter pelo menos 6 caracteres.";
        $message_type = 'error';
    } else {
        // Verificar se o nome de usuário já existe
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Nome de usuário já existe. Por favor, escolha outro.";
            $message_type = 'error';
        } else {
            // Hash da senha para segurança
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Esta página sempre cria usuários 'standard'
            $final_role = 'standard';
            
            $insert_stmt = $conn->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $username, $password_hash, $final_role);

            if ($insert_stmt->execute()) {
                $message = "Conta criada com sucesso! Você já pode fazer login.";
                $message_type = 'success';
            } else {
                $message = "Erro ao criar conta: " . $conn->error;
                $message_type = 'error';
            }
            $insert_stmt->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bel Vestit - Cadastro Cliente</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="admin-login-body">
    <div class="login-container register-container">
        <div class="logo">
            <img src="logo.png" alt="Logo Bel Vestit">
        </div>
        <h2>Criar Conta de Cliente</h2>
        <form class="register-form" action="cliente-cadastro.php" method="POST">
            <?php if (!empty($message)): ?>
                <p class="message <?php echo $message_type; ?>"><?php echo $message; ?></p>
            <?php endif; ?>
            <div class="form-group">
                <label for="new-username">Nome de Usuário:</label>
                <input type="text" id="new-username" name="newUsername" required>
            </div>
            <div class="form-group">
                <label for="new-password">Senha:</label>
                <input type="password" id="new-password" name="newPassword" required>
            </div>
            <div class="form-group">
                <label for="confirm-password">Confirmar Senha:</label>
                <input type="password" id="confirm-password" name="confirmPassword" required>
            </div>
            
            <button type="submit" class="login-btn register-btn-main">Registrar</button>
        </form>
        <p>Já tem uma conta? <a href="cliente-login.php" class="back-to-shop">Fazer Login</a></p>
        <p><a href="admin-login.php" class="back-to-shop">Login Admin/Funcionário</a></p>
        <p><a href="index.php" class="back-to-shop">Voltar para a Loja</a></p>
    </div>
</body>
</html>