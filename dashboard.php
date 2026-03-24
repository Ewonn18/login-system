<?php
require_once "session.php";
require_once "auth.php";
require_auth();

$userId = $_SESSION["user_id"];

$stmt = $conn->prepare("SELECT name, email, created_at, bio, school, specialization, skills, profile_picture, role FROM users WHERE id = ?");
if (!$stmt) {
    $conn->close();
    die("Something went wrong.");
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows !== 1) {
    $stmt->close();
    $conn->close();
    session_unset();
    session_destroy();
    header("Location: index.php?panel=signin&type=error&message=" . urlencode("User not found."));
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

$_SESSION["user_name"] = $user["name"];
$_SESSION["user_email"] = $user["email"];
$_SESSION["user_role"] = $user["role"];

$totalPosts = 0;
$totalComments = 0;
$totalLikesReceived = 0;
$bestAnswers = 0;
$openMentorQuestions = 0;
$unreadNotifications = 0;
$mentorSpotlight = [];
$activityFeed = [];
$recentNotifications = [];

$countPosts = $conn->prepare("SELECT COUNT(*) AS total FROM posts WHERE user_id = ?");
if ($countPosts) {
    $countPosts->bind_param("i", $userId);
    $countPosts->execute();
    $row = $countPosts->get_result()->fetch_assoc();
    $totalPosts = (int)($row["total"] ?? 0);
    $countPosts->close();
}

$countComments = $conn->prepare("SELECT COUNT(*) AS total FROM comments WHERE user_id = ?");
if ($countComments) {
    $countComments->bind_param("i", $userId);
    $countComments->execute();
    $row = $countComments->get_result()->fetch_assoc();
    $totalComments = (int)($row["total"] ?? 0);
    $countComments->close();
}

$countBestAnswers = $conn->prepare("SELECT COUNT(*) AS total FROM comments WHERE user_id = ? AND is_best_answer = 1");
if ($countBestAnswers) {
    $countBestAnswers->bind_param("i", $userId);
    $countBestAnswers->execute();
    $row = $countBestAnswers->get_result()->fetch_assoc();
    $bestAnswers = (int)($row["total"] ?? 0);
    $countBestAnswers->close();
}

$countOpenMentor = $conn->prepare(
    "SELECT COUNT(*) AS total
     FROM posts p
     LEFT JOIN (
         SELECT post_id, COUNT(*) AS best_answer_count
         FROM comments
         WHERE is_best_answer = 1
         GROUP BY post_id
     ) best ON best.post_id = p.id
     WHERE p.post_type = 'mentor_help'
       AND COALESCE(best.best_answer_count, 0) = 0"
);
if ($countOpenMentor) {
    $countOpenMentor->execute();
    $row = $countOpenMentor->get_result()->fetch_assoc();
    $openMentorQuestions = (int)($row["total"] ?? 0);
    $countOpenMentor->close();
}

$countLikes = $conn->prepare(
    "SELECT COUNT(pl.id) AS total
     FROM post_likes pl
     INNER JOIN posts p ON p.id = pl.post_id
     WHERE p.user_id = ?"
);
if ($countLikes) {
    $countLikes->bind_param("i", $userId);
    $countLikes->execute();
    $row = $countLikes->get_result()->fetch_assoc();
    $totalLikesReceived = (int)($row["total"] ?? 0);
    $countLikes->close();
}

$countNotifications = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
if ($countNotifications) {
    $countNotifications->bind_param("i", $userId);
    $countNotifications->execute();
    $row = $countNotifications->get_result()->fetch_assoc();
    $unreadNotifications = (int)($row["total"] ?? 0);
    $countNotifications->close();
}

$recentNotificationsStmt = $conn->prepare(
    "SELECT title, message, created_at, is_read, link
     FROM notifications
     WHERE user_id = ?
     ORDER BY is_read ASC, created_at DESC
     LIMIT 4"
);
if ($recentNotificationsStmt) {
    $recentNotificationsStmt->bind_param("i", $userId);
    $recentNotificationsStmt->execute();
    $recentNotificationsResult = $recentNotificationsStmt->get_result();
    while ($row = $recentNotificationsResult->fetch_assoc()) {
        $recentNotifications[] = $row;
    }
    $recentNotificationsStmt->close();
}

$recentPostsStmt = $conn->prepare(
    "SELECT id, title, category, created_at
     FROM posts
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 5"
);
if ($recentPostsStmt) {
    $recentPostsStmt->bind_param("i", $userId);
    $recentPostsStmt->execute();
    $recentPostsResult = $recentPostsStmt->get_result();
    while ($row = $recentPostsResult->fetch_assoc()) {
        $activityFeed[] = [
            "type" => "post",
            "title" => $row["title"],
            "meta" => $row["category"],
            "created_at" => $row["created_at"],
            "description" => "You published a new community post."
        ];
    }
    $recentPostsStmt->close();
}

$recentCommentsStmt = $conn->prepare(
    "SELECT c.content, c.created_at, p.title, c.is_best_answer
     FROM comments c
     INNER JOIN posts p ON p.id = c.post_id
     WHERE c.user_id = ?
     ORDER BY c.created_at DESC
     LIMIT 5"
);
if ($recentCommentsStmt) {
    $recentCommentsStmt->bind_param("i", $userId);
    $recentCommentsStmt->execute();
    $recentCommentsResult = $recentCommentsStmt->get_result();
    while ($row = $recentCommentsResult->fetch_assoc()) {
        $activityFeed[] = [
            "type" => (int)$row["is_best_answer"] === 1 ? "best_answer" : "comment",
            "title" => $row["title"],
            "meta" => mb_strimwidth($row["content"], 0, 80, "..."),
            "created_at" => $row["created_at"],
            "description" => (int)$row["is_best_answer"] === 1
                ? "One of your replies was marked as the best answer."
                : "You added a comment to a community discussion."
        ];
    }
    $recentCommentsStmt->close();
}

$mentorSpotlightStmt = $conn->prepare(
    "SELECT p.id, p.title, p.category, p.author_name, p.created_at,
            COALESCE(reply_counts.reply_count, 0) AS reply_count
     FROM posts p
     LEFT JOIN (
         SELECT post_id, COUNT(*) AS reply_count
         FROM comments
         GROUP BY post_id
     ) reply_counts ON reply_counts.post_id = p.id
     LEFT JOIN (
         SELECT post_id, COUNT(*) AS best_answer_count
         FROM comments
         WHERE is_best_answer = 1
         GROUP BY post_id
     ) best ON best.post_id = p.id
     WHERE p.post_type = 'mentor_help'
       AND COALESCE(best.best_answer_count, 0) = 0
     ORDER BY p.created_at DESC
     LIMIT 4"
);
if ($mentorSpotlightStmt) {
    $mentorSpotlightStmt->execute();
    $mentorSpotlightResult = $mentorSpotlightStmt->get_result();
    while ($row = $mentorSpotlightResult->fetch_assoc()) {
        $mentorSpotlight[] = $row;
    }
    $mentorSpotlightStmt->close();
}

usort($activityFeed, function ($a, $b) {
    return strtotime($b["created_at"]) <=> strtotime($a["created_at"]);
});

$activityFeed = array_slice($activityFeed, 0, 6);

$conn->close();

$completionScore = 0;
if (!empty($user["bio"])) $completionScore += 20;
if (!empty($user["school"])) $completionScore += 20;
if (!empty($user["specialization"])) $completionScore += 20;
if (!empty($user["skills"])) $completionScore += 20;
if (!empty($user["profile_picture"])) $completionScore += 20;

function userInitial(string $name): string
{
    $trimmed = trim($name);
    return $trimmed !== "" ? strtoupper(substr($trimmed, 0, 1)) : "U";
}

function helpfulBadgeFromCount(int $count): string
{
    if ($count >= 10) return "Mentor Guide";
    if ($count >= 5) return "Helpful Mentor";
    if ($count >= 1) return "Community Helper";
    return "Growing Member";
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechTrail Community - Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
  <div class="fixed inset-0 pointer-events-none bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.14),transparent_22%),radial-gradient(circle_at_bottom_right,rgba(16,185,129,0.12),transparent_24%),linear-gradient(to_bottom,rgba(2,6,23,0.16),rgba(2,6,23,0.45))]"></div>

  <div class="relative max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8 space-y-6">
    <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 md:p-8">
      <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
        <div class="flex items-center gap-4">
          <?php if (!empty($user["profile_picture"])): ?>
            <img src="<?php echo htmlspecialchars($user["profile_picture"]); ?>" alt="Profile picture" class="w-20 h-20 rounded-3xl object-cover border border-slate-700 shadow-lg">
          <?php else: ?>
            <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-sky-500/80 via-cyan-500/70 to-emerald-400/70 flex items-center justify-center text-3xl font-bold text-white border border-slate-700 shadow-lg">
              <?php echo userInitial($user["name"]); ?>
            </div>
          <?php endif; ?>

          <div>
            <p class="text-xs uppercase tracking-[0.25em] text-sky-300">TechTrail Community</p>
            <h1 class="mt-2 text-2xl md:text-3xl font-semibold text-white">Welcome back, <?php echo htmlspecialchars($user["name"]); ?>.</h1>
            <p class="mt-1 text-sm text-slate-400">Track your growth, mentor impact, and community updates.</p>

            <div class="mt-3 flex flex-wrap gap-2">
              <span class="inline-flex items-center rounded-full bg-emerald-500/15 text-emerald-300 border border-emerald-400/20 px-3 py-1 text-xs font-medium">
                <?php echo htmlspecialchars(helpfulBadgeFromCount($bestAnswers)); ?>
              </span>
              <span class="inline-flex items-center rounded-full bg-sky-500/10 text-sky-200 border border-sky-400/20 px-3 py-1 text-xs font-medium">
                <?php echo $bestAnswers; ?> best answer(s)
              </span>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap gap-3">
          <a href="edit-profile.php" class="inline-flex items-center rounded-xl bg-sky-600/90 hover:bg-sky-500 text-sm font-medium text-white px-4 py-2.5 transition">Edit profile</a>
          <a href="community.php" class="inline-flex items-center rounded-xl bg-emerald-600/90 hover:bg-emerald-500 text-sm font-medium text-white px-4 py-2.5 transition">Open community</a>
          <a href="notifications.php" class="inline-flex items-center rounded-xl border border-sky-500/40 bg-sky-500/10 hover:bg-sky-500/20 text-sm font-medium text-sky-200 px-4 py-2.5 transition">
            Notifications <?php echo $unreadNotifications > 0 ? "(" . $unreadNotifications . ")" : ""; ?>
          </a>
          <a href="mentor-leaderboard.php" class="inline-flex items-center rounded-xl border border-slate-700 bg-slate-900/70 hover:bg-slate-800/90 text-sm font-medium text-slate-100 px-4 py-2.5 transition">Mentor leaderboard</a>
          <a href="logout.php" class="inline-flex items-center rounded-xl bg-rose-600/90 hover:bg-rose-500 text-sm font-medium text-white px-4 py-2.5 transition">Sign out</a>
        </div>
      </div>
    </section>

    <section class="grid grid-cols-1 sm:grid-cols-6 gap-4">
      <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
        <p class="text-xs uppercase tracking-[0.15em] text-slate-400">Your posts</p>
        <p class="mt-3 text-3xl font-semibold text-slate-50"><?php echo $totalPosts; ?></p>
      </div>
      <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
        <p class="text-xs uppercase tracking-[0.15em] text-slate-400">Your comments</p>
        <p class="mt-3 text-3xl font-semibold text-slate-50"><?php echo $totalComments; ?></p>
      </div>
      <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
        <p class="text-xs uppercase tracking-[0.15em] text-slate-400">Likes received</p>
        <p class="mt-3 text-3xl font-semibold text-slate-50"><?php echo $totalLikesReceived; ?></p>
      </div>
      <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5">
        <p class="text-xs uppercase tracking-[0.15em] text-slate-400">Best answers</p>
        <p class="mt-3 text-3xl font-semibold text-slate-50"><?php echo $bestAnswers; ?></p>
      </div>
      <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-5">
        <p class="text-xs uppercase tracking-[0.15em] text-amber-300">Open mentor questions</p>
        <p class="mt-3 text-3xl font-semibold text-white"><?php echo $openMentorQuestions; ?></p>
      </div>
      <div class="rounded-2xl border border-sky-500/20 bg-sky-500/10 p-5">
        <p class="text-xs uppercase tracking-[0.15em] text-sky-300">Unread notifications</p>
        <p class="mt-3 text-3xl font-semibold text-white"><?php echo $unreadNotifications; ?></p>
      </div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5">
        <h2 class="text-base font-semibold text-slate-100">Profile completion</h2>
        <div class="mt-5">
          <div class="w-full h-3 bg-slate-800 rounded-full overflow-hidden">
            <div class="h-full bg-sky-500 transition-all duration-500" style="width: <?php echo $completionScore; ?>%;"></div>
          </div>
          <p class="mt-3 text-sm text-slate-200 font-medium"><?php echo $completionScore; ?>% complete</p>
        </div>
      </div>

      <div class="lg:col-span-2 rounded-3xl border border-slate-800 bg-slate-900/80 p-5">
        <div class="flex items-center justify-between gap-4 flex-wrap">
          <div>
            <h2 class="text-base font-semibold text-slate-100">Recent activity feed</h2>
            <p class="mt-1 text-sm text-slate-400">Your latest posts, comments, and mentor wins.</p>
          </div>
          <a href="community.php" class="text-sm text-sky-400 hover:text-sky-300">Open community →</a>
        </div>

        <div class="mt-5 space-y-4">
          <?php if (empty($activityFeed)): ?>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 text-center">
              <p class="text-sm text-slate-300 font-medium">No activity yet</p>
            </div>
          <?php else: ?>
            <?php foreach ($activityFeed as $activity): ?>
              <?php
                $activityClass = 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/20';
                $activityLabel = 'C';

                if ($activity["type"] === "post") {
                    $activityClass = 'bg-sky-500/15 text-sky-300 border border-sky-500/20';
                    $activityLabel = 'P';
                } elseif ($activity["type"] === "best_answer") {
                    $activityClass = 'bg-amber-500/15 text-amber-300 border border-amber-500/20';
                    $activityLabel = '★';
                }
              ?>
              <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 flex gap-4 items-start">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold <?php echo $activityClass; ?>">
                  <?php echo $activityLabel; ?>
                </div>

                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-3 flex-wrap">
                    <p class="text-sm font-semibold text-slate-100">
                      <?php
                        if ($activity["type"] === "post") {
                            echo "New post published";
                        } elseif ($activity["type"] === "best_answer") {
                            echo "Best answer earned";
                        } else {
                            echo "Comment added";
                        }
                      ?>
                    </p>
                    <span class="text-xs text-slate-500"><?php echo date("M j, Y · g:i A", strtotime($activity["created_at"])); ?></span>
                  </div>

                  <p class="mt-1 text-xs text-slate-400"><?php echo htmlspecialchars($activity["description"]); ?></p>
                  <p class="mt-2 text-sm text-slate-200"><?php echo htmlspecialchars($activity["title"]); ?></p>
                  <p class="mt-1 text-xs text-slate-500"><?php echo htmlspecialchars($activity["meta"]); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 md:p-8">
        <div class="flex items-center justify-between gap-4 flex-wrap">
          <div>
            <p class="text-xs uppercase tracking-[0.25em] text-amber-300">Mentor Spotlight</p>
            <h2 class="mt-2 text-2xl font-semibold text-white">Unanswered mentor questions</h2>
            <p class="mt-2 text-sm text-slate-400">Jump in and help the next developer who needs guidance.</p>
          </div>
          <a href="community.php?post_type_filter=mentor_help&mentor_status=open" class="rounded-2xl border border-amber-400/20 bg-amber-500/10 px-4 py-2 text-sm text-amber-200 hover:bg-amber-500/15 transition">
            View all open questions
          </a>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4">
          <?php if (empty($mentorSpotlight)): ?>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 text-center">
              <p class="text-sm text-slate-300 font-medium">No open mentor questions right now.</p>
              <p class="mt-2 text-xs text-slate-500">That means the community is keeping up well.</p>
            </div>
          <?php else: ?>
            <?php foreach ($mentorSpotlight as $spot): ?>
              <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <div class="flex items-start justify-between gap-3 flex-wrap">
                  <div>
                    <p class="text-xs uppercase tracking-[0.15em] text-amber-300">Mentor Corner</p>
                    <h3 class="mt-2 text-lg font-semibold text-white"><?php echo htmlspecialchars($spot["title"]); ?></h3>
                  </div>
                  <span class="inline-flex items-center rounded-full bg-amber-500/15 text-amber-300 border border-amber-400/20 px-3 py-1 text-xs font-medium">
                    Open
                  </span>
                </div>

                <p class="mt-3 text-xs text-slate-500">
                  By <?php echo htmlspecialchars($spot["author_name"]); ?> · <?php echo htmlspecialchars($spot["category"]); ?> · <?php echo date("M j, Y", strtotime($spot["created_at"])); ?>
                </p>

                <div class="mt-4 flex items-center justify-between gap-3 flex-wrap">
                  <span class="text-xs text-slate-400"><?php echo (int)$spot["reply_count"]; ?> reply(s)</span>
                  <a href="community.php?search=<?php echo urlencode($spot["title"]); ?>" class="rounded-xl bg-emerald-600/90 hover:bg-emerald-500 px-4 py-2 text-xs font-medium text-white transition">
                    Open question
                  </a>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 md:p-8">
        <div class="flex items-center justify-between gap-4 flex-wrap">
          <div>
            <p class="text-xs uppercase tracking-[0.25em] text-sky-300">Notifications</p>
            <h2 class="mt-2 text-2xl font-semibold text-white">Recent updates</h2>
            <p class="mt-2 text-sm text-slate-400">See the latest replies and best-answer updates.</p>
          </div>
          <a href="notifications.php" class="rounded-2xl border border-sky-500/20 bg-sky-500/10 px-4 py-2 text-sm text-sky-200 hover:bg-sky-500/15 transition">
            Open notifications
          </a>
        </div>

        <div class="mt-6 space-y-4">
          <?php if (empty($recentNotifications)): ?>
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 text-center">
              <p class="text-sm text-slate-300 font-medium">No notifications yet.</p>
              <p class="mt-2 text-xs text-slate-500">Replies and best-answer updates will appear here.</p>
            </div>
          <?php else: ?>
            <?php foreach ($recentNotifications as $note): ?>
              <a href="<?php echo htmlspecialchars($note["link"]); ?>" class="block rounded-2xl border p-4 transition hover:bg-slate-800/70 <?php echo (int)$note["is_read"] === 0 ? 'border-sky-500/30 bg-sky-500/5' : 'border-slate-800 bg-slate-900/60'; ?>">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                  <p class="text-sm font-semibold text-white"><?php echo htmlspecialchars($note["title"]); ?></p>
                  <span class="text-xs text-slate-500"><?php echo date("M j, Y · g:i A", strtotime($note["created_at"])); ?></span>
                </div>
                <p class="mt-2 text-sm text-slate-300"><?php echo htmlspecialchars($note["message"]); ?></p>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </div>
</body>
</html>