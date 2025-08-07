<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/debug.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
try {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if (empty($name) || empty($email) || empty($message)) {
        throw new Exception('Все поля должны быть заполнены');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Неверный формат email');
    }
    $attachment_path = null;
    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file_info = pathinfo($_FILES['image']['name']);
        $file_extension = strtolower($file_info['extension']);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_extension, $allowed_extensions)) {
            throw new Exception('Недопустимый тип файла. Разрешены только изображения.');
        }
        $new_filename = uniqid() . '_' . $_FILES['image']['name'];
        $upload_path = $upload_dir . $new_filename;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            throw new Exception('Ошибка при загрузке файла');
        }
        $attachment_path = 'uploads/' . $new_filename;
    }
    $stmt = $pdo->prepare("INSERT INTO feedback (name, email, message, attachment, ip, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$name, $email, $message, $attachment_path, $_SERVER['REMOTE_ADDR']]);
    $telegram_message = "📷 Фото: " . ($attachment_path ? basename($attachment_path) : "нет фото") . "\n";
    $telegram_message .= "👤 Имя: " . htmlspecialchars($name) . "\n";
    $telegram_message .= "📝 Тип: " . htmlspecialchars($_POST['type']) . "\n";
    $telegram_message .= "💬 Сообщение: " . htmlspecialchars($message) . "\n";
    $telegram_message .= "🌐 IP: " . $_SERVER['REMOTE_ADDR'];
    $telegram_data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $telegram_message,
        'parse_mode' => 'HTML'
    ];
    $ch = curl_init("https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $telegram_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
    if ($attachment_path && in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        $telegram_photo = new CURLFile($upload_path);
        $telegram_photo_data = [
            'chat_id' => TELEGRAM_CHAT_ID,
            'photo' => $telegram_photo,
            'caption' => $telegram_message,
            'parse_mode' => 'HTML'
        ];
        $ch = curl_init("https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendPhoto");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $telegram_photo_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
    logUserAction("Отправлено сообщение обратной связи от {$name} ({$email})");
    $_SESSION['feedback_success'] = true;
    header('Location: index.php');
    exit;
} catch (Exception $e) {
    error_log("Ошибка при отправке обратной связи: " . $e->getMessage());
    $_SESSION['feedback_error'] = $e->getMessage();
    header('Location: index.php');
    exit;
}
?>