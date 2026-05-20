<?php
require 'db.php';
// 取得已上架且有庫存的商品
$products = $pdo->query("SELECT * FROM products WHERE stock > 0 AND status = 'active'")->fetchAll();
$isLoggedIn = isset($_SESSION['user_id']);
// 若有儲存姓名則優先顯示，否則顯示手機號碼
$displayName = $isLoggedIn ? ($_SESSION['name'] ?? $_SESSION['phone']) : '';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>顧客點餐</title>
</head>
<body class="bg-gray-50 max-w-md mx-auto min-h-screen relative pb-20 shadow-2xl bg-white">
    
    <header class="bg-blue-600 text-white p-4 flex justify-between items-center sticky top-0 z-50 shadow-md">
        <h1 class="text-lg font-bold">線上點餐</h1>
        <?php if ($isLoggedIn): ?>
            <div class="flex items-center gap-3">
                <span class="text-sm font-bold">您好, <?= htmlspecialchars($displayName) ?></span>
                <a href="logout.php" class="text-xs bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded transition-colors shadow font-bold">登出</a>
            </div>
        <?php else: ?>
            <a href="auth.php" class="text-sm bg-white text-blue-600 px-3 py-1.5 rounded font-bold shadow hover:bg-gray-100 transition-colors">登入/註冊</a>
        <?php endif; ?>
    </header>

    <div class="p-4 grid grid-cols-2 gap-4">
        <?php if (empty($products)): ?>
            <p class="col-span-2 text-center text-gray-500 mt-10 font-bold">目前沒有可供點餐的商品。</p>
        <?php else: ?>
            <?php foreach ($products as $p): ?>
            <div class="bg-white rounded-xl shadow border border-gray-100 flex flex-col overflow-hidden">
                
                <?php if (!empty($p['image_path'])): ?>
                    <img src="../<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="w-full h-32 object-cover">
                <?php else: ?>
                    <div class="w-full h-32 bg-gray-100 flex items-center justify-center text-gray-400 text-sm font-bold">暫無圖片</div>
                <?php endif; ?>
                
                <div class="p-3 flex flex-col justify-between flex-1">
                    <div>
                        <h3 class="font-bold text-gray-800 text-md mb-1 line-clamp-1"><?= htmlspecialchars($p['name']) ?></h3>
                        <p class="text-red-500 font-bold mb-3 text-lg">$<?= $p['price'] ?></p>
                    </div>
                    <button onclick="addToCart(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>', <?= $p['price'] ?>)" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm w-full font-bold transition-colors shadow-sm">
                        加入購物車
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="fixed bottom-0 w-full max-w-md bg-white border-t p-4 flex justify-between items-center shadow-[0_-4px_10px_-2px_rgba(0,0,0,0.1)] z-50">
        <div class="text-gray-700 text-sm font-bold">總計: <span id="total" class="text-red-600 text-xl ml-1">$0</span></div>
        <button onclick="goToCheckout()" class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2.5 rounded-lg font-bold transition-colors shadow text-md">
            前往結帳
        </button>
    </div>

    <script>
        // 讀取 localStorage
        let cart = JSON.parse(localStorage.getItem('pos_cart')) || {};
        updateTotal();

        function addToCart(id, name, price) {
            if (!cart[id]) {
                cart[id] = { name: name, price: price, qty: 0 };
            }
            cart[id].qty++;
            saveCart();
            updateTotal();
            
            // 按鈕點擊視覺回饋
            const btn = event.target;
            const originalText = btn.innerText;
            btn.innerText = '已加入 ✔';
            btn.classList.replace('bg-blue-500', 'bg-green-500');
            btn.classList.replace('hover:bg-blue-600', 'hover:bg-green-600');
            
            setTimeout(() => {
                btn.innerText = originalText;
                btn.classList.replace('bg-green-500', 'bg-blue-500');
                btn.classList.replace('hover:bg-green-600', 'hover:bg-blue-600');
            }, 600);
        }

        function saveCart() {
            localStorage.setItem('pos_cart', JSON.stringify(cart));
        }

        function updateTotal() {
            let total = Object.values(cart).reduce((sum, item) => sum + item.price * item.qty, 0);
            document.getElementById('total').innerText = '$' + total;
        }

        function goToCheckout() {
            if (Object.keys(cart).length === 0) {
                alert('購物車是空的，請先選擇餐點！');
                return;
            }
            window.location.href = 'checkout.php';
        }
    </script>
</body>
</html>