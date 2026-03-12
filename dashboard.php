<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php?panel=signin&type=error&message=" . urlencode("Please sign in first."));
    exit();
}

$userId = $_SESSION["user_id"];

$sql = "SELECT name, email, created_at, bio, school, specialization, skills, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Something went wrong.");
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    $conn->close();
    session_unset();
    session_destroy();
    header("Location: index.php?panel=signin&type=error&message=" . urlencode("User not found."));
    exit();
}

$user = $result->fetch_assoc();

$_SESSION["user_name"] = $user["name"];
$_SESSION["user_email"] = $user["email"];

$stmt->close();
$conn->close();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-5xl bg-slate-900/80 border border-slate-800 rounded-3xl shadow-[0_0_40px_rgba(15,23,42,0.8)] overflow-hidden">

      <!-- Top bar -->
      <header class="bg-gradient-to-r from-slate-900 via-slate-900 to-slate-900 border-b border-slate-800 px-6 md:px-8 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <p class="text-xs uppercase tracking-[0.25em] text-slate-400">IT Community Platform</p>
          <h1 class="mt-2 text-2xl md:text-3xl font-semibold text-slate-50">
            Welcome back, <?php echo htmlspecialchars($user["name"]); ?>.
          </h1>
          <p class="mt-1 text-sm text-slate-400">
            Review your profile and stay connected with the community.
          </p>
        </div>
        <div class="flex items-center gap-3">
          <div class="hidden md:flex flex-col items-end text-xs text-slate-400">
            <span class="uppercase tracking-wide text-slate-500">Signed in as</span>
            <span class="text-slate-200 text-sm"><?php echo htmlspecialchars($user["email"]); ?></span>
          </div>
          <?php if (!empty($user["profile_picture"])): ?>
            <div class="w-12 h-12 md:w-14 md:h-14 rounded-2xl overflow-hidden border border-slate-700 bg-slate-900 shadow-lg shadow-sky-500/20">
              <img
                src="<?php echo htmlspecialchars($user["profile_picture"]); ?>"
                alt="Profile picture"
                class="w-full h-full object-cover"
              />
            </div>
          <?php else: ?>
            <div class="w-12 h-12 md:w-14 md:h-14 rounded-2xl bg-gradient-to-br from-fuchsia-500/80 via-indigo-500/80 to-sky-400/80 flex items-center justify-center text-xl md:text-2xl font-semibold shadow-lg shadow-fuchsia-500/20">
              <?php echo strtoupper(substr($user["name"], 0, 1)); ?>
            </div>
          <?php endif; ?>
        </div>
      </header>

      <main class="p-6 md:p-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

          <!-- Profile card -->
          <section class="lg:col-span-1 bg-slate-900/80 border border-slate-800 rounded-2xl p-5 flex flex-col gap-4">
            <div>
              <h2 class="text-base font-semibold text-slate-100">Profile snapshot</h2>
              <p class="mt-1 text-xs text-slate-400">
                Basic details for your developer account.
              </p>
            </div>

            <div class="flex flex-col gap-1">
              <p class="text-sm text-slate-400">Name</p>
              <p class="text-base font-medium text-slate-50">
                <?php echo htmlspecialchars($user["name"]); ?>
              </p>
            </div>

            <div class="flex flex-col gap-1">
              <p class="text-sm text-slate-400">Email</p>
              <p class="text-base font-medium text-slate-50 break-all">
                <?php echo htmlspecialchars($user["email"]); ?>
              </p>
            </div>

            <div class="flex flex-col gap-1">
              <p class="text-sm text-slate-400">Member since</p>
              <p class="text-base font-medium text-slate-50">
                <?php echo date("F j, Y", strtotime($user["created_at"])); ?>
              </p>
            </div>

            <div class="flex flex-col gap-1">
              <p class="text-sm text-slate-400">Remember me preference</p>
              <p class="text-base font-medium <?php echo !empty($_SESSION["remember_me"]) ? 'text-emerald-400' : 'text-slate-300'; ?>">
                <?php echo !empty($_SESSION["remember_me"]) ? "Enabled for this session" : "Not enabled"; ?>
              </p>
            </div>

            <div class="flex flex-col gap-1">
              <p class="text-sm text-slate-400">Bio</p>
              <p class="text-sm text-slate-200 whitespace-pre-line">
                <?php
                  $bio = trim($user["bio"] ?? "");
                  echo $bio !== "" ? nl2br(htmlspecialchars($bio)) : "No bio added yet.";
                ?>
              </p>
            </div>

            <div class="flex flex-col gap-1">
              <p class="text-sm text-slate-400">School</p>
              <p class="text-sm font-medium text-slate-200">
                <?php
                  $school = trim($user["school"] ?? "");
                  echo $school !== "" ? htmlspecialchars($school) : "Not specified";
                ?>
              </p>
            </div>

            <div class="flex flex-col gap-1">
              <p class="text-sm text-slate-400">Specialization</p>
              <p class="text-sm font-medium text-slate-200">
                <?php
                  $specialization = trim($user["specialization"] ?? "");
                  echo $specialization !== "" ? htmlspecialchars($specialization) : "Not specified";
                ?>
              </p>
            </div>

            <div class="flex flex-col gap-1">
              <p class="text-sm text-slate-400">Skills</p>
              <p class="text-sm font-medium text-slate-200">
                <?php
                  $skills = trim($user["skills"] ?? "");
                  echo $skills !== "" ? htmlspecialchars($skills) : "Not specified";
                ?>
              </p>
            </div>
          </section>

          <!-- Main actions / community area -->
          <section class="lg:col-span-2 flex flex-col gap-6">
            <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5">
              <h2 class="text-base font-semibold text-slate-100">Your space in the community</h2>
              <p class="mt-1 text-sm text-slate-400">
                Update your profile, explore the community hub, or return to the welcome screen.
              </p>

              <div class="mt-5 flex flex-wrap gap-3">
                <a
                  href="edit-profile.php"
                  class="inline-flex items-center gap-2 rounded-xl bg-sky-600/90 hover:bg-sky-500 text-sm font-medium text-white px-4 py-2.5 shadow-md shadow-sky-500/30 transition"
                >
                  <span>Edit profile</span>
                </a>

                <a
                  href="community.php"
                  class="inline-flex items-center gap-2 rounded-xl bg-emerald-600/90 hover:bg-emerald-500 text-sm font-medium text-white px-4 py-2.5 shadow-md shadow-emerald-500/30 transition"
                >
                  <span>Open community hub</span>
                </a>

                <a
                  href="index.php"
                  class="inline-flex items-center gap-2 rounded-xl border border-slate-600/80 bg-slate-900/60 hover:bg-slate-800/80 text-sm font-medium text-slate-100 px-4 py-2.5 transition"
                >
                  <span>Back to welcome</span>
                </a>

                <a
                  href="logout.php"
                  class="inline-flex items-center gap-2 rounded-xl bg-rose-600/90 hover:bg-rose-500 text-sm font-medium text-white px-4 py-2.5 shadow-md shadow-rose-500/30 transition"
                >
                  <span>Sign out</span>
                </a>
              </div>
            </div>

            <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5">
              <h3 class="text-sm font-semibold text-slate-100">Account activity</h3>
              <p class="mt-1 text-xs text-slate-400">
                You are securely signed in to your IT community account. Use the actions above to manage your presence.
              </p>
              <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-3 py-3">
                  <p class="text-slate-400 mb-1">Status</p>
                  <p class="text-emerald-400 font-medium">Online</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-3 py-3">
                  <p class="text-slate-400 mb-1">Session</p>
                  <p class="text-slate-200 font-medium">Active and protected</p>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-3 py-3">
                  <p class="text-slate-400 mb-1">Community</p>
                  <p class="text-slate-200 font-medium">Share and learn together</p>
                </div>
              </div>
            </div>
          </section>
        </div>
      </main>
    </div>
  </body>
</html>