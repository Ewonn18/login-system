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

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechTrail Community - Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-5xl bg-slate-900/80 border border-slate-800 rounded-3xl shadow-[0_0_40px_rgba(15,23,42,0.8)] overflow-hidden">
    <header class="bg-slate-900 border-b border-slate-800 px-6 md:px-8 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">TechTrail Community</p>
        <h1 class="mt-2 text-2xl md:text-3xl font-semibold text-slate-50">
          Welcome back, <?php echo htmlspecialchars($user["name"]); ?>.
        </h1>
        <p class="mt-1 text-sm text-slate-400">Manage your profile, join the community, and continue your developer journey.</p>
        <?php if ($user["role"] === "admin"): ?>
          <span class="inline-flex mt-3 items-center rounded-full bg-amber-500/20 border border-amber-400/50 text-xs text-amber-200 px-3 py-1">Admin</span>
        <?php endif; ?>
      </div>
    </header>

    <main class="p-6 md:p-8 space-y-6">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
          <p class="text-xs text-slate-400">Your posts</p>
          <p class="mt-2 text-2xl font-semibold text-slate-50"><?php echo $totalPosts; ?></p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
          <p class="text-xs text-slate-400">Your comments</p>
          <p class="mt-2 text-2xl font-semibold text-slate-50"><?php echo $totalComments; ?></p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4">
          <p class="text-xs text-slate-400">Likes received</p>
          <p class="mt-2 text-2xl font-semibold text-slate-50"><?php echo $totalLikesReceived; ?></p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <section class="lg:col-span-1 bg-slate-900/80 border border-slate-800 rounded-2xl p-5">
          <h2 class="text-base font-semibold text-slate-100">Profile snapshot</h2>
          <div class="mt-4 space-y-3 text-sm">
            <p><span class="text-slate-400">Name:</span> <?php echo htmlspecialchars($user["name"]); ?></p>
            <p><span class="text-slate-400">Email:</span> <?php echo htmlspecialchars($user["email"]); ?></p>
            <p><span class="text-slate-400">Member since:</span> <?php echo date("F j, Y", strtotime($user["created_at"])); ?></p>
            <p><span class="text-slate-400">School:</span> <?php echo htmlspecialchars($user["school"] ?? "Not specified"); ?></p>
            <p><span class="text-slate-400">Specialization:</span> <?php echo htmlspecialchars($user["specialization"] ?? "Not specified"); ?></p>
            <p><span class="text-slate-400">Skills:</span> <?php echo htmlspecialchars($user["skills"] ?? "Not specified"); ?></p>
          </div>
        </section>

        <section class="lg:col-span-2 bg-slate-900/80 border border-slate-800 rounded-2xl p-5">
          <h2 class="text-base font-semibold text-slate-100">Quick actions</h2>
          <div class="mt-5 flex flex-wrap gap-3">
            <a href="edit-profile.php" class="inline-flex items-center rounded-xl bg-sky-600/90 hover:bg-sky-500 text-sm font-medium text-white px-4 py-2.5 transition">Edit profile</a>
            <a href="community.php" class="inline-flex items-center rounded-xl bg-emerald-600/90 hover:bg-emerald-500 text-sm font-medium text-white px-4 py-2.5 transition">Open community hub</a>
            <a href="profile.php?id=<?php echo (int)$userId; ?>" class="inline-flex items-center rounded-xl border border-slate-600/80 bg-slate-900/60 hover:bg-slate-800/80 text-sm font-medium text-slate-100 px-4 py-2.5 transition">View public profile</a>
            <a href="logout.php" class="inline-flex items-center rounded-xl bg-rose-600/90 hover:bg-rose-500 text-sm font-medium text-white px-4 py-2.5 transition">Sign out</a>
          </div>
        </section>
      </div>
    </main>
  </div>
</body>
</html>