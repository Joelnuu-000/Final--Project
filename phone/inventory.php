<?php
require 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?")->execute([$_POST['stock'], $_POST['id']]);
}
$products = $pdo->query("SELECT * FROM products")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8"><title>倉儲管理</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <h1 class="text-2xl font-bold mb-6">📦 倉儲庫存管理</h1>
    <div class="bg-white rounded shadow p-4 max-w-3xl">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b bg-gray-50"><th class="p-2">商品ID</th><th class="p-2">名稱</th><th class="p-2">當前庫存</th><th class="p-2">操作</th></tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr class="border-b">
                    <td class="p-2"><?= $p['id'] ?></td>
                    <td class="p-2 font-bold"><?= htmlspecialchars($p['name']) ?></td>
                    <td class="p-2 <?= $p['stock'] < 20 ? 'text-red-500 font-bold' : '' ?>"><?= $p['stock'] ?></td>
                    <td class="p-2">
                        <form method="POST" class="flex gap-2">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="number" name="stock" value="<?= $p['stock'] ?>" class="border w-24 p-1 rounded" min="0">
                            <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded">更新</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>