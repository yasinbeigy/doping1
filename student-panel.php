<?php
require_once __DIR__ . '/app/layout.php';
$pdo = db();
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['student_name'] ?? '');
    $contact = trim($_POST['student_contact'] ?? '');
    $grade = trim($_POST['grade'] ?? '');
    $body = trim($_POST['message'] ?? '');
    if ($name && $contact && $body) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT id FROM advisor_threads WHERE student_contact = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$contact]);
        $threadId = (int)$stmt->fetchColumn();
        if (!$threadId) {
            $stmt = $pdo->prepare('INSERT INTO advisor_threads (student_name, student_contact, grade, updated_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)');
            $stmt->execute([$name, $contact, $grade]);
            $threadId = (int)$pdo->lastInsertId();
        }
        $stmt = $pdo->prepare('INSERT INTO advisor_messages (thread_id, sender, body) VALUES (?, "student", ?)');
        $stmt->execute([$threadId, $body]);
        $pdo->prepare('UPDATE advisor_threads SET status="open", updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$threadId]);
        $pdo->commit();
        $notice = 'پیام شما برای مشاور ارسال شد.';
    }
}
$contact = trim($_GET['contact'] ?? $_POST['student_contact'] ?? '');
$threads = [];
if ($contact) {
    $stmt = $pdo->prepare('SELECT * FROM advisor_threads WHERE student_contact = ? ORDER BY updated_at DESC, id DESC');
    $stmt->execute([$contact]);
    $threads = $stmt->fetchAll();
}
page_start('پنل دانش‌آموز | پیام به مشاور');
?>
<section class="page-banner"><div class="container"><h1>پیام به مشاور</h1><p>پیام‌هایی که اینجا ارسال می‌کنی در بخش مشاور پنل مدیریت نمایش داده می‌شود.</p></div></section>
<section class="section"><div class="container advisor-public-layout"><div class="auth-card auth-card-clean"><h2>ارسال پیام جدید</h2><?php if ($notice): ?><div class="success-box"><?= e($notice) ?></div><?php endif; ?><form method="post" class="auth-form"><label><span>نام</span><input name="student_name" required></label><label><span>شماره تماس</span><input name="student_contact" value="<?= e($contact) ?>" required></label><label><span>پایه</span><input name="grade" placeholder="مثلاً یازدهم"></label><label><span>پیام</span><textarea name="message" required rows="5"></textarea></label><button class="btn btn-primary">ارسال پیام</button></form></div><div class="auth-card auth-card-clean"><h2>پیگیری پیام‌ها</h2><form method="get" class="auth-form"><label><span>شماره تماس برای مشاهده گفتگو</span><input name="contact" value="<?= e($contact) ?>"></label><button class="btn btn-outline">نمایش گفتگو</button></form><?php foreach ($threads as $thread): ?><div class="chat-thread-public"><h3><?= e($thread['student_name']) ?> - <?= e($thread['status']) ?></h3><?php $m=$pdo->prepare('SELECT * FROM advisor_messages WHERE thread_id=? ORDER BY id'); $m->execute([$thread['id']]); foreach ($m->fetchAll() as $msg): ?><div class="chat-bubble <?= $msg['sender']==='advisor'?'advisor':'student' ?>"><strong><?= $msg['sender']==='advisor'?'مشاور':'دانش‌آموز' ?>:</strong> <?= nl2br(e($msg['body'])) ?></div><?php endforeach; ?></div><?php endforeach; ?></div></div></section>
<?php page_end(); ?>
