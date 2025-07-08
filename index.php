<?php
// Arquivo: index.php
require_once 'db_connection.php'; // Inclui a conexão com o banco de dados

$search_term = $_GET['search'] ?? '';
$category_filter_id = $_GET['category'] ?? ''; // Agora é o ID da categoria
$products = [];
$sql_where_parts = [];
$sql_params = [];
$param_types = '';

// Lógica para pesquisa por termo (LIKE)
if (!empty($search_term)) {
    $sql_where_parts[] = "(P.name LIKE ? OR P.description LIKE ?)";
    $sql_params[] = "%".$search_term."%"; // Usar % para busca parcial
    $sql_params[] = "%".$search_term."%";
    $param_types .= "ss"; // Dois parâmetros string
}

// Lógica para filtro por categoria (usando category_id)
if (!empty($category_filter_id)) {
    $sql_where_parts[] = "P.category_id = ?";
    $sql_params[] = $category_filter_id;
    $param_types .= "i"; // Parâmetro inteiro para category_id
}

// Montar a cláusula WHERE final
$sql_where = '';
if (!empty($sql_where_parts)) {
    $sql_where = "WHERE " . implode(" AND ", $sql_where_parts);
}

// Query principal para buscar produtos, fazendo JOIN com a tabela categories
$sql = "SELECT P.id, P.name, P.description, P.price, P.image_url, P.stock_quantity, C.name AS category_name FROM products AS P LEFT JOIN categories AS C ON P.category_id = C.id " . $sql_where . " ORDER BY P.name ASC";
$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($sql_params)) {
        $stmt->bind_param($param_types, ...$sql_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    $stmt->close();
} else {
    // Em um ambiente de produção, logar o erro em vez de exibi-lo
    // error_log("Erro na preparação da consulta de produtos: " . $conn->error);
    // Verificar se é uma requisição AJAX para não quebrar o JSON
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        echo "<p class='message error'>Ocorreu um erro ao carregar os produtos.</p>";
    } else {
        // Se for uma requisição AJAX, apenas retorna um erro para o JS lidar
        echo "<p style='text-align: center; width: 100%; color: var(--color-error);'>Erro ao carregar produtos.</p>";
        exit(); // Sair para não renderizar o restante do HTML
    }
}

// Buscar categorias para o filtro drop-down (sempre dinâmico do banco de dados)
$categories_for_filter = [];
$category_sql = "SELECT id, name FROM categories ORDER BY name ASC";
$category_result = $conn->query($category_sql);
if ($category_result && $category_result->num_rows > 0) {
    while($cat_row = $category_result->fetch_assoc()) {
        $categories_for_filter[] = $cat_row;
    }
}

// --- Conteúdo que será retornado via AJAX ---
// ob_start() e ob_get_clean() para capturar o HTML do grid
// Este HTML será o que o JavaScript vai injetar na página.
ob_start();
?>
<div class="product-grid">
    <?php if (empty($products)): ?>
        <p style="text-align: center; width: 100%; color: var(--color-text-dark);">Nenhum produto encontrado para sua pesquisa/filtro.</p>
    <?php else: ?>
        <?php foreach ($products as $product): ?>
            <div class="product-card">
                <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'https://via.placeholder.com/250x300?text=Produto+sem+imagem'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="category-name"><?php echo htmlspecialchars($product['category_name'] ?? 'Sem Categoria'); ?></p>
                <p class="price">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></p>
                <p class="description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . (strlen($product['description']) > 100 ? '...' : ''); ?></p>
                <div class="product-actions">
                    <p class="more-details"><a href="product_details.php?id=<?php echo $product['id']; ?>">Saiba Mais <i class="fas fa-info-circle"></i></a></p>
                    <form action="carrinho.php" method="POST" class="add-to-cart-form js-add-to-cart-form">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                        <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                        <input type="hidden" name="product_image" value="<?php echo htmlspecialchars($product['image_url']); ?>">
                        <button type="submit" class="details-button"><i class="fas fa-cart-plus"></i> Adicionar ao Carrinho</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php
$product_grid_html = ob_get_clean(); // Captura o HTML do grid
// --- Fim do conteúdo AJAX ---

// Verificar se é uma requisição AJAX para decidir o que renderizar
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    echo $product_grid_html; // Se for AJAX, apenas retorna o HTML do grid
    exit(); // Sai do script para não renderizar o restante do HTML da página
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bel Vestit - Sua Loja de Roupas</title>
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
        <p>Sua Loja de Roupas Online</p>
        <nav class="top-nav">
            <a href="carrinho.php" class="nav-icon-link">
                <i class="fas fa-shopping-cart"></i> Carrinho
            </a>
            <?php if (isset($_SESSION['user_id'])): // Se o usuário estiver logado ?>
                <a href="perfil.php" class="nav-icon-link">
                    <i class="fas fa-user"></i> Olá, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </a>
                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'employee'): // Se for admin/employee, mostra link para admin ?>
                    <a href="admin.php" class="nav-icon-link">
                        <i class="fas fa-user-shield"></i> Admin
                    </a>
                <?php endif; ?>
            <?php else: // Se não estiver logado, mostra link para login de cliente ?>
                <a href="cliente-login.php" class="nav-icon-link">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            <?php endif; ?>
        </nav>
    </header>

    <nav class="main-nav">
        <div class="filters">
            <form action="index.php" method="GET" class="filter-form" id="filterForm">
                <select name="category" onchange="performSearchAndFilter()" class="filter-select">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($categories_for_filter as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php if ($category_filter_id == $cat['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                </form>
        </div>
        <form action="index.php" method="GET" class="search-bar" id="searchForm">
            <input type="text" name="search" placeholder="Pesquisar produtos..." value="<?php echo htmlspecialchars($search_term); ?>" onkeyup="performSearchAndFilter()">
            <button type="submit" class="search-button">Pesquisar</button>
            </form>
    </nav>

    <main class="product-list-container">
        <h2>Nossos Produtos</h2>
        <div id="productGridContainer"> <?php echo $product_grid_html; // Renderiza o grid inicial ?>
        </div>
    </main>

    <footer class="main-footer">
        <p>&copy; 2025 Bel Vestit. Todos os direitos reservados.</p>
    </footer>

    <script>
        let searchTimeout; // Variável para controlar o debounce da pesquisa

        function performSearchAndFilter() {
            clearTimeout(searchTimeout); // Limpa o timeout anterior

            searchTimeout = setTimeout(() => { // Define um novo timeout de 300ms
                const searchTerm = document.querySelector('#searchForm input[name="search"]').value;
                const categoryId = document.querySelector('#filterForm select[name="category"]').value;
                const productGridContainer = document.getElementById('productGridContainer');

                // Construir a URL da requisição AJAX
                const params = new URLSearchParams();
                if (searchTerm) {
                    params.append('search', searchTerm);
                }
                if (categoryId) {
                    params.append('category', categoryId);
                }

                const url = 'index.php?' + params.toString();

                // Realizar a requisição AJAX
                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest' // Indica que é uma requisição AJAX
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text(); // Espera texto HTML
                })
                .then(html => {
                    productGridContainer.innerHTML = html; // Atualiza apenas o conteúdo do grid
                    // Scroll para o elemento h2 da seção "Nossos Produtos"
                    document.querySelector('.product-list-container h2').scrollIntoView({ behavior: 'smooth', block: 'start' });
                })
                .catch(error => {
                    console.error('Erro na requisição AJAX:', error);
                    productGridContainer.innerHTML = '<p style="text-align: center; width: 100%; color: var(--color-error);">Não foi possível carregar os produtos.</p>';
                });
            }, 300); // Atraso de 300ms para evitar requisições a cada tecla
        }

        // Prevenir o envio padrão dos formulários de filtro e pesquisa
        document.addEventListener('DOMContentLoaded', () => {
            const filterForm = document.getElementById('filterForm');
            const searchForm = document.getElementById('searchForm');

            filterForm.addEventListener('submit', (e) => {
                e.preventDefault(); // Impede o envio padrão do formulário
                performSearchAndFilter();
            });

            searchForm.addEventListener('submit', (e) => {
                e.preventDefault(); // Impede o envio padrão do formulário
                performSearchAndFilter();
            });

            // Lógica AJAX para adicionar ao carrinho sem recarregar a página (re-anexar após cada atualização do grid)
            function attachAddToCartListeners() {
                const addToCartForms = document.querySelectorAll('.js-add-to-cart-form');

                addToCartForms.forEach(form => {
                    form.removeEventListener('submit', handleAddToCartSubmit); // Remove para evitar duplicar
                    form.addEventListener('submit', handleAddToCartSubmit);
                });
            }

            function handleAddToCartSubmit(e) {
                e.preventDefault(); // Impede o envio padrão do formulário

                const formData = new FormData(this); // Pega os dados do formulário
                
                fetch('cart_ajax_processor.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest' // Indica que é uma requisição AJAX
                    }
                })
                .then(response => response.json()) // Espera uma resposta JSON
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message); // Pode ser substituída por uma notificação mais amigável
                        // Opcional: Atualizar um contador de itens no carrinho na interface
                    } else {
                        alert('Erro ao adicionar ao carrinho: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erro AJAX ao adicionar ao carrinho:', error);
                    alert('Ocorreu um erro ao adicionar o produto ao carrinho.');
                });
            }

            // Anexar listeners inicialmente e após cada atualização do grid (via performSearchAndFilter)
            attachAddToCartListeners();

            // Adiciona um MutationObserver para re-anexar listeners quando o grid é atualizado
            const observer = new MutationObserver(function(mutationsList, observer) {
                for(let mutation of mutationsList) {
                    if (mutation.type === 'childList' && mutation.target.id === 'productGridContainer') {
                        attachAddToCartListeners();
                        break;
                    }
                }
            });
            observer.observe(document.getElementById('productGridContainer'), { childList: true });

        });
    </script>
</body>
</html>