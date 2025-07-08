<?php
// Arquivo: admin-categories.php
require_once 'db_connection.php';

// Proteção da página: apenas admins podem acessar esta página de gestão de categorias
// Se quiser que 'employee' também possa gerenciar categorias, ajuste a condição.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('admin-login.php'); // Redireciona se não for admin
}

$message = '';
$message_type = '';
$editing_category = null; // Armazena dados da categoria se estiver em modo de edição

// Lógica de Criar/Editar Categoria
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_category'])) {
    $category_id = $_POST['category_id'] ?? null; // ID só existirá se for edição
    $name = trim($_POST['categoryName'] ?? '');
    $description = trim($_POST['categoryDescription'] ?? '');

    // Validação básica
    if (empty($name)) {
        $message = "Nome da categoria é obrigatório.";
        $message_type = 'error';
    } else {
        if ($category_id) { // Modo de Edição
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $description, $category_id);
            if ($stmt->execute()) {
                $message = "Categoria atualizada com sucesso!";
                $message_type = 'success';
            } else {
                // Erro de duplicidade de nome (UNIQUE constraint)
                if ($stmt->errno == 1062) { // MySQL error code for duplicate entry
                    $message = "Erro: Já existe uma categoria com este nome.";
                    $message_type = 'error';
                } else {
                    $message = "Erro ao atualizar categoria: " . $stmt->error;
                    $message_type = 'error';
                }
            }
        } else { // Modo de Criação
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
            if ($stmt->execute()) {
                $message = "Categoria adicionada com sucesso!";
                $message_type = 'success';
            } else {
                // Erro de duplicidade de nome (UNIQUE constraint)
                if ($stmt->errno == 1062) { // MySQL error code for duplicate entry
                    $message = "Erro: Já existe uma categoria com este nome.";
                    $message_type = 'error';
                } else {
                    $message = "Erro ao adicionar categoria: " . $stmt->error;
                    $message_type = 'error';
                }
            }
        }
        $stmt->close();
    }
}

// Lógica de Excluir Categoria
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $category_id = intval($_GET['id']);
    
    // Opcional: Verificar se há produtos associados a esta categoria antes de deletar
    // Como a FK tem ON DELETE SET NULL, os produtos terão seu category_id como NULL,
    // então não há problema em deletar a categoria diretamente.
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    if ($stmt->execute()) {
        $message = "Categoria excluída com sucesso!";
        $message_type = 'success';
    } else {
        $message = "Erro ao excluir categoria: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
    redirect('admin-categories.php'); // Redireciona para remover os parâmetros da URL
}

// Lógica para carregar dados para edição (se 'edit' for passado na URL)
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $category_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT id, name, description FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $editing_category = $result->fetch_assoc();
    }
    $stmt->close();
}

// Buscar todas as categorias para a listagem
$categories = [];
$sql_categories = "SELECT id, name, description FROM categories ORDER BY name ASC";
$result_categories = $conn->query($sql_categories);
if ($result_categories->num_rows > 0) {
    while ($row = $result_categories->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bel Vestit - Gerenciar Categorias</title>
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
        <p>Gerenciamento de Categorias</p>
        <nav class="top-nav">
            <a href="admin.php" class="nav-icon-link">
                <i class="fas fa-box-open"></i> Produtos
            </a>
            <a href="perfil.php" class="nav-icon-link">
                <i class="fas fa-user"></i> Meu Perfil
            </a>
            <a href="perfil.php?logout=true" class="nav-icon-link logout-link">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </nav>
    </header>

    <main class="admin-container">
        <h2>Gerenciamento de Categorias</h2>

        <?php if (!empty($message)): ?>
            <p class="message <?php echo $message_type; ?>"><?php echo $message; ?></p>
        <?php endif; ?>

        <section class="add-product-section">
            <h3><?php echo ($editing_category ? 'Editar' : 'Adicionar Nova'); ?> Categoria</h3>
            <form class="product-form" action="admin-categories.php" method="POST">
                <?php if ($editing_category): ?>
                    <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($editing_category['id']); ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="category-name">Nome da Categoria:</label>
                    <input type="text" id="category-name" name="categoryName" value="<?php echo htmlspecialchars($editing_category['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="category-description">Descrição:</label>
                    <textarea id="category-description" name="categoryDescription" rows="3"><?php echo htmlspecialchars($editing_category['description'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="submit_category" class="submit-product-btn">Salvar Categoria</button>
            </form>
        </section>

        <section class="product-list-admin">
            <h3>Categorias Atuais</h3>
            <?php if (empty($categories)): ?>
                <p style="text-align: center; font-size: 1.2em; color: var(--color-text-dark);">Nenhuma categoria cadastrada.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td data-label="ID:"><?php echo htmlspecialchars($cat['id']); ?></td>
                                <td data-label="Nome:"><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td data-label="Descrição:"><?php echo htmlspecialchars(substr($cat['description'], 0, 80)) . (strlen($cat['description']) > 80 ? '...' : ''); ?></td>
                                <td class="actions-cell" data-label="Ações:">
                                    <a href="admin-categories.php?action=edit&id=<?php echo htmlspecialchars($cat['id']); ?>" class="edit-btn"><i class="fas fa-edit"></i> Editar</a>
                                    <button type="button" class="delete-btn" data-id="<?php echo htmlspecialchars($cat['id']); ?>"><i class="fas fa-trash-alt"></i> Deletar</button>
                                    <div class="confirm-delete">
                                        <p>Tem certeza?</p>
                                        <a href="admin-categories.php?action=delete&id=<?php echo htmlspecialchars($cat['id']); ?>" class="confirm-yes"><i class="fas fa-check"></i> Sim</a>
                                        <button type="button" class="confirm-no"><i class="fas fa-times"></i> Não</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>

    <footer class="main-footer">
        <p>&copy; 2025 Bel Vestit. Gerenciamento de Categorias.</p>
    </footer>

    <script>
        // Lógica de confirmação de deleção (mantida em JS, apenas visual)
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                this.nextElementSibling.style.display = 'flex'; // Mostra a caixa de confirmação
                this.style.display = 'none'; // Esconde o botão de deletar
            });
        });

        document.querySelectorAll('.confirm-no').forEach(button => {
            button.addEventListener('click', function() {
                this.parentElement.style.display = 'none'; // Esconde a caixa de confirmação
                this.parentElement.previousElementSibling.style.display = 'inline-block'; // Mostra o botão de deletar
            });
        });
    </script>
</body>
</html>