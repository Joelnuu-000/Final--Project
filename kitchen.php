<?php
require 'phone/db.php';
if (isset($_POST['complete_id'])) {
    $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?")->execute([$_POST['complete_id']]);
}

$orders = $pdo->query("SELECT id, created_at FROM orders WHERE status = 'pending' ORDER BY created_at ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8"><title>廚房 KDS 系統</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta http-equiv="refresh" content="10"> <!-- 每10秒自動刷新 -->
</head>
<body class="bg-gray-800 text-white p-6">
    <h1 class="text-3xl font-bold mb-6">廚房訂單看板 (待處理)</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php foreach ($orders as $order): ?>
        <div class="bg-gray-700 p-4 rounded shadow-lg border-l-4 border-yellow-500">
            <h2 class="text-xl font-bold border-b border-gray-600 pb-2 mb-2">訂單 #<?= $order['id'] ?></h2>
            <ul class="mb-4 text-lg">
                <?php
                $items = $pdo->prepare("SELECT p.name, oi.qty FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                $items->execute([$order['id']]);
                foreach ($items->fetchAll() as $item) {
                    echo "<li>{$item['name']} <span class='text-yellow-400'>x {$item['qty']}</span></li>";
                }
                ?>
            </ul>
            <form method="POST">
                <input type="hidden" name="complete_id" value="<?= $order['id'] ?>">
                <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white py-2 rounded font-bold">出餐完成</button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>