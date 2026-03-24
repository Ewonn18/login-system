<?php
require_once "session.php";
require_once "auth.php";
require_once "csrf.php";
require_once "notification-helper.php";
require_auth();

$userId = $_SESSION["user_id"];
$csrfToken = get_csrf_token();

if (isset($_GET["read"])) {
    $notificationId = (int)$_GET["read"];
    if ($notificationId > 0) {
        mark_notification_as_read($conn, $notificationId, $userId);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedToken = $_POST["csrf_token"] ?? "";
    if (!is_valid_csrf_token($postedToken)) {
        header("Location: notifications.php?type=error&message=" . urlencode("Invalid request."));
        exit();
    }

    $formType = $_POST["form_type"] ?? "";

    if ($formType === "mark_all_read") {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: notifications.php?type=success&message=" . urlencode("All notifications marked as read."));
        exit();
    }

    if ($formType === "mark_single_read") {
        $notificationId = (int)($_POST["notification_id"] ?? 0);
        if ($notificationId > 0) {
            mark_notification_as_read($conn, $notificationId, $userId);
        }

        header("Location: notifications.php?type=success&message=" . urlencode("Notification marked as read."));
        exit();
    }
}

$message = "";
$messageType = "";
if (isset($_GET["message"], $_GET["type"])) {
    $message = trim((string)$_GET["message"]);
    $messageType = $_GET["type"] === "success" ? "success" : "error";
}

$notifications = [];
$stmt = $conn->prepare("
    SELECT n.id, n.type, n.title, n.message, n.link, n.is_read, n.created_at,
           u.name AS actor_name
    FROM notifications n
    LEFT JOIN users u ON u.id = n.actor_user_id
    WHERE n.user_id = ?
    ORDER BY n.is_read ASC, n.created_at DESC
");
if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}

$unreadCount = get_unread_notification_count($conn, $userId);

$conn->close();

function notificationBadgeClass(string $type): string
{
    if ($type === "best_answer") {
        return "bg-emerald-500/15 text-emerald-300 border border-emerald-400/20";
    }
    if ($type === "post_reply") {
        return "bg-sky-500/15 text-sky-300 border border-sky-400/20";
    }
    return "bg-slate-800 text-slate-200 border border-slate-700";
}

function notificationTypeLabel(string $type): string
{
    if ($type === "best_answer") return "Best Answer";
    if ($type === "post_reply") return "Reply";
    return "Update";
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechTrail Community - Notifications</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
  <div class="fixed inset-0 pointer-events-none bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.14),transparent_22%),radial-gradient(circle_at_bottom_right,rgba(16,185,129,0.12),transparent_24%),linear-gradient(to_bottom,rgba(2,6,23,0.16),rgba(2,6,23,0.45))]"></div>

  <div class="relative max-w-5xl mx-auto px-4 md:px-6 py-6 md:py-8 space-y-6">
    <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 md:p-8">
      <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
          <p class="text-xs uppercase tracking-[0.25em] text-sky-300">TechTrail Community</p>
          <h1 class="mt-2 text-3xl font-semibold text-white">Notifications</h1>
          <p class="mt-2 text-sm text-slate-400"><?php echo $unreadCount; ?> unread notification(s)</p>
        </div>

        <div class="flex flex-wrap gap-3">
          <a href="dashboard.php" class="rounded-xl border border-slate-700 bg-slate-900/70 px-4 py-2 text-sm text-slate-100 hover:bg-slate-800 transition">Dashboard</a>
          <a href="community.php" class="rounded-xl border border-sky-500/40 bg-sky-500/10 px-4 py-2 text-sm text-sky-200 hover:bg-sky-500/20 transition">Community</a>

          <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="form_type" value="mark_all_read">
            <button type="submit" class="rounded-xl bg-emerald-600/90 hover:bg-emerald-500 px-4 py-2 text-sm font-medium text-white transition">
              Mark all as read
            </button>
          </form>
        </div>
      </div>
    </section>

    <?php if ($message !== ""): ?>
      <div class="rounded-2xl border px-4 py-3 text-sm <?php echo $messageType === 'success'
          ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200'
          : 'border-rose-500/40 bg-rose-500/10 text-rose-200'; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 md:p-8">
      <?php if (empty($notifications)): ?>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-8 text-center">
          <p class="text-sm text-slate-300 font-medium">No notifications yet.</p>
          <p class="mt-2 text-xs text-slate-500">Replies and best-answer updates will appear here.</p>
        </div>
      <?php else: ?>
        <div class="space-y-4">
          <?php foreach ($notifications as $notification): ?>
            <div class="rounded-2xl border p-4 <?php echo (int)$notification["is_read"] === 0
              ? 'border-sky-500/30 bg-sky-500/5'
              : 'border-slate-800 bg-slate-900/60'; ?>">
              <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-medium <?php echo notificationBadgeClass($notification["type"]); ?>">
                      <?php echo htmlspecialchars(notificationTypeLabel($notification["type"])); ?>
                    </span>

                    <?php if ((int)$notification["is_read"] === 0): ?>
                      <span class="inline-flex items-center rounded-full bg-amber-500/15 text-amber-300 border border-amber-400/20 px-3 py-1 text-[10px] font-medium uppercase tracking-[0.15em]">
                        New
                      </span>
                    <?php endif; ?>
                  </div>

                  <h3 class="mt-3 text-sm font-semibold text-white">
                    <?php echo htmlspecialchars($notification["title"]); ?>
                  </h3>

                  <p class="mt-2 text-sm leading-7 text-slate-300">
                    <?php echo htmlspecialchars($notification["message"]); ?>
                  </p>

                  <p class="mt-3 text-xs text-slate-500">
                    <?php echo date("M j, Y · g:i A", strtotime($notification["created_at"])); ?>
                  </p>
                </div>

                <div class="flex flex-wrap gap-2">
                  <a href="<?php echo htmlspecialchars($notification["link"]); ?>" class="rounded-xl border border-slate-700 bg-slate-900/70 px-3 py-2 text-xs text-slate-100 hover:bg-slate-800 transition">
                    Open
                  </a>

                  <?php if ((int)$notification["is_read"] === 0): ?>
                    <form method="POST">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="form_type" value="mark_single_read">
                      <input type="hidden" name="notification_id" value="<?php echo (int)$notification["id"]; ?>">
                      <button type="submit" class="rounded-xl bg-emerald-600/90 hover:bg-emerald-500 px-3 py-2 text-xs font-medium text-white transition">
                        Mark read
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</body>
</html>