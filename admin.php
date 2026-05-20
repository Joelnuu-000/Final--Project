<?php
require 'phone/db.php'; // 確保路徑指向正確的 db.php

// 處理 POST 請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_points') {
            // 更新會員點數
            $pdo->prepare("UPDATE users SET points = ? WHERE id = ?")->execute([$_POST['points'], $_POST['user_id']]);
            echo "<script>alert('會員點數已更新！');</script>";
            
        } elseif ($_POST['action'] === 'toggle_product') {
            // 切換商品上下架
            $newStatus = $_POST['current_status'] === 'active' ? 'inactive' : 'active';
            $pdo->prepare("UPDATE products SET status = ? WHERE id = ?")->execute([$newStatus, $_POST['product_id']]);
            
        } elseif ($_POST['action'] === 'add_product') {
            // 新增商品與圖片上傳處理
            $name = trim($_POST['name']);
            $price = (int)$_POST['price'];
            $stock = (int)$_POST['stock'];
            $image_path = null;

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $ext;
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $image_path = $targetFile;
                }
            }
            
            if ($name !== '' && $price >= 0 && $stock >= 0) {
                $stmt = $pdo->prepare("INSERT INTO products (name, price, stock, status, image_path) VALUES (?, ?, ?, 'active', ?)");
                $stmt->execute([$name, $price, $stock, $image_path]);
                echo "<script>alert('新商品上架成功！');</script>";
            } else {
                echo "<script>alert('請填寫正確的商品資訊！');</script>";
            }
            
        } elseif ($_POST['action'] === 'update_product_image') {
            // 更新已上架的商品圖片
            $product_id = $_POST['product_id'];

            if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $ext = pathinfo($_FILES['new_image']['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $ext;
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['new_image']['tmp_name'], $targetFile)) {
                    // 找出舊圖片並刪除 (釋放硬碟空間)
                    $stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
                    $stmt->execute([$product_id]);
                    $oldImage = $stmt->fetchColumn();
                    if ($oldImage && file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                    
                    // 更新資料庫的路徑
                    $pdo->prepare("UPDATE products SET image_path = ? WHERE id = ?")->execute([$targetFile, $product_id]);
                    echo "<script>alert('圖片更新成功！');</script>";
                }
            } else {
                echo "<script>alert('請選擇有效的圖片檔案！');</script>";
            }
        }
    }
}

// 取得今日營收與訂單數
$today = date('Y-m-d');
$todayStats = $pdo->query("SELECT COUNT(*) as count, IFNULL(SUM(total_amount), 0) as total FROM orders WHERE DATE(created_at) = '$today'")->fetch();

// 取得近七日營業額 (Chart.js 使用)
$chartDataQuery = $pdo->query("SELECT DATE(created_at) as date, SUM(total_amount) as total FROM orders GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 7")->fetchAll();
$dates = []; $totals = [];
foreach (array_reverse($chartDataQuery) as $row) {
    $dates[] = $row['date'];
    $totals[] = $row['total'];
}

// 取得熱銷商品 Top 5
$topProducts = $pdo->query("
    SELECT p.name, SUM(oi.qty) as total_sold 
    FROM order_items oi JOIN products p ON oi.product_id = p.id 
    GROUP BY p.id ORDER BY total_sold DESC LIMIT 5
")->fetchAll();

// 取得會員與商品列表
$members = $pdo->query("SELECT id, phone, name, points FROM users ORDER BY id DESC")->fetchAll();
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8"><title>管理員中心</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto space-y-6">
        <h1 class="text-3xl font-bold text-gray-800">📊 管理員主控台</h1>
        
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white p-6 rounded shadow-sm border-l-4 border-blue-500">
                <h3 class="text-gray-500 text-sm font-bold">今日訂單數</h3>
                <p class="text-3xl font-bold mt-1"><?= $todayStats['count'] ?></p>
            </div>
            <div class="bg-white p-6 rounded shadow-sm border-l-4 border-green-500">
                <h3 class="text-gray-500 text-sm font-bold">今日總營收</h3>
                <p class="text-3xl font-bold text-green-600 mt-1">$<?= number_format($todayStats['total']) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="bg-white p-5 rounded shadow-sm lg:col-span-2">
                <h2 class="text-xl font-bold mb-4 text-gray-700">📈 近七日營業額趨勢</h2>
                <canvas id="salesChart"></canvas>
            </div>

            <div class="bg-white p-5 rounded shadow-sm flex flex-col">
                <h2 class="text-xl font-bold mb-4 text-gray-700">🔥 熱銷商品 Top 5</h2>
                <ul class="divide-y divide-gray-100 flex-1">
                    <?php if (empty($topProducts)): ?>
                        <li class="py-4 text-gray-400 text-sm text-center">暫無銷售紀錄</li>
                    <?php else: ?>
                        <?php foreach ($topProducts as $index => $tp): ?>
                        <li class="py-3 flex justify-between items-center">
                            <div class="flex items-center">
                                <?php 
                                    $rankColor = 'text-gray-400';
                                    if ($index == 0) $rankColor = 'text-yellow-500';
                                    elseif ($index == 1) $rankColor = 'text-gray-300';
                                    elseif ($index == 2) $rankColor = 'text-orange-400';
                                ?>
                                <span class="<?= $rankColor ?> font-black text-lg w-6 mr-2">#<?= $index + 1 ?></span>
                                <span class="font-bold text-gray-800"><?= htmlspecialchars($tp['name']) ?></span>
                            </div>
                            <span class="bg-red-100 text-red-700 text-xs font-bold px-2.5 py-1 rounded-full">賣出 <?= $tp['total_sold'] ?> 份</span>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="bg-white p-5 rounded shadow-sm overflow-y-auto max-h-[600px]">
                <h2 class="text-xl font-bold mb-4 text-gray-700">👥 會員點數管理</h2>
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b"><tr><th class="p-2">會員資訊</th><th class="p-2">點數修改</th></tr></thead>
                    <tbody>
                        <?php foreach ($members as $m): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-2">
                                <div class="font-bold"><?= htmlspecialchars($m['name'] ?? '訪客') ?></div>
                                <div class="text-gray-500 font-mono text-xs"><?= htmlspecialchars($m['phone']) ?></div>
                            </td>
                            <td class="p-2">
                                <form method="POST" class="flex gap-2">
                                    <input type="hidden" name="action" value="update_points">
                                    <input type="hidden" name="user_id" value="<?= $m['id'] ?>">
                                    <input type="number" name="points" value="<?= $m['points'] ?>" class="border w-16 p-1 rounded text-center">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-xs font-bold">更新</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="bg-white p-5 rounded shadow-sm lg:col-span-2 overflow-y-auto max-h-[600px]">
                <h2 class="text-xl font-bold mb-4 text-gray-700">🍔 商品管理</h2>
                
                <div class="mb-4 bg-blue-50/50 p-4 rounded-lg border border-blue-100">
                    <h3 class="font-bold mb-3 text-blue-800 text-sm">➕ 新增商品</h3>
                    <form method="POST" enctype="multipart/form-data" class="flex flex-wrap gap-3 items-end">
                        <input type="hidden" name="action" value="add_product">
                        <div><label class="block text-xs mb-1 text-gray-600 font-bold">商品名稱</label><input type="text" name="name" required class="border p-2 rounded w-32 shadow-inner"></div>
                        <div><label class="block text-xs mb-1 text-gray-600 font-bold">價格</label><input type="number" name="price" required min="0" class="border p-2 rounded w-20 shadow-inner"></div>
                        <div><label class="block text-xs mb-1 text-gray-600 font-bold">初始庫存</label><input type="number" name="stock" required min="0" class="border p-2 rounded w-20 shadow-inner"></div>
                        <div><label class="block text-xs mb-1 text-gray-600 font-bold">商品圖片</label><input type="file" name="image" accept="image/*" class="border p-1 rounded w-48 text-sm bg-white"></div>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-bold shadow h-[42px]">確認上架</button>
                    </form>
                </div>

                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b"><tr><th class="p-2">圖片</th><th class="p-2">名稱</th><th class="p-2">價格</th><th class="p-2">庫存</th><th class="p-2">狀態</th><th class="p-2">操作</th></tr></thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-2 align-top w-40">
                                <?php if ($p['image_path']): ?>
                                    <img src="<?= htmlspecialchars($p['image_path']) ?>" class="w-full h-16 object-cover rounded mb-2 border border-gray-200">
                                <?php else: ?>
                                    <div class="w-full h-16 bg-gray-100 rounded border border-gray-200 flex items-center justify-center text-[10px] text-gray-400 font-bold mb-2">無圖片</div>
                                <?php endif; ?>
                                
                                <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-1">
                                    <input type="hidden" name="action" value="update_product_image">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <div class="flex items-center gap-1">
                                        <input type="file" name="new_image" accept="image/*" required class="w-24 text-[10px] file:text-[10px] file:py-0.5 file:px-1 file:border-0 file:bg-gray-200 file:rounded file:cursor-pointer text-gray-500">
                                        <button type="submit" class="bg-indigo-500 hover:bg-indigo-600 text-white px-2 py-0.5 rounded text-[10px] font-bold shadow">上傳</button>
                                    </div>
                                </form>
                            </td>
                            <td class="p-2 font-bold text-gray-800 align-top pt-4"><?= htmlspecialchars($p['name']) ?></td>
                            <td class="p-2 text-gray-600 align-top pt-4">$<?= $p['price'] ?></td>
                            <td class="p-2 text-gray-600 align-top pt-4"><?= $p['stock'] ?></td>
                            <td class="p-2 align-top pt-4">
                                <span class="<?= $p['status'] === 'active' ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100' ?> font-bold px-2 py-1 rounded text-xs">
                                    <?= $p['status'] === 'active' ? '上架中' : '已下架' ?>
                                </span>
                            </td>
                            <td class="p-2 align-top pt-3">
                                <form method="POST">
                                    <input type="hidden" name="action" value="toggle_product">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="current_status" value="<?= $p['status'] ?>">
                                    <button type="submit" class="<?= $p['status'] === 'active' ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600' ?> text-white px-3 py-1 rounded transition-colors font-bold text-xs shadow-sm">
                                        <?= $p['status'] === 'active' ? '設為下架' : '重新上架' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($dates) ?>,
                datasets: [{
                    label: '營業額 (NTD)',
                    data: <?= json_encode($totals) ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true, tension: 0.3, pointBackgroundColor: 'rgb(59, 130, 246)', pointHoverBorderColor: 'rgb(59, 130, 246)'
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { borderDash: [5, 5] } }, x: { grid: { display: false } } } }
        });
    </script>
</body>
</html>