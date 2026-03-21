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
  <title>TechTrail Community – Student Developer Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    .auth-shell {
      position: relative;
      width: 100%;
      max-width: 1200px;
      min-height: 720px;
      overflow: hidden;
      border-radius: 32px;
      border: 1px solid rgba(148, 163, 184, 0.18);
      background:
        radial-gradient(circle at top left, rgba(56,189,248,.12), transparent 28%),
        radial-gradient(circle at bottom right, rgba(16,185,129,.10), transparent 26%),
        linear-gradient(135deg, #020617 0%, #0f172a 55%, #0b1120 100%);
      box-shadow: 0 30px 80px rgba(2, 6, 23, 0.65), 0 0 80px rgba(14, 165, 233, 0.08);
    }

    .desktop-auth {
      position: relative;
      min-height: 720px;
    }

    .slider-viewport {
      position: absolute;
      inset: 0;
      overflow: hidden;
    }

    .slider-track {
      width: 200%;
      height: 100%;
      display: flex;
      transition: transform 0.7s ease;
      transform: translateX(0);
    }

    .slider-track.signup-active {
      transform: translateX(-50%);
    }

    .slide {
      width: 50%;
      height: 100%;
      display: grid;
      grid-template-columns: 1fr 1fr;
      align-items: stretch;
    }

    .hero-side {
      padding: 56px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .hero-content {
      max-width: 430px;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 14px;
      border-radius: 999px;
      background: rgba(14,165,233,.10);
      border: 1px solid rgba(125,211,252,.18);
      color: #bae6fd;
      font-size: 12px;
      letter-spacing: .08em;
      text-transform: uppercase;
    }

    .form-side {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 28px;
    }

    .form-card {
      width: 100%;
      max-width: 470px;
      border-radius: 28px;
      background: linear-gradient(180deg, rgba(15,23,42,.92), rgba(15,23,42,.84));
      border: 1px solid rgba(125,211,252,.16);
      box-shadow: 0 20px 50px rgba(2,6,23,.45), 0 0 30px rgba(14,165,233,.08);
      padding: 40px 34px;
    }

    .social-btn {
      width: 44px;
      height: 44px;
      border-radius: 14px;
      border: 1px solid rgba(148,163,184,.35);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: white;
      background: rgba(15,23,42,.55);
      transition: .2s ease;
    }

    .social-btn:hover {
      border-color: rgba(125,211,252,.6);
      box-shadow: 0 0 0 4px rgba(56,189,248,.10);
    }

    .ghost-btn {
      border: 1px solid rgba(255,255,255,.18);
      background: rgba(255,255,255,.04);
      transition: .2s ease;
    }

    .ghost-btn:hover {
      background: rgba(255,255,255,.09);
      border-color: rgba(255,255,255,.28);
      transform: translateY(-1px);
    }

    @media (max-width: 767px) {
      .desktop-auth { display: none; }
    }

    @media (min-width: 768px) {
      .mobile-auth { display: none; }
    }
  </style>
</head>
<body class="min-h-screen bg-slate-950 flex items-center justify-center px-4 py-10 overflow-x-hidden text-slate-100">

  <?php if (!empty($message)): ?>
    <div class="fixed top-5 left-1/2 -translate-x-1/2 z-50 w-full max-w-md px-4">
      <div class="<?php echo $messageType === 'success' ? 'bg-emerald-500/90 border-emerald-300' : 'bg-rose-500/90 border-rose-300'; ?> text-white border rounded-xl px-4 py-3 shadow-lg text-center text-sm">
        <?php echo $message; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="auth-shell">
    <div class="desktop-auth">
      <div class="slider-viewport">
        <div id="sliderTrack" class="slider-track <?php echo $panel === 'signup' ? 'signup-active' : ''; ?>">

          <section class="slide">
            <div class="hero-side">
              <div class="hero-content">
                <span class="hero-badge">
                  <i class="fa-solid fa-code"></i>
                  Student Developer Space
                </span>
                <h2 class="mt-6 text-5xl font-black tracking-tight leading-tight text-white">
                  Build your tech identity.
                </h2>
                <p class="mt-5 text-base leading-8 text-slate-300">
                  Create a profile, connect with fellow learners, and share your progress as you grow in web, code, cloud, and design.
                </p>
                <div class="mt-8 flex gap-3">
                  <button type="button" id="toSignupFromSignin" class="ghost-btn rounded-2xl px-6 py-3 font-semibold text-white">
                    Create account
                  </button>
                </div>
              </div>
            </div>

            <div class="form-side">
              <form action="login.php" method="POST" class="form-card">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="text-center">
                  <p class="text-xs uppercase tracking-[0.32em] text-sky-300">TechTrail Community</p>
                  <h1 class="mt-4 text-4xl font-black tracking-tight text-white">Sign in to TechTrail</h1>
                  <p class="mt-3 text-sm leading-6 text-slate-300">Access your dashboard, profile, and IT community feed.</p>
                </div>

                <div class="mt-6 flex items-center justify-center gap-3">
                  <button type="button" class="social-btn"><i class="fa-brands fa-github"></i></button>
                  <button type="button" class="social-btn"><i class="fa-brands fa-linkedin-in"></i></button>
                  <span class="text-sm text-slate-300">Social sign-in coming soon.</span>
                </div>

                <div class="mt-7 space-y-4">
                  <input
                    type="email"
                    name="email"
                    placeholder="Student email"
                    required
                    class="w-full bg-slate-900/70 text-slate-100 placeholder-slate-400 rounded-2xl px-5 py-4 outline-none border border-sky-400/45 focus:border-sky-300 focus:ring-2 focus:ring-sky-400/20 text-sm"
                  >

                  <div class="relative">
                    <input
                      type="password"
                      name="password"
                      id="signinPassword"
                      placeholder="Password"
                      required
                      class="w-full bg-slate-900/70 text-slate-100 placeholder-slate-400 rounded-2xl px-5 py-4 pr-14 outline-none border border-sky-400/45 focus:border-sky-300 focus:ring-2 focus:ring-sky-400/20 text-sm"
                    >
                    <button
                      type="button"
                      onclick="togglePassword('signinPassword', 'signinEye')"
                      class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-white"
                    >
                      <i id="signinEye" class="fa-solid fa-eye"></i>
                    </button>
                  </div>

                  <div class="flex items-center justify-between gap-4 text-sm">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-200">
                      <input type="checkbox" name="remember" class="accent-emerald-500 w-4 h-4">
                      <span>Remember me</span>
                    </label>

                    <a href="forgot-password.php" class="text-sky-300 hover:text-sky-200 hover:underline">
                      Forgot your password?
                    </a>
                  </div>

                  <button
                    type="submit"
                    class="w-full rounded-2xl bg-emerald-400 hover:bg-emerald-300 text-slate-950 font-extrabold py-4 transition shadow-[0_0_28px_rgba(52,211,153,0.28)]"
                  >
                    Sign in and continue
                  </button>
                </div>
              </form>
            </div>
          </section>

          <section class="slide">
            <div class="form-side">
              <form action="register.php" method="POST" class="form-card">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="text-center">
                  <p class="text-xs uppercase tracking-[0.32em] text-sky-300">TechTrail Community</p>
                  <h1 class="mt-4 text-4xl font-black tracking-tight text-white">Create your account</h1>
                  <p class="mt-3 text-sm leading-6 text-slate-300">Join as a student developer and start sharing your IT journey.</p>
                </div>

                <div class="mt-7 space-y-4">
                  <input
                    type="text"
                    name="name"
                    placeholder="Full name"
                    required
                    class="w-full bg-slate-900/70 text-slate-100 placeholder-slate-400 rounded-2xl px-5 py-4 outline-none border border-sky-400/45 focus:border-sky-300 focus:ring-2 focus:ring-sky-400/20 text-sm"
                  >

                  <input
                    type="email"
                    name="email"
                    placeholder="Student email"
                    required
                    class="w-full bg-slate-900/70 text-slate-100 placeholder-slate-400 rounded-2xl px-5 py-4 outline-none border border-sky-400/45 focus:border-sky-300 focus:ring-2 focus:ring-sky-400/20 text-sm"
                  >

                  <div class="relative">
                    <input
                      type="password"
                      name="password"
                      id="signupPassword"
                      placeholder="Password"
                      required
                      class="w-full bg-slate-900/70 text-slate-100 placeholder-slate-400 rounded-2xl px-5 py-4 pr-14 outline-none border border-sky-400/45 focus:border-sky-300 focus:ring-2 focus:ring-sky-400/20 text-sm"
                    >
                    <button
                      type="button"
                      onclick="togglePassword('signupPassword', 'signupEye')"
                      class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-white"
                    >
                      <i id="signupEye" class="fa-solid fa-eye"></i>
                    </button>
                  </div>

                  <div class="relative">
                    <input
                      type="password"
                      name="confirm_password"
                      id="signupConfirmPassword"
                      placeholder="Confirm password"
                      required
                      class="w-full bg-slate-900/70 text-slate-100 placeholder-slate-400 rounded-2xl px-5 py-4 pr-14 outline-none border border-sky-400/45 focus:border-sky-300 focus:ring-2 focus:ring-sky-400/20 text-sm"
                    >
                    <button
                      type="button"
                      onclick="togglePassword('signupConfirmPassword', 'signupConfirmEye')"
                      class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-white"
                    >
                      <i id="signupConfirmEye" class="fa-solid fa-eye"></i>
                    </button>
                  </div>

                  <button
                    type="submit"
                    class="w-full rounded-2xl bg-sky-400 hover:bg-sky-300 text-slate-950 font-extrabold py-4 transition shadow-[0_0_28px_rgba(56,189,248,0.28)]"
                  >
                    Create my TechTrail account
                  </button>
                </div>
              </form>
            </div>

            <div class="hero-side">
              <div class="hero-content text-right">
                <div class="flex justify-end">
                  <span class="hero-badge">
                    <i class="fa-solid fa-bolt"></i>
                    Continue your journey
                  </span>
                </div>
                <h2 class="mt-6 text-5xl font-black tracking-tight leading-tight text-white">
                  Come back and keep building.
                </h2>
                <p class="mt-5 text-base leading-8 text-slate-300">
                  Sign in to manage your profile, explore the community feed, and continue building your developer journey with TechTrail.
                </p>
                <div class="mt-8 flex justify-end gap-3">
                  <button type="button" id="toSigninFromSignup" class="ghost-btn rounded-2xl px-6 py-3 font-semibold text-white">
                    Back to sign in
                  </button>
                </div>
              </div>
            </div>
          </section>

        </div>
      </div>
    </div>

    <div class="mobile-auth p-6 sm:p-8">
      <div class="max-w-md mx-auto">
        <div class="text-center mb-8">
          <p class="text-sky-300 text-xs uppercase tracking-[0.25em]">TechTrail Community</p>
          <h1 class="mt-3 text-3xl font-black text-white"><?php echo $panel === "signup" ? "Create account" : "Sign in"; ?></h1>
        </div>

        <div class="flex justify-center gap-2 mb-6">
          <button type="button" id="mobileSigninTab" class="px-4 py-2 rounded-xl text-sm font-semibold <?php echo $panel === 'signin' ? 'bg-emerald-400 text-slate-950' : 'bg-slate-800 text-white'; ?>">
            Sign in
          </button>
          <button type="button" id="mobileSignupTab" class="px-4 py-2 rounded-xl text-sm font-semibold <?php echo $panel === 'signup' ? 'bg-sky-400 text-slate-950' : 'bg-slate-800 text-white'; ?>">
            Sign up
          </button>
        </div>

        <div id="mobileSigninForm" class="<?php echo $panel === 'signup' ? 'hidden' : ''; ?>">
          <form action="login.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <input type="email" name="email" placeholder="Student email" required class="w-full bg-slate-900/70 text-slate-100 placeholder-slate-400 rounded-2xl px-5 py-4 outline-none border border-sky-400/45 text-sm">

            <div class="relative">
              <input type="password" name="password" id="mobileSigninPassword" placeholder="Password" required class="w-full bg-slate-900/70 text-slate-100 placeholder-slate-400 rounded-2xl px-5 py-4 pr-14 outline-none border border-sky-400/45 text-sm">
              <button type="button" onclick="togglePassword('mobileSigninPassword', 'mobileSigninEye')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-white">
                <i id="mobileSigninEye" class="fa-solid fa-eye"></i>
              </button>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-200">
              <input type="checkbox" name="remember" class="accent-emerald-500 w-4 h-4">
              <span>Remember me</span>
            </label>

            <a href="forgot-password.php" class="block text-sm text-sky-300 hover:underline">Forgot your password?</a>

            <button type="submit" class="w-full rounded-2xl bg-emerald-400 hover:bg-emerald-300 text-slate-950 font-bold py-4 transition">
              Sign in and continue
            </button>
          </form>
        </div>

        <div id="mobileSignupForm" class="<?php echo $panel === 'signin' ? 'hidden' : ''; ?>">
          <form action="register.php" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <input type="text" name="name" placeholder="Full name" required class="w-full bg-slate-900/70 text-slate-100 placeholder-slate-400 rounded-2xl px-5 py-4 outline-none border border-sky-400/45 text-sm">
            <input type="email" name="email" placeholder="Student email" required class="w-full bg-slate-900/70 text-slate-100 placeholder-slate-400 rounded-2xl px-5 py-4 outline-none border border-sky-400/45 text-sm">

            <div class="relative">
              <input type="password" name="password" id="mobileSignupPassword" placeholder="Password" required class="w-full bg-slate-900/70 text-slate-100 placeholder-slate-400 rounded-2xl px-5 py-4 pr-14 outline-none border border-sky-400/45 text-sm">
              <button type="button" onclick="togglePassword('mobileSignupPassword', 'mobileSignupEye')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-white">
                <i id="mobileSignupEye" class="fa-solid fa-eye"></i>
              </button>
            </div>

            <div class="relative">
              <input type="password" name="confirm_password" id="mobileSignupConfirmPassword" placeholder="Confirm password" required class="w-full bg-slate-900/70 text-slate-100 placeholder-slate-400 rounded-2xl px-5 py-4 pr-14 outline-none border border-sky-400/45 text-sm">
              <button type="button" onclick="togglePassword('mobileSignupConfirmPassword', 'mobileSignupConfirmEye')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-white">
                <i id="mobileSignupConfirmEye" class="fa-solid fa-eye"></i>
              </button>
            </div>

            <button type="submit" class="w-full rounded-2xl bg-sky-400 hover:bg-sky-300 text-slate-950 font-bold py-4 transition">
              Create my TechTrail account
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    const sliderTrack = document.getElementById("sliderTrack");
    const toSignupFromSignin = document.getElementById("toSignupFromSignin");
    const toSigninFromSignup = document.getElementById("toSigninFromSignup");

    const mobileSigninTab = document.getElementById("mobileSigninTab");
    const mobileSignupTab = document.getElementById("mobileSignupTab");
    const mobileSigninForm = document.getElementById("mobileSigninForm");
    const mobileSignupForm = document.getElementById("mobileSignupForm");

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

    function showSignup() {
      sliderTrack.classList.add("signup-active");
    }

    function showSignin() {
      sliderTrack.classList.remove("signup-active");
    }

    toSignupFromSignin?.addEventListener("click", showSignup);
    toSigninFromSignup?.addEventListener("click", showSignin);

    mobileSigninTab?.addEventListener("click", function () {
      mobileSigninForm.classList.remove("hidden");
      mobileSignupForm.classList.add("hidden");

      mobileSigninTab.classList.remove("bg-slate-800", "text-white");
      mobileSigninTab.classList.add("bg-emerald-400", "text-slate-950");

      mobileSignupTab.classList.remove("bg-sky-400", "text-slate-950");
      mobileSignupTab.classList.add("bg-slate-800", "text-white");
    });

    mobileSignupTab?.addEventListener("click", function () {
      mobileSignupForm.classList.remove("hidden");
      mobileSigninForm.classList.add("hidden");

      mobileSignupTab.classList.remove("bg-slate-800", "text-white");
      mobileSignupTab.classList.add("bg-sky-400", "text-slate-950");

      mobileSigninTab.classList.remove("bg-emerald-400", "text-slate-950");
      mobileSigninTab.classList.add("bg-slate-800", "text-white");
    });
  </script>
</body>
</html>