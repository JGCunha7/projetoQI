<?php
// Arquivo: perfil.php
require_once 'db_connection.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    redirect('cliente-login.php'); // Redireciona para o login de cliente se não estiver logado
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Lógica para carregar dados do usuário
$user_data = [];
$stmt = $conn->prepare("SELECT username, email, full_name, address, phone, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 1) {
    $user_data = $result->fetch_assoc();
} else {
    // Se o usuário não for encontrado (improvável se logado), redirecionar para logout
    session_destroy();
    redirect('cliente-login.php');
}
$stmt->close();

// Lógica para atualizar dados do usuário
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_email = trim($_POST['email'] ?? '');
    $new_full_name = trim($_POST['full_name'] ?? '');
    $new_address = trim($_POST['address'] ?? '');
    $new_phone = trim($_POST['phone'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validação básica
    if (!empty($new_password) && $new_password !== $confirm_password) {
        $message = "As novas senhas não coincidem.";
        $message_type = 'error';
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $message = "A nova senha deve ter pelo menos 6 caracteres.";
        $message_type = 'error';
    } else {
        $update_query = "UPDATE users SET email = ?, full_name = ?, address = ?, phone = ? WHERE id = ?";
        $params = "ssssi";
        $values = [$new_email, $new_full_name, $new_address, $new_phone, $user_id];

        if (!empty($new_password)) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET email = ?, full_name = ?, address = ?, phone = ?, password_hash = ? WHERE id = ?";
            $params = "sssssi";
            $values = [$new_email, $new_full_name, $new_address, $new_phone, $password_hash, $user_id];
        }

        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param($params, ...$values);

        if ($update_stmt->execute()) {
            $message = "Perfil atualizado com sucesso!";
            $message_type = 'success';
            // Atualizar os dados na sessão após a atualização do banco de dados para refletir na navegação
            $_SESSION['email'] = $new_email;
            $_SESSION['full_name'] = $new_full_name;
            $_SESSION['address'] = $new_address;
            $_SESSION['phone'] = $new_phone;
            // Recarregar os dados do usuário para garantir que o formulário exiba os valores mais recentes
            $user_data['email'] = $new_email;
            $user_data['full_name'] = $new_full_name;
            $user_data['address'] = $new_address;
            $user_data['phone'] = $new_phone;

        } else {
            $message = "Erro ao atualizar perfil: " . $conn->error;
            $message_type = 'error';
        }
        $update_stmt->close();
    }
}

// Lógica de Logout
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy(); // Destrói todas as variáveis de sessão
    redirect('cliente-login.php'); // Redireciona para a página de login de cliente
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bel Vestit - Meu Perfil</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header class="main-header">
        <div class="logo">
            <img src="logo.png" alt="Logo Bel Vestit">
        </div>
        <h1>Bel Vestit</h1>
        <p>Meu Perfil</p>
        <nav class="top-nav">
            <a href="index.php" class="nav-icon-link">
                <i class="fas fa-store"></i> Loja
            </a>
            <a href="carrinho.php" class="nav-icon-link">
                <i class="fas fa-shopping-cart"></i> Carrinho
            </a>
            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'employee'): ?>
            <a href="admin.php" class="nav-icon-link">
                <i class="fas fa-user-shield"></i> Admin
            </a>
            <?php endif; ?>
            <a href="perfil.php?logout=true" class="nav-icon-link logout-link">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </nav>
    </header>

    <main class="main-container perfil-container">
        <h2>Detalhes do Perfil</h2>

        <?php if (!empty($message)): ?>
            <p class="message <?php echo $message_type; ?>"><?php echo $message; ?></p>
        <?php endif; ?>

        <form action="perfil.php" method="POST">
            <div class="form-group">
                <label for="username">Nome de Usuário:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled>
                <small>O nome de usuário não pode ser alterado.</small>
            </div>

            <div class="form-group">
                <label for="role">Tipo de Usuário:</label>
                <input type="text" id="role" name="role" value="<?php echo htmlspecialchars(ucfirst($user_data['role'])); ?>" disabled>
                <small>Seu papel no sistema.</small>
            </div>

            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="full_name">Nome Completo:</label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="address">Endereço:</label>
                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="phone">Telefone:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
            </div>

            <h3>Alterar Senha (opcional)</h3>
            <div class="form-group">
                <label for="new_password">Nova Senha:</label>
                <input type="password" id="new_password" name="new_password">
                <small>Deixe em branco para não alterar a senha.</small>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmar Nova Senha:</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>

            <button type="submit" class="place-order-btn">Salvar Alterações</button>
        </form>
    </main>

    <footer class="main-footer">
        <p>&copy; 2025 Bel Vestit. Todos os direitos reservados.</p>
    </footer>
</body>
</html>