# 🍔 全端輕量化 POS 點餐系統 (PHP Full-Stack POS)

這是一個基於 PHP (PDO) + Vanilla JS + Tailwind CSS 打造的全端 POS 點餐系統。專案採用「模組化但不破碎」的架構設計，專為高效率本地開發 (Vibe Coding) 所優化。系統分為 **顧客行動端**、**管理員後台** 與 **廚房 KDS (Kitchen Display System)** 三大核心模組。

## 🚀 技術棧 (Tech Stack)
* **後端語言**：PHP 8+ (採用 PDO 進行安全的資料庫操作)
* **前端框架**：Tailwind CSS (CDN) 提供響應式 (Mobile-first) 設計
* **資料庫**：MySQL / MariaDB
* **狀態管理**：純 JavaScript (`localStorage`) 實作輕量級購物車，減少 Server loading
* **第三方套件**：
  * `Chart.js`：後台數據視覺化
  * `PHPMailer`：會員註冊 Email 開通驗證

---

## ✨ 核心功能模組 (Features)

### 📱 顧客端 (行動裝置優先)
* **無縫點餐體驗**：圖文並茂的商品列表，一鍵加入購物車，按鈕具備即時視覺回饋。
* **本地購物車**：使用 `localStorage` 暫存餐點，頁面跳轉不掉單。
* **會員系統與點數機制**：
  * 支援訪客點餐或登入會員點餐。
  * 採用信箱 Token 驗證開通帳號機制 (PHPMailer)。
  * 消費滿 300 元自動累積 1 點。

### 📊 管理員後台 (Admin Dashboard)
* **營收視覺化**：整合 Chart.js 顯示近七日營業額趨勢圖。
* **數據統計**：今日訂單數、總營收，以及系統自動結算的「🔥 熱銷商品 Top 5」。
* **商品與圖床管理**：
  * 支援新增商品、設定初始庫存與價格。
  * **智能圖片管理**：上傳新圖片時自動覆蓋並刪除舊圖，節省硬碟空間。
  * 一鍵切換商品「上架/下架」狀態。
* **會員 CRM**：即時查看會員資料與手動調整會員點數。

### 🍳 廚房出餐系統 (KDS)
* **自動刷新看板**：定時自動抓取最新「待處理」訂單。
* **出餐控管**：廚房完成製作後點擊出餐，自動將訂單狀態更新為 `completed`。

---

## 📂 核心檔案架構與運作邏輯 (File Architecture)

專案目錄劃分為根目錄（後端管理模組）與 `phone/` 子目錄（顧客端模組）。

### ⚙️ 基礎建設與 API
* **`database.sql`**
  * **作用**：資料庫建置腳本。包含 `users`, `products`, `orders`, `order_items` 四個關聯資料表的建立語法及初始測試資料，具備外鍵 (Foreign Key) 約束防呆。
* **`phone/db.php`**
  * **作用**：全域資料庫連線檔。使用 PDO 建立連線，並負責啟動 `session_start()`。所有需要讀寫 DB 的頁面皆需 `require` 此檔。
* **`phone/api.php`**
  * **作用**：核心交易處理引擎。接收前端傳來的購物車 JSON 數據，開啟 PDO Transaction (交易機制) 執行：計算總金額、扣除庫存、新增訂單與明細、計算並發放點數。若庫存不足則 Rollback，確保資料一致性。

### 🛒 顧客端頁面 (位於 `phone/` 目錄)
* **`index.php`** (點餐主畫面)
  * **運作**：抓取 `status = 'active'` 且具備庫存的商品渲染成網格卡片。頂部判斷 Session 顯示會員名稱與登出按鈕。底層使用 JS 監聽點擊，將商品寫入 `localStorage` 並更新總價。
* **`checkout.php`** (確認訂單頁)
  * **運作**：讀取 `localStorage` 渲染購物車清單，允許使用者增減數量。提供點數累積試算，確認無誤後將資料以 `fetch` POST 至 `api.php` 完成結帳。
* **`auth.php`** (登入與註冊)
  * **運作**：採用單頁 Tab 切換邏輯 (JS 隱藏/顯示表單)。註冊時透過 `password_hash` 加密密碼，生成亂數 Token 並利用 PHPMailer 寄送開通信件。登入時驗證 `is_verified` 狀態。
* **`verify.php`** (信箱開通驗證)
  * **運作**：接收 URL 中的 `token` 參數，比對資料庫，若吻合則將 `is_verified` 設為 1 並清空 Token。
* **`logout.php`** (登出機制)
  * **運作**：執行 `session_destroy()`，隨後導向回 `index.php`。

### 👑 後端管理頁面 (位於根目錄)
* **`admin.php`** (管理員主控台)
  * **運作**：高度整合的單頁應用。
    * **頂部**：處理所有 POST 請求 (更新點數、上下架、新增商品、更換圖片)。包含圖片上傳邏輯與舊圖 `unlink()` 刪除機制。
    * **資料層**：透過複雜的 SQL `JOIN` 與 `GROUP BY` 撈取熱銷 Top 5 與 Chart.js 所需的連續七日營收陣列。
    * **視圖層**：使用 Tailwind Grid 系統排版，分為圖表區、排行榜、會員管理與商品管理區塊。
* **`kitchen.php`** (廚房系統)
  * **運作**：使用 `<meta http-equiv="refresh" content="10">` 實作簡易且穩定的自動刷新。查詢所有 `status = 'pending'` 的訂單並列出明細，點擊按鈕即更新狀態。

---

## 🛠️ 安裝與啟動指引 (Setup Guide)

1. **環境準備**：
   * 建議使用 **Laragon** 或 **XAMPP**。
   * 確認 PHP 版本 >= 8.0。
   * 確認已安裝 Composer。
2. **資料庫匯入**：
   * 打開資料庫管理工具 (如 HeidiSQL 或 phpMyAdmin)。
   * 執行專案根目錄下的 `database.sql` 建立 `pos_system` 資料庫與測試資料。
3. **資料庫連線設定**：
   * 開啟 `phone/db.php`。
   * 確認 `$host`, `$user` (預設 root), `$pass` (Laragon 預設為空字串 `''`) 與你的本地環境一致。
4. **安裝依賴套件 (PHPMailer)**：
   * 在終端機進入專案根目錄 (或 `phone/` 目錄，依你的 `composer.json` 位置而定)，執行：
     ```bash
     composer install
     ```
5. **設定 SMTP (寄信功能)**：
   * 開啟 `phone/auth.php`。
   * 找到 PHPMailer 設定區塊，填入你的發信 Gmail 與 **應用程式密碼 (App Password)**。
6. **啟動測試**：
   * 顧客入口：`http://localhost/Final Project/phone/index.php`
   * 後台入口：`http://localhost/Final Project/admin.php`

---

*Built with ❤️ utilizing Local LLM Vibe Coding workflows.*
