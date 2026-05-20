<?php
require 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['action']) && $data['action'] === 'checkout') {
    $cart = $data['cart'];
    $userId = $_SESSION['user_id'] ?? null;
    $totalAmount = 0;

    try {
        $pdo->beginTransaction();
        
        // 計算總金額並扣除庫存
        foreach ($cart as $id => $item) {
            $totalAmount += $item['price'] * $item['qty'];
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
            $stmt->execute([$item['qty'], $id, $item['qty']]);
            if ($stmt->rowCount() == 0) throw new Exception("庫存不足");
        }

        // 計算點數：每滿300元1點
        $earnedPoints = floor($totalAmount / 300);

        // 建立訂單
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, earned_points) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $totalAmount, $earnedPoints]);
        $orderId = $pdo->lastInsertId();

        // 寫入訂單明細
        $stmtItems = $pdo->prepare("INSERT INTO order_items (order_id, product_id, qty) VALUES (?, ?, ?)");
        foreach ($cart as $id => $item) {
            $stmtItems->execute([$orderId, $id, $item['qty']]);
        }

        // 增加會員點數
        if ($userId && $earnedPoints > 0) {
            $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?")->execute([$earnedPoints, $userId]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'points' => ($userId ? $earnedPoints : 0)]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
}
?>