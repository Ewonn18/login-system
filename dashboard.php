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
$recentPosts = [];
$recentComments = [];
$activityFeed = [];

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
        $recentPosts[] = $row;
        $activityFeed[] = [
            "type" => "post",
            "title" => $row["title"],
            "meta" => $row["category"],
            "created_at" => $row["created_at"],
            "description" => "You shared a new post in the community."
        ];
    }
    $recentPostsStmt->close();
}

$recentCommentsStmt = $conn->prepare(
    "SELECT c.content, c.created_at, p.title
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
        $recentComments[] = $row;
        $activityFeed[] = [
            "type" => "comment",
            "title" => $row["title"],
            "meta" => mb_strimwidth($row["content"], 0, 80, "..."),
            "created_at" => $row["created_at"],
            "description" => "You added a comment to a community post."
        ];
    }
    $recentCommentsStmt->close();
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
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.10),transparent_25%),radial-gradient(circle_at_bottom_right,rgba(16,185,129,0.10),transparent_25%)] pointer-events-none"></div>

  <div class="relative max-w-6xl mx-auto px-4 py-8 md:py-10">
    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl shadow-[0_0_40px_rgba(15,23,42,0.8)] overflow-hidden">
      <header class="bg-slate-900 border-b border-slate-800 px-6 md:px-8 py-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
        <div class="flex items-center gap-4">
          <?php if (!empty($user["profile_picture"])): ?>
            <img src="<?php echo htmlspecialchars($user["profile_picture"]); ?>" alt="Profile picture" class="w-16 h-16 rounded-2xl object-cover border border-slate-700">
          <?php else: ?>
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-sky-500/80 via-cyan-500/70 to-emerald-400/70 flex items-center justify-center text-2xl font-bold text-white border border-slate-700">
              <?php echo strtoupper(substr($user["name"], 0, 1)); ?>
            </div>
          <?php endif; ?>

          <div>
            <p class="text-xs uppercase tracking-[0.25em] text-sky-300">TechTrail Community</p>
            <h1 class="mt-2 text-2xl md:text-3xl font-semibold text-slate-50">
              Welcome back, <?php echo htmlspecialchars($user["name"]); ?>.
            </h1>
            <p class="mt-1 text-sm text-slate-400">Track your activity, improve your profile, and stay active in the community.</p>
            <?php if ($user["role"] === "admin"): ?>
              <span class="inline-flex mt-3 items-center rounded-full bg-amber-500/20 border border-amber-400/50 text-xs text-amber-200 px-3 py-1">Admin</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="flex flex-wrap gap-3">
          <a href="edit-profile.php" class="inline-flex items-center rounded-xl bg-sky-600/90 hover:bg-sky-500 text-sm font-medium text-white px-4 py-2.5 transition">Edit profile</a>
          <a href="community.php" class="inline-flex items-center rounded-xl bg-emerald-600/90 hover:bg-emerald-500 text-sm font-medium text-white px-4 py-2.5 transition">Open community</a>
          <a href="logout.php" class="inline-flex items-center rounded-xl bg-rose-600/90 hover:bg-rose-500 text-sm font-medium text-white px-4 py-2.5 transition">Sign out</a>
        </div>
      </header>

      <main class="p-6 md:p-8 space-y-6">
        <section class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-1 bg-slate-900/80 border border-slate-800 rounded-2xl p-5">
            <h2 class="text-base font-semibold text-slate-100">Profile snapshot</h2>
            <div class="mt-4 space-y-3 text-sm">
              <p><span class="text-slate-400">Name:</span> <?php echo htmlspecialchars($user["name"]); ?></p>
              <p><span class="text-slate-400">Email:</span> <?php echo htmlspecialchars($user["email"]); ?></p>
              <p><span class="text-slate-400">Member since:</span> <?php echo date("F j, Y", strtotime($user["created_at"])); ?></p>
              <p><span class="text-slate-400">School:</span> <?php echo htmlspecialchars($user["school"] ?? "Not specified"); ?></p>
              <p><span class="text-slate-400">Specialization:</span> <?php echo htmlspecialchars($user["specialization"] ?? "Not specified"); ?></p>
              <p><span class="text-slate-400">Skills:</span> <?php echo htmlspecialchars($user["skills"] ?? "Not specified"); ?></p>
            </div>
          </div>

          <div class="lg:col-span-2 bg-slate-900/80 border border-slate-800 rounded-2xl p-5">
            <h2 class="text-base font-semibold text-slate-100">Quick actions</h2>
            <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
              <a href="edit-profile.php" class="rounded-2xl border border-slate-800 bg-slate-900/60 hover:bg-slate-800/80 p-4 transition">
                <p class="text-sm font-semibold text-slate-100">Edit your profile</p>
                <p class="mt-1 text-xs text-slate-400">Update your developer details, skills, and badge.</p>
              </a>

              <a href="community.php" class="rounded-2xl border border-slate-800 bg-slate-900/60 hover:bg-slate-800/80 p-4 transition">
                <p class="text-sm font-semibold text-slate-100">Join the community</p>
                <p class="mt-1 text-xs text-slate-400">Create posts, join conversations, and support others.</p>
              </a>

              <a href="profile.php?id=<?php echo (int)$userId; ?>" class="rounded-2xl border border-slate-800 bg-slate-900/60 hover:bg-slate-800/80 p-4 transition">
                <p class="text-sm font-semibold text-slate-100">View public profile</p>
                <p class="mt-1 text-xs text-slate-400">See how your profile looks to the community.</p>
              </a>

              <a href="logout.php" class="rounded-2xl border border-rose-500/30 bg-rose-500/5 hover:bg-rose-500/10 p-4 transition">
                <p class="text-sm font-semibold text-rose-200">Sign out securely</p>
                <p class="mt-1 text-xs text-rose-300/70">End your current session and return to login.</p>
              </a>
            </div>
          </div>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5">
            <h2 class="text-base font-semibold text-slate-100">Profile completion</h2>
            <p class="mt-1 text-sm text-slate-400">Complete more fields to make your profile stand out.</p>

            <div class="mt-5">
              <div class="w-full h-3 bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-sky-500 transition-all duration-500" style="width: <?php echo $completionScore; ?>%;"></div>
              </div>
              <p class="mt-3 text-sm text-slate-200 font-medium"><?php echo $completionScore; ?>% complete</p>
            </div>

            <div class="mt-5 space-y-2 text-xs text-slate-400">
              <p><?php echo !empty($user["bio"]) ? "✓" : "○"; ?> Bio</p>
              <p><?php echo !empty($user["school"]) ? "✓" : "○"; ?> School</p>
              <p><?php echo !empty($user["specialization"]) ? "✓" : "○"; ?> Specialization</p>
              <p><?php echo !empty($user["skills"]) ? "✓" : "○"; ?> Skills</p>
              <p><?php echo !empty($user["profile_picture"]) ? "✓" : "○"; ?> Profile picture</p>
            </div>
          </div>

          <div class="lg:col-span-2 bg-slate-900/80 border border-slate-800 rounded-2xl p-5">
            <div class="flex items-center justify-between gap-4 flex-wrap">
              <div>
                <h2 class="text-base font-semibold text-slate-100">Recent activity feed</h2>
                <p class="mt-1 text-sm text-slate-400">Your latest posts and comments in one timeline.</p>
              </div>
              <a href="community.php" class="text-sm text-sky-400 hover:text-sky-300">Open community →</a>
            </div>

            <div class="mt-5 space-y-4">
              <?php if (empty($activityFeed)): ?>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 text-center">
                  <p class="text-sm text-slate-300 font-medium">No activity yet</p>
                  <p class="mt-2 text-xs text-slate-500">Start by updating your profile, creating a post, or joining a discussion.</p>
                </div>
              <?php else: ?>
                <?php foreach ($activityFeed as $activity): ?>
                  <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 flex gap-4 items-start">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold <?php echo $activity["type"] === "post" ? 'bg-sky-500/15 text-sky-300 border border-sky-500/20' : 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/20'; ?>">
                      <?php echo $activity["type"] === "post" ? "P" : "C"; ?>
                    </div>

                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between gap-3 flex-wrap">
                        <p class="text-sm font-semibold text-slate-100">
                          <?php echo $activity["type"] === "post" ? "New post published" : "Comment added"; ?>
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
      </main>
    </div>
  </div>
</body>
</html>