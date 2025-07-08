<?php
// Arquivo: product_details.php
require_once 'db_connection.php';

$product = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = intval($_GET['id']);
    // Altera a query para fazer JOIN com categories para obter o nome da categoria
    $stmt = $conn->prepare("SELECT P.id, P.name, P.description, P.price, P.image_url, P.stock_quantity, C.name AS category_name FROM products AS P LEFT JOIN categories AS C ON P.category_id = C.id WHERE P.id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $product = $result->fetch_assoc();
    } else {
        // Produto não encontrado, redirecionar para a página inicial
        redirect('index.php');
    }
    $stmt->close();
} else {
    // ID inválido, redirecionar para a página inicial
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bel Vestit - <?php echo htmlspecialchars($product['name']); ?></title>
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
        <p>Detalhes do Produto</p>
        <nav class="top-nav">
            <a href="carrinho.php" class="nav-icon-link">
                <i class="fas fa-shopping-cart"></i> Carrinho
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="perfil.php" class="nav-icon-link">
                    <i class="fas fa-user"></i> Olá, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </a>
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'employee'): ?>
                    <a href="admin.php" class="nav-icon-link">
                        <i class="fas fa-user-shield"></i> Admin
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="cliente-login.php" class="nav-icon-link">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="product-details-container main-container">
        <div class="product-details">
            <div class="product-image">
                <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'https://via.placeholder.com/400x500?text=Sem+Imagem'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <div class="product-info">
                <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                <p class="price">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></p>
                <p class="category">Categoria: <?php echo htmlspecialchars($product['category_name'] ?? 'Sem Categoria'); ?></p>
                <p class="description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                <p class="stock-info">Estoque: <?php echo htmlspecialchars($product['stock_quantity']); ?></p>
                
                <form action="carrinho.php" method="POST" class="add-to-cart-form">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                    <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                    <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($product['image_url']); ?>">
                    <button type="submit" class="details-button"><i class="fas fa-cart-plus"></i> Adicionar ao Carrinho</button>
                </form>
                <p class="back-to-list"><a href="index.php"><i class="fas fa-arrow-left"></i> Voltar para a Loja</a></p>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <p>&copy; 2025 Bel Vestit. Todos os direitos reservados.</p>
    </footer>
</body>
</html>