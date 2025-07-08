<?php
// Arquivo: cart_ajax_processor.php
require_once 'db_connection.php'; // Inclui a conexão e inicia a sessão

header('Content-Type: application/json'); // Define o cabeçalho de resposta como JSON

// Inicializar o carrinho na sessão se não existir
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$response = [
    'status' => 'error',
    'message' => 'Requisição inválida.',
    'cart_items' => $_SESSION['cart'], // Retorna os itens do carrinho
    'subtotal' => 0,
    'total_items' => 0
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $product_id = $_POST['product_id'] ?? 0;

    switch ($action) {
        case 'add':
            $product_name = $_POST['product_name'] ?? 'Produto Desconhecido';
            $product_price = $_POST['product_price'] ?? 0.00;
            $product_image = $_POST['product_image'] ?? 'https://via.placeholder.com/100x120?text=Sem+Imagem';
            $quantity = (int)($_POST['quantity'] ?? 1); // Pode vir quantidade para adicionar N

            if ($product_id <= 0 || empty($product_name) || $product_price < 0) {
                $response['message'] = "Dados do produto inválidos.";
                break;
            }

            if (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] += $quantity;
                $response['message'] = "Quantidade de '{$product_name}' atualizada no carrinho!";
            } else {
                $_SESSION['cart'][$product_id] = [
                    'id' => $product_id,
                    'name' => $product_name,
                    'price' => $product_price,
                    'image' => $product_image,
                    'quantity' => $quantity
                ];
                $response['message'] = "'{$product_name}' adicionado ao carrinho!";
            }
            $response['status'] = 'success';
            break;

        case 'update_qty':
            $new_qty = (int)($_POST['quantity'] ?? 1);
            if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
                if ($new_qty > 0) {
                    $_SESSION['cart'][$product_id]['quantity'] = $new_qty;
                    $response['message'] = "Quantidade de '{$_SESSION['cart'][$product_id]['name']}' atualizada para {$new_qty}.";
                    $response['status'] = 'success';
                } else { // Se a nova quantidade for 0 ou menos, remove o item
                    $product_name = $_SESSION['cart'][$product_id]['name'];
                    unset($_SESSION['cart'][$product_id]);
                    $response['message'] = "'{$product_name}' removido do carrinho.";
                    $response['status'] = 'success';
                }
            } else {
                $response['message'] = "Erro ao atualizar quantidade do produto.";
            }
            break;

        case 'remove':
            if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
                $product_name = $_SESSION['cart'][$product_id]['name'];
                unset($_SESSION['cart'][$product_id]);
                $response['message'] = "'{$product_name}' removido do carrinho.";
                $response['status'] = 'success';
            } else {
                $response['message'] = "Erro ao remover produto do carrinho.";
            }
            break;
    }
}

// Recalcular subtotal e total de itens para enviar na resposta
$response['subtotal'] = 0;
$response['total_items'] = 0;
foreach ($_SESSION['cart'] as $item) {
    $response['subtotal'] += $item['price'] * $item['quantity'];
    $response['total_items'] += $item['quantity'];
}
$response['cart_items'] = array_values($_SESSION['cart']); // Reindexar o array para JSON (se um item é removido, as chaves podem ficar esparsas)
echo json_encode($response);
exit();
?>