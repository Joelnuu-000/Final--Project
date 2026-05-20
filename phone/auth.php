<?php
require 'db.php';
// 引入 Composer 自動載入 (PHPMailer)
require '../vendor/autoload.php'; // 注意：vendor 通常在專案根目錄，請依實際相對路徑調整
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action === 'register') {
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $name = trim($_POST['name']);
        $title = $_POST['title'];
        $password = $_POST['password'];
        
        // 檢查手機或信箱是否已存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? OR email = ?");
        $stmt->execute([$phone, $email]);
        if ($stmt->fetch()) {
             echo "<script>alert('此手機號碼或 Email 已被註冊！');</script>";
        } else {
             $hash = password_hash($password, PASSWORD_DEFAULT);
             $token = bin2hex(random_bytes(16)); // 產生 32 字元驗證碼
             
             $stmt = $pdo->prepare("INSERT INTO users (phone, email, name, title, password, verify_token) VALUES (?, ?, ?, ?, ?, ?)");
             if($stmt->execute([$phone, $email, $name, $title, $hash, $token])){
                 // --- PHPMailer 發信邏輯 ---
                 $mail = new PHPMailer(true);
                 try {
                     $mail->isSMTP();
                     $mail->Host       = 'smtp.gmail.com';
                     $mail->SMTPAuth   = true;
                     $mail->Username   = 'tsungpin950118@gmail.com'; // 你的 Gmail
                     $mail->Password   = 'eyhmeeyzflqsomni'; // 你的應用程式密碼
                     $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                     $mail->Port       = 587;
                     $mail->CharSet    = 'UTF-8';

                     $mail->setFrom('tsungpin950118@gmail.com', 'POS 系統客服');
                     $mail->addAddress($email, $name . ' ' . $title);
                     $mail->isHTML(true);
                     $mail->Subject = '【POS系統】請開通您的會員帳號';
                     
                     // 驗證網址，請確認路徑是否正確 (目前設定指向 phone 目錄下的 verify.php)
                     $verifyLink = "http://localhost/Final%20Project/phone/verify.php?token=" . $token; 
                     $mail->Body = "親愛的 {$name} {$title} 您好：<br><br>請點擊下方連結開通您的帳號：<br><a href='{$verifyLink}'>開通帳號</a>";

                     $mail->send();
                     echo "<script>alert('註冊成功！請至 Email 收信並點擊開通連結。');</script>";
                 } catch (Exception $e) {
                     echo "<script>alert('信件發送失敗: {$mail->ErrorInfo}');</script>";
                 }
             }
        }
    } elseif ($action === 'login') {
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_verified'] == 0) {
                echo "<script>alert('登入失敗！您的帳號尚未開通，請先至 Email 收信開通。');</script>";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['phone'] = $user['phone'];
                $_SESSION['name'] = $user['name'];
                header("Location: index.php");
                exit;
            }
        } else {
            echo "<script>alert('帳號或密碼錯誤。');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>會員登入/註冊</title>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-6 rounded-xl shadow-lg w-full max-w-sm relative">
        
        <div class="mb-5">
            <a href="index.php" class="inline-flex items-center gap-1 text-gray-500 hover:text-blue-600 transition-colors font-bold text-sm bg-gray-100 hover:bg-blue-50 px-3 py-1.5 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                返回點餐
            </a>
        </div>

        <div class="flex mb-6 border-b">
            <button id="tab-login" class="w-1/2 pb-2 border-b-2 border-blue-500 font-bold text-blue-600" onclick="switchTab('login')">登入</button>
            <button id="tab-register" class="w-1/2 pb-2 text-gray-500 font-bold" onclick="switchTab('register')">註冊</button>
        </div>

        <form id="form-login" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="login">
            <div><label class="block text-sm font-bold text-gray-700 mb-1">手機號碼</label><input type="tel" name="phone" required class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-200 focus:outline-none"></div>
            <div><label class="block text-sm font-bold text-gray-700 mb-1">密碼</label><input type="password" name="password" required class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-200 focus:outline-none"></div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white p-2.5 rounded-lg font-bold transition-colors mt-2 shadow">登入</button>
        </form>

        <form id="form-register" method="POST" class="space-y-3 hidden">
            <input type="hidden" name="action" value="register">
            <div><label class="block text-sm font-bold text-gray-700 mb-1">真實姓名</label><input type="text" name="name" required class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-green-200 focus:outline-none"></div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">稱謂</label>
                <select name="title" class="w-full border border-gray-300 p-2 rounded-lg bg-white focus:ring-2 focus:ring-green-200 focus:outline-none">
                    <option value="先生">先生</option><option value="小姐">小姐</option>
                </select>
            </div>
            <div><label class="block text-sm font-bold text-gray-700 mb-1">手機號碼 (登入帳號)</label><input type="tel" name="phone" required class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-green-200 focus:outline-none"></div>
            <div><label class="block text-sm font-bold text-gray-700 mb-1">電子信箱 (用於開通)</label><input type="email" name="email" required class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-green-200 focus:outline-none"></div>
            <div><label class="block text-sm font-bold text-gray-700 mb-1">密碼</label><input type="password" name="password" required class="w-full border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-green-200 focus:outline-none"></div>
            <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white p-2.5 rounded-lg font-bold transition-colors mt-2 shadow">註冊並發送驗證信</button>
        </form>
    </div>
    
    <script>
        function switchTab(t) {
            document.getElementById('form-login').classList.toggle('hidden', t !== 'login');
            document.getElementById('form-register').classList.toggle('hidden', t === 'login');
            document.getElementById('tab-login').className = t === 'login' ? 'w-1/2 pb-2 border-b-2 border-blue-500 font-bold text-blue-600' : 'w-1/2 pb-2 text-gray-400 hover:text-gray-600 font-bold transition-colors';
            document.getElementById('tab-register').className = t === 'register' ? 'w-1/2 pb-2 border-b-2 border-blue-500 font-bold text-blue-600' : 'w-1/2 pb-2 text-gray-400 hover:text-gray-600 font-bold transition-colors';
        }
    </script>
</body>
</html>