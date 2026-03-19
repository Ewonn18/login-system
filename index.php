<?php
session_start();
require_once "csrf.php";

$message = "";
$messageType = "";
$panel = "signin";

if (isset($_GET["message"]) && isset($_GET["type"])) {
    $message = htmlspecialchars($_GET["message"]);
    $messageType = $_GET["type"] === "success" ? "success" : "error";
}

if (isset($_GET["panel"]) && $_GET["panel"] === "signup") {
    $panel = "signup";
}

$csrfToken = get_csrf_token();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TechTrail Community – Student Developer Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    />
  </head>

  <body class="min-h-screen bg-slate-950 flex items-center justify-center px-4 py-10">
    <?php if (!empty($message)): ?>
      <div class="fixed top-5 left-1/2 -translate-x-1/2 z-50 w-full max-w-md px-4">
        <div class="<?php echo $messageType === 'success' ? 'bg-green-500/90 border-green-300' : 'bg-red-500/90 border-red-300'; ?> text-white border rounded-xl px-4 py-3 shadow-lg backdrop-blur-md text-center">
          <?php echo $message; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="absolute top-4 left-1/2 -translate-x-1/2 md:left-8 md:translate-x-0 z-40 flex flex-col items-center md:items-start">
      <span class="text-xs uppercase tracking-[0.25em] text-slate-500">TechTrail Community</span>
      <span class="mt-1 text-[11px] md:text-xs text-slate-400 text-center md:text-left">
        Where student developers log in, grow, and share.
      </span>
    </div>

    <div
      id="container"
      class="relative w-full max-w-5xl min-h-[680px] bg-slate-900/80 backdrop-blur-md border border-slate-800 rounded-3xl shadow-[0_0_40px_rgba(15,23,42,0.9)] overflow-hidden"
    >
      <div
        id="signUpPanel"
        class="absolute top-0 left-0 w-full md:w-1/2 h-full flex items-center justify-center px-6 md:px-8 py-10 opacity-0 z-10 transition-all duration-700 ease-in-out pointer-events-none"
      >
        <form action="register.php" method="POST" class="w-full max-w-md flex flex-col items-center text-center text-slate-100">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
          <h1 class="text-2xl md:text-3xl font-semibold mb-2">Join TechTrail Community</h1>
          <p class="text-xs md:text-sm text-slate-400 mb-4 max-w-xs">
            Create your student developer profile and start sharing your IT journey.
          </p>

          <div class="flex gap-3 my-3 items-center">
            <div class="flex gap-2 text-slate-400 text-xs md:text-sm items-center">
              <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-700 bg-slate-900/80">
                <i class="fa-brands fa-github text-lg"></i>
              </span>
              <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-700 bg-slate-900/80">
                <i class="fa-brands fa-linkedin-in text-lg"></i>
              </span>
            </div>
            <span class="text-[11px] md:text-xs text-slate-500">
              Social sign-in coming soon.
            </span>
          </div>

          <span class="text-xs md:text-sm text-slate-400 mb-4">Use your email to create your TechTrail account.</span>

          <input
            type="text"
            name="name"
            placeholder="Full name"
            required
            class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 mb-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
          />

          <input
            type="email"
            name="email"
            placeholder="Student email"
            required
            class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 mb-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
          />

          <div class="relative w-full mb-4">
            <input
              type="password"
              name="password"
              id="signupPassword"
              placeholder="Password (min 8 characters)"
              required
              class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 pr-12 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
              oninput="checkPasswordStrength(this.value, 'signupStrengthText', 'signupStrengthBar')"
            />
            <button
              type="button"
              onclick="togglePassword('signupPassword', 'signupEye')"
              class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-white"
            >
              <i id="signupEye" class="fa-solid fa-eye"></i>
            </button>
          </div>

          <div class="w-full mb-4">
            <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
              <div id="signupStrengthBar" class="h-full w-0 transition-all duration-300"></div>
            </div>
            <p id="signupStrengthText" class="text-left text-xs text-slate-400 mt-2">Password strength: —</p>
          </div>

          <div class="relative w-full mb-5">
            <input
              type="password"
              name="confirm_password"
              id="signupConfirmPassword"
              placeholder="Confirm password"
              required
              class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 pr-12 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
            />
            <button
              type="button"
              onclick="togglePassword('signupConfirmPassword', 'signupConfirmEye')"
              class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-white"
            >
              <i id="signupConfirmEye" class="fa-solid fa-eye"></i>
            </button>
          </div>

          <button
            type="submit"
            class="mt-2 inline-flex items-center justify-center bg-sky-600/90 hover:bg-sky-500 text-white font-semibold tracking-wide px-8 py-3 rounded-xl shadow-md shadow-sky-500/30 text-sm md:text-base transition"
          >
            Create my TechTrail account
          </button>
        </form>
      </div>

      <div
        id="signInPanel"
        class="absolute top-0 left-0 w-full md:w-1/2 h-full flex items-center justify-center px-6 md:px-8 py-10 z-20 transition-all duration-700 ease-in-out"
      >
        <form action="login.php" method="POST" class="w-full max-w-md flex flex-col items-center text-center text-slate-100">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
          <h1 class="text-2xl md:text-3xl font-semibold mb-2">Sign in to TechTrail</h1>
          <p class="text-xs md:text-sm text-slate-400 mb-4 max-w-xs">
            Access your dashboard, profile, and IT community feed.
          </p>

          <div class="flex gap-3 my-3 items-center">
            <div class="flex gap-2 text-slate-400 text-xs md:text-sm items-center">
              <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-700 bg-slate-900/80">
                <i class="fa-brands fa-github text-lg"></i>
              </span>
              <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-700 bg-slate-900/80">
                <i class="fa-brands fa-linkedin-in text-lg"></i>
              </span>
            </div>
            <span class="text-[11px] md:text-xs text-slate-500">
              Social sign-in coming soon.
            </span>
          </div>

          <span class="text-xs md:text-sm text-slate-400 mb-4">Use your email and password to continue your journey.</span>

          <input
            type="email"
            name="email"
            placeholder="Student email"
            required
            class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 mb-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
          />

          <div class="relative w-full mb-4">
            <input
              type="password"
              name="password"
              id="signinPassword"
              placeholder="Password"
              required
              class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 pr-12 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
            />
            <button
              type="button"
              onclick="togglePassword('signinPassword', 'signinEye')"
              class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-white"
            >
              <i id="signinEye" class="fa-solid fa-eye"></i>
            </button>
          </div>

          <div class="w-full flex items-center justify-between mb-5 text-xs md:text-sm text-slate-100">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="remember" class="accent-sky-600 w-4 h-4" />
              <span>Remember me on this device</span>
            </label>

            <a href="forgot-password.php" class="hover:underline text-sky-400">Forgot your password?</a>
          </div>

          <button
            type="submit"
            class="inline-flex items-center justify-center bg-emerald-600/90 hover:bg-emerald-500 text-white font-semibold tracking-wide px-8 py-3 rounded-xl shadow-md shadow-emerald-500/30 text-sm md:text-base transition"
          >
            Sign in and continue
          </button>
        </form>
      </div>

      <div
        id="toggleWrapper"
        class="hidden md:block absolute top-0 left-1/2 w-1/2 h-full overflow-hidden rounded-l-[120px] z-30 transition-all duration-700 ease-in-out"
      >
        <div
          id="togglePanel"
          class="relative -left-full w-[200%] h-full bg-gradient-to-r from-sky-600 via-indigo-600 to-emerald-500 text-slate-50 transition-all duration-700 ease-in-out"
        >
          <div
            id="toggleLeft"
            class="absolute top-0 left-0 w-1/2 h-full flex flex-col items-center justify-center text-center px-8 -translate-x-[200%] transition-all duration-700 ease-in-out"
          >
            <h1 class="text-2xl md:text-3xl font-semibold mb-3">Welcome back to TechTrail</h1>
            <p class="text-xs md:text-sm mb-6 max-w-sm text-slate-100/80">
              Sign in to continue your learning path, update your profile, and connect with the community.
            </p>
            <button
              type="button"
              id="login"
              class="border border-slate-100/80 text-slate-50 px-8 py-3 rounded-xl text-xs md:text-sm font-semibold hover:bg-slate-50 hover:text-sky-700 transition"
            >
              Go to sign in
            </button>
          </div>

          <div
            id="toggleRight"
            class="absolute top-0 right-0 w-1/2 h-full flex flex-col items-center justify-center text-center px-8 transition-all duration-700 ease-in-out"
          >
            <h1 class="text-2xl md:text-3xl font-semibold mb-3">New to TechTrail?</h1>
            <p class="text-xs md:text-sm mb-6 max-w-sm text-slate-100/80">
              Create a free account as a student developer and start sharing your IT journey.
            </p>
            <button
              type="button"
              id="register"
              class="border border-slate-100/80 text-slate-50 px-8 py-3 rounded-xl text-xs md:text-sm font-semibold hover:bg-slate-50 hover:text-emerald-700 transition"
            >
              Go to sign up
            </button>
          </div>
        </div>
      </div>

      <div class="md:hidden absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-3 z-40">
        <button
          type="button"
          id="mobileLogin"
          class="bg-emerald-600/90 text-white px-5 py-2 rounded-lg text-xs font-semibold shadow-md shadow-emerald-500/30"
        >
          Sign in
        </button>
        <button
          type="button"
          id="mobileRegister"
          class="bg-slate-900 text-sky-300 border border-sky-600 px-5 py-2 rounded-lg text-xs font-semibold"
        >
          Create account
        </button>
      </div>
    </div>

    <script>
      const registerBtn = document.getElementById("register");
      const loginBtn = document.getElementById("login");
      const mobileRegisterBtn = document.getElementById("mobileRegister");
      const mobileLoginBtn = document.getElementById("mobileLogin");

      const signInPanel = document.getElementById("signInPanel");
      const signUpPanel = document.getElementById("signUpPanel");
      const toggleWrapper = document.getElementById("toggleWrapper");
      const togglePanel = document.getElementById("togglePanel");
      const toggleLeft = document.getElementById("toggleLeft");
      const toggleRight = document.getElementById("toggleRight");

      function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);

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

      function checkPasswordStrength(password, textId, barId) {
        const text = document.getElementById(textId);
        const bar = document.getElementById(barId);

        let score = 0;

        if (password.length >= 8) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;

        let label = "Very Weak";
        let width = "20%";
        let color = "bg-red-500";

        if (score === 2) {
          label = "Weak";
          width = "40%";
          color = "bg-orange-500";
        } else if (score === 3) {
          label = "Medium";
          width = "60%";
          color = "bg-yellow-500";
        } else if (score === 4) {
          label = "Strong";
          width = "80%";
          color = "bg-green-500";
        } else if (score === 5) {
          label = "Very Strong";
          width = "100%";
          color = "bg-emerald-500";
        }

        if (password.length === 0) {
          label = "—";
          width = "0%";
          color = "";
        }

        bar.className = `h-full transition-all duration-300 ${color}`;
        bar.style.width = width;
        text.textContent = `Password strength: ${label}`;
      }

      function resetDesktopState() {
        signInPanel.className =
          "absolute top-0 left-0 w-full md:w-1/2 h-full flex items-center justify-center px-8 py-10 z-20 transition-all duration-700 ease-in-out";
        signUpPanel.className =
          "absolute top-0 left-0 w-full md:w-1/2 h-full flex items-center justify-center px-8 py-10 opacity-0 z-10 transition-all duration-700 ease-in-out pointer-events-none";
        toggleWrapper.className =
          "hidden md:block absolute top-0 left-1/2 w-1/2 h-full overflow-hidden rounded-l-[120px] z-30 transition-all duration-700 ease-in-out";
        togglePanel.className =
          "relative -left-full w-[200%] h-full bg-gradient-to-r from-violet-500 to-fuchsia-600 text-white transition-all duration-700 ease-in-out";
        toggleLeft.className =
          "absolute top-0 left-0 w-1/2 h-full flex flex-col items-center justify-center text-center px-10 -translate-x-[200%] transition-all duration-700 ease-in-out";
        toggleRight.className =
          "absolute top-0 right-0 w-1/2 h-full flex flex-col items-center justify-center text-center px-10 transition-all duration-700 ease-in-out";
      }

      function showSignUp() {
        if (window.innerWidth >= 768) {
          resetDesktopState();

          signInPanel.classList.add("translate-x-full", "opacity-0", "pointer-events-none", "z-10");
          signInPanel.classList.remove("z-20");

          signUpPanel.classList.remove("opacity-0", "pointer-events-none", "z-10");
          signUpPanel.classList.add("translate-x-full", "opacity-100", "z-20");

          toggleWrapper.classList.add("-translate-x-full", "rounded-r-[120px]");
          toggleWrapper.classList.remove("rounded-l-[120px]");

          togglePanel.classList.add("translate-x-1/2");

          toggleLeft.classList.remove("-translate-x-[200%]");
          toggleRight.classList.add("translate-x-[200%]");
        } else {
          signInPanel.classList.add("opacity-0", "pointer-events-none");
          signInPanel.classList.remove("opacity-100", "z-20");

          signUpPanel.classList.remove("opacity-0", "pointer-events-none");
          signUpPanel.classList.add("opacity-100", "z-20");
        }
      }

      function showSignIn() {
        if (window.innerWidth >= 768) {
          resetDesktopState();
        } else {
          signUpPanel.classList.add("opacity-0", "pointer-events-none");
          signUpPanel.classList.remove("opacity-100", "z-20");

          signInPanel.classList.remove("opacity-0", "pointer-events-none");
          signInPanel.classList.add("opacity-100", "z-20");
        }
      }

      registerBtn?.addEventListener("click", showSignUp);
      loginBtn?.addEventListener("click", showSignIn);
      mobileRegisterBtn?.addEventListener("click", showSignUp);
      mobileLoginBtn?.addEventListener("click", showSignIn);

      window.addEventListener("resize", () => {
        if (window.innerWidth >= 768) {
          const activePanel = "<?php echo $panel; ?>";
          if (activePanel === "signup") {
            showSignUp();
          } else {
            showSignIn();
          }
        } else {
          signInPanel.classList.remove("translate-x-full");
          signUpPanel.classList.remove("translate-x-full");
        }
      });

      const activePanel = "<?php echo $panel; ?>";

      if (activePanel === "signup") {
        showSignUp();
      } else {
        showSignIn();
      }
    </script>
  </body>
</html>