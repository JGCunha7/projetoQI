<?php
// Arquivo: cliente-login.php
require_once 'db_connection.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = "Por favor, preencha todos os campos.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password_hash, role, email, full_name, address, phone FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            // Permite login para qualquer role nesta página
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['address'] = $user['address'];
                $_SESSION['phone'] = $user['phone'];

                redirect('perfil.php'); // Redireciona para o perfil do cliente
            } else {
                $error_message = "Nome de usuário ou senha incorretos.";
            }
        } else {
            $error_message = "Nome de usuário ou senha incorretos.";
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
    <title>Bel Vestit - Login Cliente</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="admin-login-body">
    <div class="login-container">
        <div class="logo">
            <img src="logo.png" alt="Logo Bel Vestit">
        </div>
        <h2>Acesso Cliente</h2>
        <form class="login-form" action="cliente-login.php" method="POST">
            <?php if (!empty($error_message)): ?>
                <p class="message error"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <div class="form-group">
                <label for="username">Usuário:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-btn">Entrar</button>
        </form>
        <p>Não tem uma conta? <a href="cliente-cadastro.php" class="back-to-shop">Cadastre-se aqui</a></p>
        <p><a href="admin-login.php" class="back-to-shop">Login Admin/Funcionário</a></p>
        <p><a href="index.php" class="back-to-shop">Voltar para a Loja</a></p>
    </div>
</body>
</html>