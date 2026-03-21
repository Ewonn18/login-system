<?php
require_once "session.php";

$profileUserId = (int)($_GET["id"] ?? 0);

if ($profileUserId <= 0) {
    $conn->close();
    header("Location: community.php");
    exit();
}

$stmt = $conn->prepare(
    "SELECT id, name, bio, school, specialization, skills, profile_picture, created_at
     FROM users
     WHERE id = ?"
);
if (!$stmt) {
    $conn->close();
    die("Something went wrong.");
}

$stmt->bind_param("i", $profileUserId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows !== 1) {
    $stmt->close();
    $conn->close();
    die("User not found.");
}

$user = $result->fetch_assoc();
$stmt->close();

$posts = [];
$postStmt = $conn->prepare(
    "SELECT id, title, category, content, created_at
     FROM posts
     WHERE user_id = ?
     ORDER BY created_at DESC"
);
if ($postStmt) {
    $postStmt->bind_param("i", $profileUserId);
    $postStmt->execute();
    $postResult = $postStmt->get_result();
    while ($row = $postResult->fetch_assoc()) {
        $posts[] = $row;
    }
    $postStmt->close();
}

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechTrail Community - Public Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.10),transparent_25%),radial-gradient(circle_at_bottom_right,rgba(16,185,129,0.10),transparent_25%)] pointer-events-none"></div>

  <div class="relative max-w-6xl mx-auto px-4 py-8 md:py-10 space-y-6">
    <section class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 md:p-8 shadow-[0_0_40px_rgba(15,23,42,0.8)]">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div class="flex items-center gap-4">
          <?php if (!empty($user["profile_picture"])): ?>
            <img src="<?php echo htmlspecialchars($user["profile_picture"]); ?>" alt="Profile picture" class="w-24 h-24 rounded-3xl object-cover border border-slate-700 shadow-lg">
          <?php else: ?>
            <div class="w-24 h-24 rounded-3xl bg-gradient-to-br from-sky-500/80 via-cyan-500/70 to-emerald-400/70 flex items-center justify-center text-4xl font-semibold border border-slate-700">
              <?php echo strtoupper(substr($user["name"], 0, 1)); ?>
            </div>
          <?php endif; ?>

          <div>
            <p class="text-xs uppercase tracking-[0.25em] text-sky-300">TechTrail Profile</p>
            <h1 class="mt-2 text-2xl md:text-3xl font-semibold"><?php echo htmlspecialchars($user["name"]); ?></h1>
            <p class="text-slate-400 text-sm mt-1"><?php echo htmlspecialchars($user["specialization"] ?? "Student Developer"); ?></p>
            <p class="text-slate-500 text-xs mt-2">Member since <?php echo date("F j, Y", strtotime($user["created_at"])); ?></p>
          </div>
        </div>

        <div class="flex flex-wrap gap-3">
          <a href="community.php" class="inline-flex items-center rounded-xl border border-slate-600/80 bg-slate-900/60 hover:bg-slate-800/80 text-sm font-medium text-slate-100 px-4 py-2.5 transition">Back to community</a>
          <a href="dashboard.php" class="inline-flex items-center rounded-xl bg-sky-600/90 hover:bg-sky-500 text-sm font-medium text-white px-4 py-2.5 transition">Dashboard</a>
        </div>
      </div>

      <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
          <p class="text-slate-400 text-xs uppercase tracking-[0.15em]">School</p>
          <p class="mt-2 text-slate-100"><?php echo htmlspecialchars($user["school"] ?? "Not specified"); ?></p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
          <p class="text-slate-400 text-xs uppercase tracking-[0.15em]">Skills</p>
          <p class="mt-2 text-slate-100"><?php echo htmlspecialchars($user["skills"] ?? "Not specified"); ?></p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
          <p class="text-slate-400 text-xs uppercase tracking-[0.15em]">Focus</p>
          <p class="mt-2 text-slate-100"><?php echo htmlspecialchars($user["specialization"] ?? "Not specified"); ?></p>
        </div>
      </div>

      <div class="mt-6 rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
        <p class="text-slate-400 text-sm mb-2">Bio</p>
        <p class="text-slate-200 text-sm leading-7 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($user["bio"] ?? "No bio added yet.")); ?></p>
      </div>
    </section>

    <section class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 md:p-8 shadow-[0_0_40px_rgba(15,23,42,0.8)]">
      <div class="flex items-center justify-between gap-4 flex-wrap">
        <div>
          <h2 class="text-lg md:text-xl font-semibold">Posts by <?php echo htmlspecialchars($user["name"]); ?></h2>
          <p class="text-sm text-slate-400 mt-1">Browse the latest shared posts from this developer.</p>
        </div>
        <span class="inline-flex items-center rounded-full bg-sky-600/80 text-xs font-semibold px-3 py-1 text-white">
          <?php echo count($posts); ?> post(s)
        </span>
      </div>

      <?php if (empty($posts)): ?>
        <div class="mt-6 rounded-2xl border border-slate-800 bg-slate-900/70 p-8 text-center">
          <p class="text-sm text-slate-300 font-medium">No posts yet.</p>
          <p class="text-xs text-slate-500 mt-2">This profile has not shared any posts in the community yet.</p>
        </div>
      <?php else: ?>
        <div class="mt-6 space-y-4">
          <?php foreach ($posts as $post): ?>
            <article class="border border-slate-800 rounded-2xl p-5 bg-slate-900/70 shadow-[0_0_25px_rgba(15,23,42,0.25)]">
              <div class="flex items-start justify-between gap-3 flex-wrap">
                <div>
                  <h3 class="font-semibold text-slate-50 text-base"><?php echo htmlspecialchars($post["title"]); ?></h3>
                  <p class="text-xs text-slate-400 mt-1"><?php echo date("M j, Y · g:i A", strtotime($post["created_at"])); ?></p>
                </div>
                <span class="inline-flex items-center rounded-full bg-sky-600/80 text-xs font-semibold px-3 py-1 text-white">
                  <?php echo htmlspecialchars($post["category"]); ?>
                </span>
              </div>
              <p class="mt-4 text-sm text-slate-200 leading-7 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($post["content"])); ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</body>
</html>