<?php
// Arquivo: admin.php
require_once 'db_connection.php';

// Proteção da página: apenas admins e employees podem acessar
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'employee')) {
    redirect('admin-login.php'); // Redireciona se não for admin nem employee
}


$message = '';
$message_type = '';
$editing_product = null; // Armazena dados do produto se estiver em modo de edição

// Lógica para buscar categorias para o formulário de produtos
$categories_for_form = [];
$cat_stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY name ASC");
$cat_stmt->execute();
$cat_result = $cat_stmt->get_result();
while ($row = $cat_result->fetch_assoc()) {
    $categories_for_form[] = $row;
}
$cat_stmt->close();


// Lógica de Criar/Editar Produto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_product'])) {
    $product_id = $_POST['product_id'] ?? null; // ID só existirá se for edição
    $name = trim($_POST['productName'] ?? '');
    $description = trim($_POST['productDescription'] ?? '');
    $price = floatval(str_replace(',', '.', $_POST['productPrice'] ?? 0)); // Converte vírgula para ponto e para float
    $image_url = trim($_POST['productImage'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0); // Agora é category_id
    $stock_quantity = intval($_POST['stockQuantity'] ?? 0);

    // Validação básica
    if (empty($name) || empty($price) || $category_id == 0) {
        $message = "Nome do produto, preço e categoria são obrigatórios.";
        $message_type = 'error';
    } elseif ($price <= 0) {
        $message = "O preço deve ser um valor positivo.";
        $message_type = 'error';
    } else {
        if ($product_id) { // Modo de Edição
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, image_url = ?, category_id = ?, stock_quantity = ? WHERE id = ?");
            $stmt->bind_param("ssdsiii", $name, $description, $price, $image_url, $category_id, $stock_quantity, $product_id);
            if ($stmt->execute()) {
                $message = "Produto atualizado com sucesso!";
                $message_type = 'success';
            } else {
                $message = "Erro ao atualizar produto: " . $stmt->error;
                $message_type = 'error';
            }
        } else { // Modo de Criação
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, image_url, category_id, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsii", $name, $description, $price, $image_url, $category_id, $stock_quantity);
            if ($stmt->execute()) {
                $message = "Produto adicionado com sucesso!";
                $message_type = 'success';
            } else {
                $message = "Erro ao adicionar produto: " . $stmt->error;
                $message_type = 'error';
            }
        }
        $stmt->close();
    }
}

// Lógica de Excluir Produto
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    if ($stmt->execute()) {
        $message = "Produto excluído com sucesso!";
        $message_type = 'success';
    } else {
        $message = "Erro ao excluir produto: " . $stmt->error;
        $message_type = 'error';
    }
    $stmt->close();
    redirect('admin.php'); // Redireciona para remover os parâmetros da URL
}

// Lógica para carregar dados para edição (se 'edit' for passado na URL)
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    // Busca o produto, incluindo category_id
    $stmt = $conn->prepare("SELECT id, name, description, price, image_url, category_id, stock_quantity FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $editing_product = $result->fetch_assoc();
    }
    $stmt->close();
}

// Buscar todos os produtos para a listagem (JOIN para exibir o nome da categoria)
$products = [];
$sql_products = "SELECT P.id, P.name, P.price, C.name AS category_name, P.stock_quantity FROM products AS P LEFT JOIN categories AS C ON P.category_id = C.id ORDER BY P.id DESC";
$result_products = $conn->query($sql_products);
if ($result_products->num_rows > 0) {
    while ($row = $result_products->fetch_assoc()) {
        $products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bel Vestit - Painel Administrativo</title>
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
        <p>Painel Administrativo</p>
        <nav class="top-nav">
            <a href="index.php" class="nav-icon-link">
                <i class="fas fa-store"></i> Ver Loja
            </a>
            <a href="admin-categories.php" class="nav-icon-link">
                <i class="fas fa-tags"></i> Categorias
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
        <h2>Gerenciamento de Produtos</h2>

        <?php if (!empty($message)): ?>
            <p class="message <?php echo $message_type; ?>"><?php echo $message; ?></p>
        <?php endif; ?>

        <section class="add-product-section">
            <h3><?php echo ($editing_product ? 'Editar' : 'Adicionar Novo'); ?> Produto</h3>
            <form class="product-form" action="admin.php" method="POST">
                <?php if ($editing_product): ?>
                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($editing_product['id']); ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="product-name">Nome do Produto:</label>
                    <input type="text" id="product-name" name="productName" value="<?php echo htmlspecialchars($editing_product['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="product-description">Descrição:</label>
                    <textarea id="product-description" name="productDescription" rows="4"><?php echo htmlspecialchars($editing_product['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="product-price">Preço:</label>
                    <input type="number" id="product-price" name="productPrice" step="0.01" min="0" value="<?php echo htmlspecialchars($editing_product['price'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="product-image">URL da Imagem:</label>
                    <input type="url" id="product-image" name="productImage" placeholder="Ex: https://example.com/imagem.jpg" value="<?php echo htmlspecialchars($editing_product['image_url'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="product-category">Categoria:</label>
                    <select id="product-category" name="category_id" required>
                        <option value="">Selecione uma Categoria</option>
                        <?php foreach ($categories_for_form as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['id']); ?>" 
                                <?php echo ($editing_product && $editing_product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="stock-quantity">Quantidade em Estoque:</label>
                    <input type="number" id="stock-quantity" name="stockQuantity" min="0" value="<?php echo htmlspecialchars($editing_product['stock_quantity'] ?? 0); ?>" required>
                </div>
                <button type="submit" name="submit_product" class="submit-product-btn">Salvar Produto</button>
            </form>
        </section>

        <section class="product-list-admin">
            <h3>Produtos Atuais</h3>
            <?php if (empty($products)): ?>
                <p style="text-align: center; font-size: 1.2em; color: var(--color-text-dark);">Nenhum produto cadastrado.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Preço</th>
                            <th>Categoria</th>
                            <th>Estoque</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td data-label="ID:"><?php echo htmlspecialchars($product['id']); ?></td>
                                <td data-label="Nome:"><?php echo htmlspecialchars($product['name']); ?></td>
                                <td data-label="Preço:">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></td>
                                <td data-label="Categoria:"><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                <td data-label="Estoque:"><?php echo htmlspecialchars($product['stock_quantity']); ?></td>
                                <td class="actions-cell" data-label="Ações:">
                                    <a href="admin.php?action=edit&id=<?php echo htmlspecialchars($product['id']); ?>" class="edit-btn"><i class="fas fa-edit"></i> Editar</a>
                                    <button type="button" class="delete-btn" data-id="<?php echo htmlspecialchars($product['id']); ?>"><i class="fas fa-trash-alt"></i> Deletar</button>
                                    <div class="confirm-delete">
                                        <p>Tem certeza?</p>
                                        <a href="admin.php?action=delete&id=<?php echo htmlspecialchars($product['id']); ?>" class="confirm-yes"><i class="fas fa-check"></i> Sim</a>
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
        <p>&copy; 2025 Bel Vestit. Painel Administrativo.</p>
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