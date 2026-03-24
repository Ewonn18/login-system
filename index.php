<?php
require_once "session.php";
require_once "csrf.php";

$message = "";
$messageType = "";
$panel = "signin";

if (isset($_GET["message"]) && isset($_GET["type"])) {
    $message = htmlspecialchars($_GET["message"]);
    $messageType = $_GET["type"] === "success" ? "success" : "error";
}

if (isset($_GET["panel"]) && in_array($_GET["panel"], ["signin", "signup"], true)) {
    $panel = $_GET["panel"];
}

if (isset($_SESSION["user_id"])) {
    $conn->close();
    header("Location: dashboard.php");
    exit();
}

$csrfToken = get_csrf_token();
$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechTrail Community - Sign In</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 overflow-x-hidden">
  <div class="fixed inset-0 pointer-events-none bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.14),transparent_22%),radial-gradient(circle_at_bottom_right,rgba(16,185,129,0.12),transparent_24%),linear-gradient(to_bottom,rgba(2,6,23,0.16),rgba(2,6,23,0.45))]"></div>

  <div class="relative min-h-screen">
    <?php if (!empty($message)): ?>
      <div class="fixed top-5 left-1/2 -translate-x-1/2 z-50 w-full max-w-md px-4">
        <div class="rounded-2xl border px-4 py-3 text-sm shadow-lg <?php echo $messageType === 'success'
          ? 'border-emerald-400/40 bg-emerald-500/90 text-white'
          : 'border-rose-300/40 bg-rose-500/90 text-white'; ?>">
          <?php echo $message; ?>
        </div>
      </div>
    <?php endif; ?>

    <main class="max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-10 min-h-screen flex items-center">
      <div class="w-full rounded-[32px] border border-slate-800 bg-slate-900/70 backdrop-blur-xl shadow-[0_0_60px_rgba(15,23,42,0.55)] overflow-hidden">
        <div class="grid grid-cols-1 xl:grid-cols-12">
          <section class="xl:col-span-7 p-6 sm:p-8 lg:p-10 xl:p-12 border-b xl:border-b-0 xl:border-r border-slate-800">
            <div class="max-w-3xl">
              <p class="inline-flex items-center gap-2 rounded-full border border-sky-400/20 bg-sky-500/10 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.25em] text-sky-300">
                <i class="fa-solid fa-code"></i>
                Student Developer Community
              </p>

              <h1 class="mt-5 text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight leading-tight text-white">
                Learn faster with a developer community that grows with you.
              </h1>

              <p class="mt-5 max-w-2xl text-sm sm:text-base leading-8 text-slate-300">
                TechTrail Community is a space for young developers to ask questions, share insights, build confidence, and connect with others on the same path.
              </p>

              <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                  <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-500/15 text-sky-300 border border-sky-400/20">
                    <i class="fa-solid fa-book-open"></i>
                  </div>
                  <h2 class="mt-4 text-base font-semibold text-white">Learn from developers</h2>
                  <p class="mt-2 text-sm leading-7 text-slate-400">
                    Discover practical lessons, coding tips, and real developer experiences from the community.
                  </p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                  <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500/15 text-emerald-300 border border-emerald-400/20">
                    <i class="fa-solid fa-lightbulb"></i>
                  </div>
                  <h2 class="mt-4 text-base font-semibold text-white">Share insights</h2>
                  <p class="mt-2 text-sm leading-7 text-slate-400">
                    Post what you learned, ask focused questions, and help other learners move forward.
                  </p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                  <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-cyan-500/15 text-cyan-300 border border-cyan-400/20">
                    <i class="fa-solid fa-users"></i>
                  </div>
                  <h2 class="mt-4 text-base font-semibold text-white">Connect and grow</h2>
                  <p class="mt-2 text-sm leading-7 text-slate-400">
                    Build your developer identity while connecting with students and builders in tech.
                  </p>
                </div>
              </div>

              <div class="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                  <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Built for</p>
                  <p class="mt-2 text-sm font-semibold text-white">Student developers</p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                  <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Community style</p>
                  <p class="mt-2 text-sm font-semibold text-white">Discussion-driven</p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                  <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Goal</p>
                  <p class="mt-2 text-sm font-semibold text-white">Progress through sharing</p>
                </div>
              </div>

              <div class="mt-8 rounded-3xl border border-slate-800 bg-gradient-to-r from-slate-900/80 to-slate-950/70 p-5">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                  <div>
                    <p class="text-sm font-semibold text-white">Start your developer journey with more support.</p>
                    <p class="mt-1 text-sm text-slate-400">
                      Create your profile, join conversations, and keep learning through community experience.
                    </p>
                  </div>

                  <div class="flex items-center gap-2 text-xs text-slate-400">
                    <span class="rounded-full border border-slate-700 bg-slate-900/70 px-3 py-1.5">PHP</span>
                    <span class="rounded-full border border-slate-700 bg-slate-900/70 px-3 py-1.5">MySQL</span>
                    <span class="rounded-full border border-slate-700 bg-slate-900/70 px-3 py-1.5">Tailwind</span>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section class="xl:col-span-5 p-6 sm:p-8 lg:p-10 xl:p-12">
            <div class="max-w-md mx-auto">
              <div class="text-center">
                <p class="text-[11px] uppercase tracking-[0.32em] text-sky-300">TechTrail Community</p>
                <h2 class="mt-3 text-3xl md:text-4xl font-bold tracking-tight text-white">
                  Welcome in
                </h2>
                <p class="mt-3 text-sm leading-7 text-slate-400">
                  Sign in to continue your journey, or create an account to start building your community presence.
                </p>
              </div>

              <div class="mt-8 flex items-center gap-2 rounded-2xl border border-slate-800 bg-slate-950/60 p-2">
                <button
                  id="signinTab"
                  type="button"
                  class="flex-1 rounded-xl px-4 py-3 text-sm font-semibold transition <?php echo $panel === 'signin'
                    ? 'bg-emerald-400 text-slate-950'
                    : 'text-slate-300 hover:bg-slate-800'; ?>"
                >
                  Sign in
                </button>
                <button
                  id="signupTab"
                  type="button"
                  class="flex-1 rounded-xl px-4 py-3 text-sm font-semibold transition <?php echo $panel === 'signup'
                    ? 'bg-sky-400 text-slate-950'
                    : 'text-slate-300 hover:bg-slate-800'; ?>"
                >
                  Sign up
                </button>
              </div>

              <div id="signinPanel" class="<?php echo $panel === 'signup' ? 'hidden' : ''; ?>">
                <form action="login.php" method="POST" class="mt-6 space-y-4">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                  <div>
                    <label class="block text-sm text-slate-300 mb-2">Email address</label>
                    <input
                      type="email"
                      name="email"
                      placeholder="Enter your email"
                      required
                      class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 placeholder-slate-500 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                    >
                  </div>

                  <div>
                    <label class="block text-sm text-slate-300 mb-2">Password</label>
                    <div class="relative">
                      <input
                        type="password"
                        name="password"
                        id="signinPassword"
                        placeholder="Enter your password"
                        required
                        class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 pr-12 text-sm text-slate-100 placeholder-slate-500 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                      >
                      <button
                        type="button"
                        onclick="togglePassword('signinPassword', 'signinEye')"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white"
                      >
                        <i id="signinEye" class="fa-solid fa-eye"></i>
                      </button>
                    </div>
                  </div>

                  <div class="flex items-center justify-between gap-4 text-sm">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-300">
                      <input type="checkbox" name="remember" class="accent-emerald-500 w-4 h-4">
                      <span>Remember me</span>
                    </label>

                    <a href="forgot-password.php" class="text-sky-300 hover:text-sky-200 hover:underline">
                      Forgot password?
                    </a>
                  </div>

                  <button
                    type="submit"
                    class="w-full rounded-2xl bg-emerald-400 hover:bg-emerald-300 text-slate-950 font-bold py-3.5 transition shadow-[0_0_25px_rgba(52,211,153,0.22)]"
                  >
                    Sign in and continue
                  </button>
                </form>

                <div class="mt-6 rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                  <p class="text-xs uppercase tracking-[0.15em] text-slate-400">Why sign in?</p>
                  <ul class="mt-3 space-y-2 text-sm text-slate-300">
                    <li>• Access your dashboard and public profile</li>
                    <li>• Create posts and join discussions</li>
                    <li>• Track your growth in the community</li>
                  </ul>
                </div>
              </div>

              <div id="signupPanel" class="<?php echo $panel === 'signin' ? 'hidden' : ''; ?>">
                <form action="register.php" method="POST" class="mt-6 space-y-4">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                  <div>
                    <label class="block text-sm text-slate-300 mb-2">Full name</label>
                    <input
                      type="text"
                      name="name"
                      placeholder="Enter your full name"
                      required
                      class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 placeholder-slate-500 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                    >
                  </div>

                  <div>
                    <label class="block text-sm text-slate-300 mb-2">Email address</label>
                    <input
                      type="email"
                      name="email"
                      placeholder="Enter your email"
                      required
                      class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 placeholder-slate-500 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                    >
                  </div>

                  <div>
                    <label class="block text-sm text-slate-300 mb-2">Password</label>
                    <div class="relative">
                      <input
                        type="password"
                        name="password"
                        id="signupPassword"
                        placeholder="Create a password"
                        required
                        class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 pr-12 text-sm text-slate-100 placeholder-slate-500 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                      >
                      <button
                        type="button"
                        onclick="togglePassword('signupPassword', 'signupEye')"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white"
                      >
                        <i id="signupEye" class="fa-solid fa-eye"></i>
                      </button>
                    </div>
                  </div>

                  <div>
                    <label class="block text-sm text-slate-300 mb-2">Confirm password</label>
                    <div class="relative">
                      <input
                        type="password"
                        name="confirm_password"
                        id="signupConfirmPassword"
                        placeholder="Confirm your password"
                        required
                        class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 pr-12 text-sm text-slate-100 placeholder-slate-500 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                      >
                      <button
                        type="button"
                        onclick="togglePassword('signupConfirmPassword', 'signupConfirmEye')"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white"
                      >
                        <i id="signupConfirmEye" class="fa-solid fa-eye"></i>
                      </button>
                    </div>
                  </div>

                  <button
                    type="submit"
                    class="w-full rounded-2xl bg-sky-400 hover:bg-sky-300 text-slate-950 font-bold py-3.5 transition shadow-[0_0_25px_rgba(56,189,248,0.22)]"
                  >
                    Create my account
                  </button>
                </form>

                <div class="mt-6 rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                  <p class="text-xs uppercase tracking-[0.15em] text-slate-400">Getting started</p>
                  <ul class="mt-3 space-y-2 text-sm text-slate-300">
                    <li>• Create your developer profile</li>
                    <li>• Share your first lesson or question</li>
                    <li>• Connect with other learners in tech</li>
                  </ul>
                </div>
              </div>

              <div class="mt-6 text-center text-xs text-slate-500 leading-6">
                By continuing, you’re joining a student-centered learning space built for growth, curiosity, and collaboration.
              </div>
            </div>
          </section>
        </div>
      </div>
    </main>
  </div>

  <script>
    const signinTab = document.getElementById("signinTab");
    const signupTab = document.getElementById("signupTab");
    const signinPanel = document.getElementById("signinPanel");
    const signupPanel = document.getElementById("signupPanel");

    function togglePassword(inputId, iconId) {
      const input = document.getElementById(inputId);
      const icon = document.getElementById(iconId);

      if (!input || !icon) return;

      if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      }
    }

    function showSignin() {
      signinPanel.classList.remove("hidden");
      signupPanel.classList.add("hidden");

      signinTab.classList.add("bg-emerald-400", "text-slate-950");
      signinTab.classList.remove("text-slate-300");
      signupTab.classList.remove("bg-sky-400", "text-slate-950");
      signupTab.classList.add("text-slate-300");
    }

    function showSignup() {
      signupPanel.classList.remove("hidden");
      signinPanel.classList.add("hidden");

      signupTab.classList.add("bg-sky-400", "text-slate-950");
      signupTab.classList.remove("text-slate-300");
      signinTab.classList.remove("bg-emerald-400", "text-slate-950");
      signinTab.classList.add("text-slate-300");
    }

    signinTab?.addEventListener("click", showSignin);
    signupTab?.addEventListener("click", showSignup);
  </script>
</body>
</html>