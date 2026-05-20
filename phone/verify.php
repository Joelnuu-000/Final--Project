<?php
require 'db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // 尋找符合該 token 且尚未驗證的帳號
    $stmt = $pdo->prepare("SELECT id FROM users WHERE verify_token = ? AND is_verified = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // 更新為已驗證，並將 token 清空以策安全
        $update = $pdo->prepare("UPDATE users SET is_verified = 1, verify_token = NULL WHERE id = ?");
        $update->execute([$user['id']]);
        $msg = "帳號開通成功！您現在可以進行登入。";
    } else {
        $msg = "無效的驗證碼或帳號已開通過。";
    }
} else {
    $msg = "缺少驗證參數。";
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>帳號開通</title>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded shadow text-center max-w-sm w-full">
        <h2 class="text-2xl font-bold mb-4 text-blue-600">開通結果</h2>
        <p class="mb-6 font-bold text-gray-700"><?= htmlspecialchars($msg) ?></p>
        <a href="auth.php" class="bg-blue-500 text-white px-6 py-2 rounded font-bold">前往登入</a>
    </div>
</body>
</html>