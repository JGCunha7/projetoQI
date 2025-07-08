<?php
// Arquivo: admin-cadastro.php
require_once 'db_connection.php';

$message = '';
$message_type = ''; // 'success' or 'error'

// Definindo os códigos de segurança distintos (PARA DEMONSTRAÇÃO!)
define('ADMIN_SECURITY_CODE', 'admin123'); // <--- ALTERE para um código MUITO SEGURO!
define('EMPLOYEE_SECURITY_CODE', 'func123');   // <--- ALTERE para um código SEGURO!

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['newUsername'] ?? '';
    $password = $_POST['newPassword'] ?? '';
    $confirm_password = $_POST['confirmPassword'] ?? '';
    $selected_role = $_POST['selected_role'] ?? ''; // Nova variável para a escolha do rádio
    $security_code_input = $_POST['security_code'] ?? ''; // Campo do código de segurança

    // Validação de entrada
    if (empty($username) || empty($password) || empty($confirm_password) || empty($selected_role)) {
        $message = "Por favor, preencha todos os campos e selecione o tipo de conta.";
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = "As senhas não coincidem.";
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = "A senha deve ter pelo menos 6 caracteres.";
        $message_type = 'error';
    } else {
        // Lógica de validação do código de segurança com base na role selecionada
        $is_code_correct = false;
        $final_role = '';

        if ($selected_role === 'admin') {
            if ($security_code_input === ADMIN_SECURITY_CODE) {
                $is_code_correct = true;
                $final_role = 'admin';
            } else {
                $message = "Código de segurança incorreto para criar conta de Administrador.";
                $message_type = 'error';
            }
        } elseif ($selected_role === 'employee') {
            if ($security_code_input === EMPLOYEE_SECURITY_CODE) {
                $is_code_correct = true;
                $final_role = 'employee';
            } else {
                $message = "Código de segurança incorreto para criar conta de Funcionário.";
                $message_type = 'error';
            }
        } else {
            // Isso não deveria acontecer se o HTML estiver correto, mas é uma salvaguarda
            $message = "Tipo de conta inválido selecionado.";
            $message_type = 'error';
        }

        if ($is_code_correct) {
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
                
                $insert_stmt = $conn->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("sss", $username, $password_hash, $final_role);

                if ($insert_stmt->execute()) {
                    $message = "Conta de " . ucfirst($final_role) . " criada com sucesso! Você já pode fazer login.";
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
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bel Vestit - Cadastro Admin/Funcionário</title>
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
        <h2>Criar Conta de Administrador/Funcionário</h2>
        <form class="register-form" action="admin-cadastro.php" method="POST">
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

            <div class="form-group role-selection">
                <label>Tipo de Conta:</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="selected_role" value="admin" onchange="updateSecurityCodeHint()" <?php echo (isset($_POST['selected_role']) && $_POST['selected_role'] === 'admin') ? 'checked' : ''; ?>> Administrador
                    </label>
                    <label>
                        <input type="radio" name="selected_role" value="employee" onchange="updateSecurityCodeHint()" <?php echo (isset($_POST['selected_role']) && $_POST['selected_role'] === 'employee') ? 'checked' : ''; ?>> Funcionário
                    </label>
                </div>
            </div>

            <div class="form-group security-code-group">
                <label for="security-code">Código de Segurança:</label>
                <input type="password" id="security-code" name="security_code" required>
                <small id="security-code-hint">Selecione o tipo de conta acima.</small>
            </div>
            
            <button type="submit" class="login-btn register-btn-main">Registrar</button>
        </form>
        <p>Já tem uma conta? <a href="admin-login.php" class="back-to-shop">Fazer Login Admin/Funcionário</a></p>
        <p><a href="cliente-login.php" class="back-to-shop">Login Cliente</a></p>
        <p><a href="index.php" class="back-to-shop">Voltar para a Loja</a></p>
    </div>

    <script>
        function updateSecurityCodeHint() {
            var selectedRole = document.querySelector('input[name="selected_role"]:checked');
            var hintElement = document.getElementById('security-code-hint');
            
            if (selectedRole) {
                if (selectedRole.value === 'admin') {
                    hintElement.textContent = "Código de segurança para contas de Administrador.";
                } else if (selectedRole.value === 'employee') {
                    hintElement.textContent = "Código de segurança para contas de Funcionário.";
                } else {
                    hintElement.textContent = "Selecione o tipo de conta para ver o código necessário.";
                }
            } else {
                hintElement.textContent = "Selecione o tipo de conta acima para ver o código necessário.";
            }
        }
        // Chamada inicial para definir a dica ao carregar a página
        document.addEventListener('DOMContentLoaded', updateSecurityCodeHint);
    </script>
</body>
</html>
