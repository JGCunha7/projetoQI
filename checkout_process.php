<?php
// Arquivo: checkout_process.php
require_once 'db_connection.php';

// Verificar se o formulário foi submetido via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = '';
    $message_type = '';

    // Coleta dos dados do formulário
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $cep = $_POST['cep'] ?? '';
    $rua = $_POST['rua'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $complemento = $_POST['complemento'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $pagamento = $_POST['pagamento'] ?? '';

    // Validação básica (expanda conforme necessário para produção)
    if (empty($nome) || empty($email) || empty($rua) || empty($cidade) || empty($estado) || empty($pagamento)) {
        $message = "Por favor, preencha todos os campos obrigatórios do formulário de checkout.";
        $message_type = 'error';
    } else {
        // Obter itens do carrinho da sessão
        $cart_items = $_SESSION['cart'] ?? [];

        if (empty($cart_items)) {
            $message = "Seu carrinho está vazio. Não é possível finalizar a compra.";
            $message_type = 'error';
        } else {
            // --- VERIFICAÇÃO E REDUÇÃO DE ESTOQUE ---
            $can_process_order = true;
            $stock_errors = [];
            
            // 1. Verificar estoque para cada item no carrinho
            foreach ($cart_items as $cart_item_id => $details) {
                $product_id_in_cart = $details['id'];
                $quantity_in_cart = $details['quantity'];

                $stmt_stock = $conn->prepare("SELECT stock_quantity, name FROM products WHERE id = ?");
                $stmt_stock->bind_param("i", $product_id_in_cart);
                $stmt_stock->execute();
                $result_stock = $stmt_stock->get_result();
                
                if ($result_stock->num_rows > 0) {
                    $product_db = $result_stock->fetch_assoc();
                    $available_stock = $product_db['stock_quantity'];
                    $product_name_db = $product_db['name'];

                    if ($available_stock < $quantity_in_cart) {
                        $stock_errors[] = "Estoque insuficiente para '{$product_name_db}'. Disponível: {$available_stock}, Solicitado: {$quantity_in_cart}.";
                        $can_process_order = false;
                    }
                } else {
                    $stock_errors[] = "Produto com ID {$product_id_in_cart} não encontrado no estoque.";
                    $can_process_order = false;
                }
                $stmt_stock->close();
            }

            if (!$can_process_order) {
                // Se houver erros de estoque, não finaliza a compra
                $message = "Não foi possível finalizar a compra devido a problemas de estoque:<br>" . implode("<br>", $stock_errors);
                $message_type = 'error';
            } else {
                // 2. Reduzir o estoque (apenas se tudo estiver ok)
                $conn->begin_transaction(); // Inicia uma transação para garantir que todas as atualizações ocorram ou nenhuma

                try {
                    foreach ($cart_items as $cart_item_id => $details) {
                        $product_id_in_cart = $details['id'];
                        $quantity_in_cart = $details['quantity'];

                        $stmt_update_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                        $stmt_update_stock->bind_param("ii", $quantity_in_cart, $product_id_in_cart);
                        $stmt_update_stock->execute();
                        
                        if ($stmt_update_stock->affected_rows === 0) {
                            // Se 0 linhas afetadas, significa que o produto não existe ou o estoque já era 0 (race condition)
                            throw new Exception("Falha ao atualizar estoque para o produto ID {$product_id_in_cart}.");
                        }
                        $stmt_update_stock->close();
                    }

                    // Calcular o total (recalculado aqui para garantir que esteja com dados atuais)
                    $subtotal = 0;
                    foreach ($cart_items as $item) {
                        $subtotal += $item['price'] * $item['quantity'];
                    }
                    $discount = 0;
                    $shipping = $subtotal > 0 ? 25.00 : 0.00;
                    $total_payable = $subtotal - $discount + $shipping;

                    // --- SIMULAÇÃO DE PROCESSAMENTO DE PEDIDO (CONTINUA) ---
                    // Em um sistema real, aqui você:
                    // 1. Inseriria o pedido em uma tabela de 'pedidos' no banco de dados.
                    // 2. Inseriria cada item do carrinho em uma tabela 'itens_do_pedido', linkando ao pedido.
                    // 3. Opcional: Integraria com um gateway de pagamento (PagSeguro, Stripe, etc.).
                    // 4. Opcional: Enviaria um e-mail de confirmação ao cliente.

                    $order_details_display = "Detalhes do Pedido:\n";
                    foreach ($cart_items as $item) {
                        $order_details_display .= "- {$item['name']} (x{$item['quantity']}) - R$ " . number_format($item['price'] * $item['quantity'], 2, ',', '.') . "\n";
                    }
                    $order_details_display .= "\nSubtotal: R$ " . number_format($subtotal, 2, ',', '.') . "\n";
                    $order_details_display .= "Frete: R$ " . number_format($shipping, 2, ',', '.') . "\n";
                    $order_details_display .= "Total: R$ " . number_format($total_payable, 2, ',', '.') . "\n";
                    $order_details_display .= "\nDados do Cliente:\n";
                    $order_details_display .= "Nome: {$nome}\n";
                    $order_details_display .= "Email: {$email}\n";
                    $order_details_display .= "Telefone: {$telefone}\n";
                    $order_details_display .= "Endereço: {$rua}, {$numero} - {$bairro}, {$cidade}/{$estado} - CEP: {$cep}\n";
                    $order_details_display .= "Forma de Pagamento: {$pagamento}\n";

                    // Limpa o carrinho da sessão após a "compra" bem-sucedida
                    unset($_SESSION['cart']);

                    $conn->commit(); // Confirma todas as alterações no banco de dados
                    $message = "Pedido realizado com sucesso! Em um sistema real, um e-mail de confirmação seria enviado para {$email}.";
                    $message_type = 'success';

                } catch (Exception $e) {
                    $conn->rollback(); // Reverte todas as alterações se algo deu errado
                    $message = "Erro ao processar o pedido: " . $e->getMessage() . " O estoque não foi atualizado.";
                    $message_type = 'error';
                }
            }
        }
    }
} else {
    // Se a página for acessada diretamente sem uma submissão POST válida
    $message = "Acesso inválido à página de processamento de checkout.";
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bel Vestit - Confirmação de Pedido</title>
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
        <p>Confirmação de Pedido</p>
        <nav class="top-nav">
            <a href="index.php" class="nav-icon-link">
                <i class="fas fa-store"></i> Voltar para a Loja
            </a>
            <a href="carrinho.php" class="nav-icon-link">
                <i class="fas fa-shopping-cart"></i> Meu Carrinho
            </a>
        </nav>
    </header>

    <main class="main-container">
        <h2>Status do Pedido</h2>
        <?php if (!empty($message)): ?>
            <p class="message <?php echo $message_type; ?>"><?php echo nl2br(htmlspecialchars($message)); ?></p>
            <?php if ($message_type == 'success' && isset($order_details_display)): ?>
                <h3>Detalhes da Simulação do Pedido:</h3>
                <pre style="background-color: var(--color-border-light); padding: 15px; border-radius: 8px; overflow-x: auto; color: var(--color-text-dark); margin-top: 20px;"><code><?php echo htmlspecialchars($order_details_display); ?></code></pre>
            <?php endif; ?>
        <?php endif; ?>
        <p style="text-align: center; margin-top: 30px; color: var(--color-text-dark);">Obrigado por sua compra!</p>
    </main>

    <footer class="main-footer">
        <p>&copy; 2025 Bel Vestit. Todos os direitos reservados.</p>
    </footer>
</body>
</html>