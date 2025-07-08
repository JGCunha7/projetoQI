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

    // Dados específicos do pagamento (simulados)
    $card_number = $_POST['card_number'] ?? '';
    $card_name = $_POST['card_name'] ?? '';
    $card_expiry = $_POST['card_expiry'] ?? '';
    $card_cvv = $_POST['card_cvv'] ?? '';
    $pix_key_simulated = "00.000.000/0001-00"; // Simulado
    
    $payment_details_display = '';

    switch ($pagamento) {
        case 'cartao':
            // Em produção: Validaria e processaria com API de pagamento
            $last_four_digits = substr($card_number, -4);
            $payment_details_display = "Cartão de Crédito/Débito (final: {$last_four_digits})";
            if (empty($card_number) || empty($card_name) || empty($card_expiry) || empty($card_cvv)) {
                $message = "Por favor, preencha todos os dados do cartão.";
                $message_type = 'error';
            }
            break;
        case 'pix':
            // Em produção: Geraria QR Code real e chave PIX real ligada ao pedido
            $payment_details_display = "PIX (Chave: {$pix_key_simulated})";
            break;
        default: // Inclui boleto caso algum valor inválido seja enviado (mesmo que a opção tenha sido removida)
            $message = "Método de pagamento inválido.";
            $message_type = 'error';
            break;
    }

    // Se já houver uma mensagem de erro, para a execução antes de continuar com estoque
    if (!empty($message)) {
        // Renderiza a página com a mensagem de erro
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
                $message = "Não foi possível finalizar a compra devido a problemas de estoque:<br>" . implode("<br>", $stock_errors);
                $message_type = 'error';
            } else {
                // 2. Reduzir o estoque (apenas se tudo estiver ok)
                $conn->begin_transaction(); // Inicia uma transação

                try {
                    foreach ($cart_items as $cart_item_id => $details) {
                        $product_id_in_cart = $details['id'];
                        $quantity_in_cart = $details['quantity'];

                        $stmt_update_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?"); // Previne estoque negativo
                        $stmt_update_stock->bind_param("iii", $quantity_in_cart, $product_id_in_cart, $quantity_in_cart);
                        $stmt_update_stock->execute();
                        
                        if ($stmt_update_stock->affected_rows === 0) {
                            // Se 0 linhas afetadas, significa que o produto não existe ou o estoque já era 0 (race condition)
                            throw new Exception("Falha ao atualizar estoque para o produto ID {$product_id_in_cart}. Pode não haver estoque suficiente.");
                        }
                        $stmt_update_stock->close();
                    }

                    // Calcular o total
                    $subtotal = 0;
                    foreach ($cart_items as $item) {
                        $subtotal += $item['price'] * $item['quantity'];
                    }
                    $discount = 0;
                    $shipping = $subtotal > 0 ? 25.00 : 0.00;
                    $total_payable = $subtotal - $discount + $shipping;

                    // --- SIMULAÇÃO DE GERAÇÃO DE NOTA DO PEDIDO ---
                    // Em um sistema real, você registraria o pedido e itens no BD e obteria um ID real.
                    $order_id_simulated = "ORD-" . time() . rand(100, 999); // ID de pedido simulado
                    $order_date = date("d/m/Y H:i:s");

                    $order_details_display_html = "
                        <h3>Detalhes do Pedido</h3>
                        <p><strong>Pedido ID:</strong> {$order_id_simulated}</p>
                        <p><strong>Data:</strong> {$order_date}</p>
                        <p><strong>Cliente:</strong> " . htmlspecialchars($nome) . "</p>
                        <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                        <p><strong>Telefone:</strong> " . htmlspecialchars($telefone) . "</p>
                        <p><strong>Endereço de Entrega:</strong> " . htmlspecialchars("{$rua}, {$numero} {$complemento} - {$bairro}, {$cidade}/{$estado} - CEP: {$cep}") . "</p>
                        
                        <h3>Itens Comprados:</h3>
                        <table class='order-items-table'>
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Qtd</th>
                                    <th>Preço Unit.</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>";
                    foreach ($cart_items as $item) {
                        $order_details_display_html .= "
                                <tr>
                                    <td>" . htmlspecialchars($item['name']) . "</td>
                                    <td>" . htmlspecialchars($item['quantity']) . "</td>
                                    <td>R$ " . number_format($item['price'], 2, ',', '.') . "</td>
                                    <td>R$ " . number_format($item['price'] * $item['quantity'], 2, ',', '.') . "</td>
                                </tr>";
                    }
                    $order_details_display_html .= "
                            </tbody>
                        </table>
                        <div class='order-summary-details'>
                            <p>Subtotal: <span>R$ " . number_format($subtotal, 2, ',', '.') . "</span></p>
                            <p>Frete: <span>R$ " . number_format($shipping, 2, ',', '.') . "</span></p>
                            <p>Desconto: <span>R$ " . number_format($discount, 2, ',', '.') . "</span></p>
                            <p><strong>Total Pago: <span>R$ " . number_format($total_payable, 2, ',', '.') . "</span></strong></p>
                            <p><strong>Método de Pagamento:</strong> " . htmlspecialchars($payment_details_display) . "</p>
                        </div>
                    ";

                    // Limpa o carrinho da sessão após a "compra" bem-sucedida
                    unset($_SESSION['cart']);

                    $conn->commit(); // Confirma todas as alterações no banco de dados
                    $message = "Pedido realizado com sucesso! Sua nota de pedido está pronta abaixo.";
                    $message_type = 'success';
                    $show_receipt = true; // Flag para exibir o recibo
                } catch (Exception $e) {
                    $conn->rollback(); // Reverte todas as alterações se algo deu errado
                    $message = "Erro ao processar o pedido: " . $e->getMessage() . " O estoque não foi atualizado ou ocorreu um problema. Por favor, tente novamente.";
                    $message_type = 'error';
                    $show_receipt = false;
                }
            }
        }
    }
} else {
    // Se a página for acessada diretamente sem uma submissão POST válida
    $message = "Acesso inválido à página de processamento de checkout.";
    $message_type = 'error';
    $show_receipt = false;
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
    <header class="main-header no-print"> <div class="logo">
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
        <?php endif; ?>

        <?php if ($show_receipt && isset($order_details_display_html)): ?>
            <section class="order-receipt printable-area"> <h2>Nota do Pedido</h2>
                <?php echo $order_details_display_html; ?>
                <div class="print-actions no-print"> <button onclick="window.print()" class="print-button place-order-btn"><i class="fas fa-print"></i> Imprimir Pedido</button>
                    <a href="index.php" class="place-order-btn" style="margin-top: 15px; background-color: var(--color-plum-purple);"><i class="fas fa-store"></i> Continuar Comprando</a>
                </div>
            </section>
        <?php endif; ?>
        
        <?php if (!$show_receipt): ?>
            <p style="text-align: center; margin-top: 30px; color: var(--color-text-dark);">Obrigado por sua visita!</p>
        <?php endif; ?>
    </main>

    <footer class="main-footer no-print"> <p>&copy; 2025 Bel Vestit. Todos os direitos reservados.</p>
    </footer>
</body>
</html>
