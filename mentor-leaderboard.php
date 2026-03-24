<?php
require_once "session.php";
require_once "auth.php";
require_auth();

$leaders = [];
$stmt = $conn->prepare(
    "SELECT u.id, u.name, u.specialization, u.profile_picture, COUNT(c.id) AS helpful_count
     FROM users u
     INNER JOIN comments c ON c.user_id = u.id
     WHERE c.is_best_answer = 1
     GROUP BY u.id, u.name, u.specialization, u.profile_picture
     ORDER BY helpful_count DESC, u.name ASC"
);

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $leaders[] = $row;
    }
    $stmt->close();
}

$conn->close();

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
  <title>TechTrail Community - Mentor Leaderboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
  <div class="fixed inset-0 pointer-events-none bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.14),transparent_22%),radial-gradient(circle_at_bottom_right,rgba(16,185,129,0.12),transparent_24%),linear-gradient(to_bottom,rgba(2,6,23,0.15),rgba(2,6,23,0.45))]"></div>

  <div class="relative">
    <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-xl">
      <div class="max-w-7xl mx-auto px-4 md:px-6">
        <div class="flex items-center justify-between gap-4 py-4">
          <div>
            <p class="text-[11px] uppercase tracking-[0.32em] text-sky-300">TechTrail Community</p>
            <h1 class="mt-1 text-lg md:text-2xl font-semibold text-white">Mentor Leaderboard</h1>
          </div>

          <div class="flex flex-wrap items-center gap-2 md:gap-3">
            <a href="community.php" class="rounded-xl border border-slate-700 bg-slate-900/70 hover:bg-slate-800/90 px-4 py-2 text-sm text-slate-100 transition">Community</a>
            <a href="dashboard.php" class="rounded-xl border border-sky-500/40 bg-sky-500/10 hover:bg-sky-500/20 px-4 py-2 text-sm text-sky-200 transition">Dashboard</a>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 md:px-6 py-6 md:py-8 space-y-6">
      <section class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6 md:p-8">
        <h2 class="text-2xl md:text-3xl font-bold text-white">Most Helpful Members</h2>
        <p class="mt-2 text-sm text-slate-400">Ranked by replies marked as best answer in Mentor Corner.</p>

        <?php if (empty($leaders)): ?>
          <div class="mt-6 rounded-2xl border border-slate-800 bg-slate-900/60 p-8 text-center">
            <p class="text-slate-300">No leaderboard data yet.</p>
          </div>
        <?php else: ?>
          <div class="mt-6 space-y-4">
            <?php foreach ($leaders as $index => $leader): ?>
              <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5 flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-4">
                  <div class="w-10 text-lg font-bold text-slate-400">#<?php echo $index + 1; ?></div>

                  <?php if (!empty($leader["profile_picture"])): ?>
                    <img src="<?php echo htmlspecialchars($leader["profile_picture"]); ?>" alt="Profile picture" class="w-14 h-14 rounded-2xl object-cover border border-slate-700">
                  <?php else: ?>
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-500/80 via-cyan-500/70 to-emerald-400/70 flex items-center justify-center text-lg font-bold text-white border border-slate-700">
                      <?php echo userInitial($leader["name"]); ?>
                    </div>
                  <?php endif; ?>

                  <div>
                    <a href="profile.php?id=<?php echo (int)$leader["id"]; ?>" class="text-base font-semibold text-white hover:text-sky-300">
                      <?php echo htmlspecialchars($leader["name"]); ?>
                    </a>
                    <p class="mt-1 text-sm text-slate-400"><?php echo htmlspecialchars($leader["specialization"] ?: "Student Developer"); ?></p>
                    <p class="mt-2 inline-flex items-center rounded-full bg-emerald-500/15 text-emerald-300 border border-emerald-400/20 px-3 py-1 text-xs font-medium">
                      <?php echo htmlspecialchars(helpfulBadgeFromCount((int)$leader["helpful_count"])); ?>
                    </p>
                  </div>
                </div>

                <div class="text-right">
                  <p class="text-2xl font-bold text-white"><?php echo (int)$leader["helpful_count"]; ?></p>
                  <p class="text-xs text-slate-500">best answer(s)</p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>