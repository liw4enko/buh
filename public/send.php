<?php
/**
 * Обработчик формы заявки для хостинга Beget (PHP + функция mail()).
 * Форма на сайте отправляет POST сюда, скрипт валидирует и шлёт письмо.
 *
 * ⚠️ ПЕРЕД ПУБЛИКАЦИЕЙ ЗАПОЛНИТЬ НАСТРОЙКИ НИЖЕ:
 *   $TO   — почта, куда падают заявки (лучше ящик на этом же домене Beget).
 *   $FROM — адрес-отправитель; ОБЯЗАТЕЛЬНО ящик на вашем домене на Beget,
 *           иначе письма уйдут в спам или не отправятся (SPF/DKIM домена).
 */

// ================= НАСТРОЙКИ =================
$TO      = 'info@milabuh.ru';                   // куда слать заявки
$FROM    = 'info@milabuh.ru';                   // ящик-отправитель на вашем домене (SPF/DKIM домена)
$FROMNAME = 'Сайт бухгалтера';
$SUBJECT = 'Новая заявка с сайта';
// ============================================

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method']);
    exit;
}

// Антиспам-ловушка: скрытое поле website должно оставаться пустым.
// Боты его заполняют — тихо отвечаем «успех», письмо не шлём.
if (!empty($_POST['website'])) {
    echo json_encode(['ok' => true]);
    exit;
}

// Убираем переносы строк и служебные последовательности (защита от header-injection).
function clean_line($v) {
    $v = (string) $v;
    $v = str_replace(["\r", "\n", "%0a", "%0d", "\0"], ' ', $v);
    return trim($v);
}

$name    = clean_line($_POST['name'] ?? '');
$contact = clean_line($_POST['phone'] ?? '');
$task    = trim((string) ($_POST['task'] ?? ''));
$task    = mb_substr($task, 0, 1000, 'UTF-8');

// Серверная валидация (клиентскую дублируем — на бэкенде нельзя доверять фронту).
$errors = [];
if (mb_strlen($name, 'UTF-8') < 2)    $errors['name']  = 'Введите имя';
if (mb_strlen($contact, 'UTF-8') < 3) $errors['phone'] = 'Укажите телефон или мессенджер';
if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '';

$body  = "Новая заявка с сайта\n\n";
$body .= "Имя:      {$name}\n";
$body .= "Контакт:  {$contact}\n";
$body .= "Задача:   " . ($task !== '' ? $task : '—') . "\n\n";
$body .= "IP:       {$ip}\n";
$body .= "Время:    " . date('d.m.Y H:i') . "\n";

$subjectEnc = '=?UTF-8?B?' . base64_encode($SUBJECT) . '?=';
$fromEnc    = '=?UTF-8?B?' . base64_encode($FROMNAME) . '?= <' . $FROM . '>';

$headers  = "From: {$fromEnc}\r\n";
$headers .= "Reply-To: {$FROM}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";

$sent = @mail($TO, $subjectEnc, $body, $headers, '-f' . $FROM);

if ($sent) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'send']);
}
