<?php
require 'db.php';
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>確認訂單</title>
</head>
<body class="bg-gray-50 max-w-md mx-auto min-h-screen relative pb-24 shadow-xl">
    <header class="bg-blue-600 text-white p-4 sticky top-0 flex items-center">
        <a href="index.php" class="mr-4 text-xl">←</a>
        <h1 class="text-lg font-bold">確認訂單</h1>
    </header>

    <div class="p-4" id="cart-container">
        <!-- 購物車內容由 JS 生成 -->
    </div>

    <div class="fixed bottom-0 w-full max-w-md bg-white border-t p-4 space-y-2">
        <div class="flex justify-between text-lg font-bold">
            <span>總計</span>
            <span id="final-total" class="text-red-600">$0</span>
        </div>
        <?php if ($isLoggedIn): ?>
            <div class="text-sm text-green-600 text-right" id="points-preview">預計可得 0 點</div>
        <?php endif; ?>
        <button onclick="submitOrder()" class="w-full bg-green-500 text-white py-3 rounded font-bold text-lg">確認送出</button>
    </div>

    <script>
        let cart = JSON.parse(localStorage.getItem('pos_cart')) || {};

        function renderCart() {
            const container = document.getElementById('cart-container');
            if (Object.keys(cart).length === 0) {
                container.innerHTML = '<p class="text-center text-gray-500 mt-10">購物車是空的</p>';
                document.getElementById('final-total').innerText = '$0';
                if(document.getElementById('points-preview')) document.getElementById('points-preview').innerText = '預計可得 0 點';
                return;
            }

            let html = '';
            let total = 0;
            for (let id in cart) {
                let item = cart[id];
                total += item.price * item.qty;
                html += `
                    <div class="bg-white p-4 rounded shadow mb-3 flex justify-between items-center border">
                        <div>
                            <h3 class="font-bold">${item.name}</h3>
                            <p class="text-gray-500">$${item.price}</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button onclick="updateQty(${id}, -1)" class="bg-gray-200 px-3 py-1 rounded font-bold">-</button>
                            <span class="font-bold w-6 text-center">${item.qty}</span>
                            <button onclick="updateQty(${id}, 1)" class="bg-gray-200 px-3 py-1 rounded font-bold">+</button>
                        </div>
                    </div>
                `;
            }
            container.innerHTML = html;
            document.getElementById('final-total').innerText = '$' + total;
            
            // 點數試算
            if(document.getElementById('points-preview')){
                let pts = Math.floor(total / 300);
                document.getElementById('points-preview').innerText = `預計可得 ${pts} 點`;
            }
        }

        function updateQty(id, change) {
            cart[id].qty += change;
            if (cart[id].qty <= 0) delete cart[id];
            localStorage.setItem('pos_cart', JSON.stringify(cart));
            renderCart();
        }

        async function submitOrder() {
            if (Object.keys(cart).length === 0) return alert('購物車是空的');
            
            let res = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'checkout', cart })
            });
            let data = await res.json();
            
            if (data.success) {
                alert(`訂單送出成功！${data.points ? '獲得點數: ' + data.points : ''}`);
                localStorage.removeItem('pos_cart'); // 清空購物車
                window.location.href = 'index.php'; // 返回首頁
            } else {
                alert('結帳失敗: ' + (data.msg || '發生錯誤'));
            }
        }

        // 初始化
        renderCart();
    </script>
</body>
</html>