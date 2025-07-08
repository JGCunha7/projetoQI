<?php
// Arquivo: carrinho.php
require_once 'db_connection.php';

// Inicializar o carrinho na sessão se não existir
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Esta página apenas exibe o carrinho; o processamento AJAX é em cart_ajax_processor.php
// As mensagens e updates visuais são feitos via JavaScript/AJAX.

$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$discount = 0;
$shipping = $subtotal > 0 ? 25.00 : 0.00;
$total = $subtotal - $discount + $shipping;

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bel Vestit - Meu Carrinho</title>
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
        <p>Seu Carrinho de Compras</p>
        <nav class="top-nav">
            <a href="index.php" class="nav-icon-link">
                <i class="fas fa-store"></i> Voltar para a Loja
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="perfil.php" class="nav-icon-link">
                    <i class="fas fa-user"></i> Olá, <?php echo htmlspecialchars($_SESSION['username']); ?>
                </a>
            <?php else: ?>
                <a href="cliente-login.php" class="nav-icon-link">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="cart-container">
        <h2>Itens no seu Carrinho</h2>

        <p id="cartMessage" class="message" style="display: none;"></p> <section class="cart-items" id="cartItemsContainer">
            <?php if (empty($_SESSION['cart'])): ?>
                <p style="text-align: center; font-size: 1.2em; color: var(--color-text-dark);">Seu carrinho está vazio.</p>
            <?php else: ?>
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <div class="cart-item" data-product-id="<?php echo $item['id']; ?>">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p>Preço unitário: R$ <span class="item-unit-price"><?php echo number_format($item['price'], 2, ',', '.'); ?></span></p>
                            <div class="quantity-controls">
                                <form class="update-qty-form" data-product-id="<?php echo $item['id']; ?>" style="display: inline-flex; align-items: center;">
                                    <input type="hidden" name="action" value="update_qty">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <button type="button" class="qty-btn decrease-qty">-</button>
                                    <span class="qty-display" data-current-qty="<?php echo $item['quantity']; ?>"><?php echo $item['quantity']; ?></span>
                                    <button type="button" class="qty-btn increase-qty">+</button>
                                </form>
                            </div>
                            <p class="item-total">Total: R$ <span class="item-total-price"><?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?></span></p>
                            <form class="remove-item-form" data-product-id="<?php echo $item['id']; ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="remove-item-btn"><i class="fas fa-trash-alt"></i> Remover</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="cart-summary" id="cartSummaryContainer">
            <h2>Resumo do Pedido</h2>
            <div class="summary-line">
                <span>Subtotal:</span>
                <span id="subtotalValue">R$ <?php echo number_format($subtotal, 2, ',', '.'); ?></span>
            </div>
            <div class="summary-line">
                <span>Desconto:</span>
                <span id="discountValue">R$ <?php echo number_format($discount, 2, ',', '.'); ?></span>
            </div>
            <div class="summary-line">
                <span>Frete:</span>
                <span id="shippingValue">R$ <?php echo number_format($shipping, 2, ',', '.'); ?></span>
            </div>
            <div class="summary-line total">
                <span>Total a Pagar:</span>
                <span id="totalValue">R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
            </div>
        </section>

        <?php if (!empty($_SESSION['cart'])): ?>
        <section class="checkout-form">
            <h2>Finalizar Pedido</h2>
            <form action="checkout_process.php" method="POST">
                <h3>Informações Pessoais</h3>
                <div class="form-group">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="tel" id="telefone" name="telefone" placeholder="(XX) XXXXX-XXXX" value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>" required>
                </div>

                <h3>Endereço de Entrega</h3>
                <div class="form-group">
                    <label for="cep">CEP:</label>
                    <input type="text" id="cep" name="cep" placeholder="XXXXX-XXX" value="<?php echo htmlspecialchars($_SESSION['address'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="rua">Rua:</label>
                    <input type="text" id="rua" name="rua" required>
                </div>
                <div class="form-group half-width">
                    <label for="numero">Número:</label>
                    <input type="text" id="numero" name="numero" required>
                </div>
                <div class="form-group half-width">
                    <label for="complemento">Complemento (opcional):</label>
                    <input type="text" id="complemento" name="complemento">
                </div>
                <div class="form-group">
                    <label for="bairro">Bairro:</label>
                    <input type="text" id="bairro" name="bairro" required>
                </div>
                <div class="form-group half-width">
                    <label for="cidade">Cidade:</label>
                    <input type="text" id="cidade" name="cidade" required>
                </div>
                <div class="form-group half-width">
                    <label for="estado">Estado:</label>
                    <select id="estado" name="estado" required>
                        <option value="">Selecione...</option>
                        <option value="AC" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'AC') !== false) echo 'selected'; ?>>Acre</option>
                        <option value="AL" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'AL') !== false) echo 'selected'; ?>>Alagoas</option>
                        <option value="AP" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'AP') !== false) echo 'selected'; ?>>Amapá</option>
                        <option value="AM" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'AM') !== false) echo 'selected'; ?>>Amazonas</option>
                        <option value="BA" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'BA') !== false) echo 'selected'; ?>>Bahia</option>
                        <option value="CE" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'CE') !== false) echo 'selected'; ?>>Ceará</option>
                        <option value="DF" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'DF') !== false) echo 'selected'; ?>>Distrito Federal</option>
                        <option value="ES" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'ES') !== false) echo 'selected'; ?>>Espírito Santo</option>
                        <option value="GO" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'GO') !== false) echo 'selected'; ?>>Goiás</option>
                        <option value="MA" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'MA') !== false) echo 'selected'; ?>>Maranhão</option>
                        <option value="MT" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'MT') !== false) echo 'selected'; ?>>Mato Grosso</option>
                        <option value="MS" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'MS') !== false) echo 'selected'; ?>>Mato Grosso do Sul</option>
                        <option value="MG" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'MG') !== false) echo 'selected'; ?>>Minas Gerais</option>
                        <option value="PA" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'PA') !== false) echo 'selected'; ?>>Pará</option>
                        <option value="PB" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'PB') !== false) echo 'selected'; ?>>Paraíba</option>
                        <option value="PR" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'PR') !== false) echo 'selected'; ?>>Paraná</option>
                        <option value="PE" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'PE') !== false) echo 'selected'; ?>>Pernambuco</option>
                        <option value="PI" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'PI') !== false) echo 'selected'; ?>>Piauí</option>
                        <option value="RJ" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'RJ') !== false) echo 'selected'; ?>>Rio de Janeiro</option>
                        <option value="RN" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'RN') !== false) echo 'selected'; ?>>Rio Grande do Norte</option>
                        <option value="RS" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'RS') !== false) echo 'selected'; ?>>Rio Grande do Sul</option>
                        <option value="RO" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'RO') !== false) echo 'selected'; ?>>Rondônia</option>
                        <option value="RR" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'RR') !== false) echo 'selected'; ?>>Roraima</option>
                        <option value="SC" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'SC') !== false) echo 'selected'; ?>>Santa Catarina</option>
                        <option value="SP" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'SP') !== false) echo 'selected'; ?>>São Paulo</option>
                        <option value="SE" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'SE') !== false) echo 'selected'; ?>>Sergipe</option>
                        <option value="TO" <?php if(isset($_SESSION['address']) && strpos($_SESSION['address'], 'TO') !== false) echo 'selected'; ?>>Tocantins</option>
                    </select>
                </div>

                <h3>Meio de Pagamento</h3>
                <div class="form-group">
                    <label for="pagamento">Escolha a forma de pagamento:</label>
                    <select id="pagamento" name="pagamento" required>
                        <option value="">Selecione...</option>
                        <option value="cartao">Cartão de Crédito/Débito</option>
                        <option value="boleto">Boleto Bancário</option>
                        <option value="pix">PIX</option>
                    </select>
                </div>
                <button type="submit" class="place-order-btn">Finalizar Compra</button>
            </form>
        </section>
        <?php endif; ?>
    </main>

    <footer class="main-footer">
        <p>&copy; 2025 Bel Vestit. Todos os direitos reservados.</p>
    </footer>

    <script>
        // Função para formatar moeda
        function formatCurrency(value) {
            return 'R$ ' + parseFloat(value).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+,)/g, '$1.');
        }

        // Função para exibir mensagens
        function showCartMessage(message, type) {
            const msgElement = document.getElementById('cartMessage');
            msgElement.textContent = message;
            msgElement.className = 'message ' + type;
            msgElement.style.display = 'block';
            setTimeout(() => {
                msgElement.style.display = 'none';
            }, 3000); // Esconde a mensagem após 3 segundos
        }

        // Função para atualizar o resumo do carrinho
        function updateCartSummary(data) {
            document.getElementById('subtotalValue').textContent = formatCurrency(data.subtotal);
            // Implemente discountValue e shippingValue se a lógica for mais complexa
            let shipping = data.subtotal > 0 ? 25.00 : 0.00;
            document.getElementById('shippingValue').textContent = formatCurrency(shipping);
            document.getElementById('totalValue').textContent = formatCurrency(data.subtotal - 0 + shipping); // Considerando desconto 0 por enquanto

            const cartItemsContainer = document.getElementById('cartItemsContainer');
            const checkoutFormSection = document.querySelector('.checkout-form');

            if (data.total_items === 0) {
                cartItemsContainer.innerHTML = '<p style="text-align: center; font-size: 1.2em; color: var(--color-text-dark);">Seu carrinho está vazio.</p>';
                if (checkoutFormSection) {
                    checkoutFormSection.style.display = 'none'; // Esconde o formulário de checkout
                }
            } else {
                if (checkoutFormSection) {
                    checkoutFormSection.style.display = 'block'; // Mostra o formulário de checkout
                }
            }
        }

        // Função para renderizar os itens do carrinho com base nos dados recebidos
        function renderCartItems(cartItems) {
            const cartItemsContainer = document.getElementById('cartItemsContainer');
            cartItemsContainer.innerHTML = ''; // Limpa o conteúdo atual

            if (cartItems.length === 0) {
                cartItemsContainer.innerHTML = '<p style="text-align: center; font-size: 1.2em; color: var(--color-text-dark);">Seu carrinho está vazio.</p>';
                return;
            }

            cartItems.forEach(item => {
                const cartItemDiv = document.createElement('div');
                cartItemDiv.className = 'cart-item';
                cartItemDiv.dataset.productId = item.id;
                cartItemDiv.innerHTML = `
                    <img src="${htmlspecialchars(item.image)}" alt="${htmlspecialchars(item.name)}">
                    <div class="item-details">
                        <h3>${htmlspecialchars(item.name)}</h3>
                        <p>Preço unitário: R$ <span class="item-unit-price">${formatCurrency(item.price)}</span></p>
                        <div class="quantity-controls">
                            <form class="update-qty-form" data-product-id="${item.id}" style="display: inline-flex; align-items: center;">
                                <input type="hidden" name="action" value="update_qty">
                                <input type="hidden" name="product_id" value="${item.id}">
                                <button type="button" class="qty-btn decrease-qty">-</button>
                                <span class="qty-display" data-current-qty="${item.quantity}">${item.quantity}</span>
                                <button type="button" class="qty-btn increase-qty">+</button>
                            </form>
                        </div>
                        <p class="item-total">Total: R$ <span class="item-total-price">${formatCurrency(item.price * item.quantity)}</span></p>
                        <form class="remove-item-form" data-product-id="${item.id}">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="${item.id}">
                            <button type="submit" class="remove-item-btn"><i class="fas fa-trash-alt"></i> Remover</button>
                        </form>
                    </div>
                `;
                cartItemsContainer.appendChild(cartItemDiv);
            });
            attachCartEventListeners(); // Re-anexa event listeners após renderização
        }

        // Função para anexar event listeners aos botões e formulários do carrinho
        function attachCartEventListeners() {
            // Event listeners para aumentar/diminuir quantidade
            document.querySelectorAll('.qty-btn.increase-qty').forEach(button => {
                button.onclick = function() {
                    const form = this.closest('.update-qty-form');
                    const productId = form.dataset.productId;
                    const qtyDisplay = form.querySelector('.qty-display');
                    let currentQty = parseInt(qtyDisplay.dataset.currentQty);
                    performCartAction('update_qty', productId, currentQty + 1);
                };
            });

            document.querySelectorAll('.qty-btn.decrease-qty').forEach(button => {
                button.onclick = function() {
                    const form = this.closest('.update-qty-form');
                    const productId = form.dataset.productId;
                    const qtyDisplay = form.querySelector('.qty-display');
                    let currentQty = parseInt(qtyDisplay.dataset.currentQty);
                    if (currentQty > 1) {
                        performCartAction('update_qty', productId, currentQty - 1);
                    } else {
                        // Se for 1 e diminuir, é como remover
                        if (confirm('Tem certeza que deseja remover este item do carrinho?')) {
                            performCartAction('remove', productId);
                        }
                    }
                };
            });

            // Event listeners para remover item
            document.querySelectorAll('.remove-item-form').forEach(form => {
                form.onsubmit = function(e) {
                    e.preventDefault();
                    if (confirm('Tem certeza que deseja remover este item do carrinho?')) {
                        const productId = this.dataset.productId;
                        performCartAction('remove', productId);
                    }
                };
            });
        }

        // Função para realizar ações no carrinho via AJAX
        function performCartAction(action, productId, quantity = null) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('product_id', productId);
            if (quantity !== null) {
                formData.append('quantity', quantity);
            }

            fetch('cart_ajax_processor.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    showCartMessage(data.message, 'success');
                    renderCartItems(data.cart_items); // Re-renderiza todos os itens do carrinho
                    updateCartSummary(data); // Atualiza o resumo
                } else {
                    showCartMessage('Erro no carrinho: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro AJAX no carrinho:', error);
                showCartMessage('Ocorreu um erro ao processar sua requisição.', 'error');
            });
        }
        
        // Helper para htmlspecialchars no JS, simples
        function htmlspecialchars(str) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return str.replace(/[&<>"']/g, function(m) { return map[m]; });
        }


        // Anexar event listeners quando a página carregar
        document.addEventListener('DOMContentLoaded', attachCartEventListeners);
    </script>
</body>
</html>