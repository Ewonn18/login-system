<?php
session_start();

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

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Animated Login Page</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    />
  </head>

  <body class="min-h-screen bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center px-4 py-10">
    <?php if (!empty($message)): ?>
      <div class="fixed top-5 left-1/2 -translate-x-1/2 z-50 w-full max-w-md px-4">
        <div class="<?php echo $messageType === 'success' ? 'bg-green-500/90 border-green-300' : 'bg-red-500/90 border-red-300'; ?> text-white border rounded-xl px-4 py-3 shadow-lg backdrop-blur-md text-center">
          <?php echo $message; ?>
        </div>
      </div>
    <?php endif; ?>

    <div
      id="container"
      class="relative w-full max-w-5xl min-h-[700px] bg-white/10 backdrop-blur-md border border-white/20 rounded-3xl shadow-2xl overflow-hidden"
    >
      <!-- Sign Up -->
      <div
        id="signUpPanel"
        class="absolute top-0 left-0 w-full md:w-1/2 h-full flex items-center justify-center px-8 py-10 opacity-0 z-10 transition-all duration-700 ease-in-out pointer-events-none"
      >
        <form action="register.php" method="POST" class="w-full max-w-md flex flex-col items-center text-center text-white">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
          <h1 class="text-4xl font-bold mb-4">Create Account</h1>

          <div class="flex gap-4 my-5">
            <a href="#" class="w-14 h-14 rounded-xl border border-white/40 flex items-center justify-center text-xl hover:bg-white/10 transition">
              <i class="fa-brands fa-google-plus-g"></i>
            </a>
            <a href="#" class="w-14 h-14 rounded-xl border border-white/40 flex items-center justify-center text-xl hover:bg-white/10 transition">
              <i class="fa-brands fa-facebook-f"></i>
            </a>
            <a href="#" class="w-14 h-14 rounded-xl border border-white/40 flex items-center justify-center text-xl hover:bg-white/10 transition">
              <i class="fa-brands fa-github"></i>
            </a>
            <a href="#" class="w-14 h-14 rounded-xl border border-white/40 flex items-center justify-center text-xl hover:bg-white/10 transition">
              <i class="fa-brands fa-linkedin-in"></i>
            </a>
          </div>

          <span class="text-sm text-gray-200 mb-5">or use your email for registration</span>

          <input
            type="text"
            name="name"
            placeholder="Name"
            required
            class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-5 py-4 mb-4 outline-none border border-white/10"
          />

          <input
            type="email"
            name="email"
            placeholder="Email"
            required
            class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-5 py-4 mb-4 outline-none border border-white/10"
          />

          <div class="relative w-full mb-4">
            <input
              type="password"
              name="password"
              id="signupPassword"
              placeholder="Password"
              required
              class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-5 py-4 pr-14 outline-none border border-white/10"
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
            <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
              <div id="signupStrengthBar" class="h-full w-0 transition-all duration-300"></div>
            </div>
            <p id="signupStrengthText" class="text-left text-sm text-gray-300 mt-2">Password strength: —</p>
          </div>

          <div class="relative w-full mb-5">
            <input
              type="password"
              name="confirm_password"
              id="signupConfirmPassword"
              placeholder="Confirm Password"
              required
              class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-5 py-4 pr-14 outline-none border border-white/10"
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
            class="mt-2 bg-fuchsia-600 hover:bg-fuchsia-700 text-white font-bold uppercase tracking-wide px-12 py-4 rounded-xl shadow-lg transition"
          >
            Sign Up
          </button>
        </form>
      </div>

      <!-- Sign In -->
      <div
        id="signInPanel"
        class="absolute top-0 left-0 w-full md:w-1/2 h-full flex items-center justify-center px-8 py-10 z-20 transition-all duration-700 ease-in-out"
      >
        <form action="login.php" method="POST" class="w-full max-w-md flex flex-col items-center text-center text-white">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
          <h1 class="text-4xl font-bold mb-4">Sign In</h1>

          <div class="flex gap-4 my-5">
            <a href="#" class="w-14 h-14 rounded-xl border border-white/40 flex items-center justify-center text-xl hover:bg-white/10 transition">
              <i class="fa-brands fa-google-plus-g"></i>
            </a>
            <a href="#" class="w-14 h-14 rounded-xl border border-white/40 flex items-center justify-center text-xl hover:bg-white/10 transition">
              <i class="fa-brands fa-facebook-f"></i>
            </a>
            <a href="#" class="w-14 h-14 rounded-xl border border-white/40 flex items-center justify-center text-xl hover:bg-white/10 transition">
              <i class="fa-brands fa-github"></i>
            </a>
            <a href="#" class="w-14 h-14 rounded-xl border border-white/40 flex items-center justify-center text-xl hover:bg-white/10 transition">
              <i class="fa-brands fa-linkedin-in"></i>
            </a>
          </div>

          <span class="text-sm text-gray-200 mb-5">or use your email and password</span>

          <input
            type="email"
            name="email"
            placeholder="Email"
            required
            class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-5 py-4 mb-4 outline-none border border-white/10"
          />

          <div class="relative w-full mb-4">
            <input
              type="password"
              name="password"
              id="signinPassword"
              placeholder="Password"
              required
              class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-5 py-4 pr-14 outline-none border border-white/10"
            />
            <button
              type="button"
              onclick="togglePassword('signinPassword', 'signinEye')"
              class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-white"
            >
              <i id="signinEye" class="fa-solid fa-eye"></i>
            </button>
          </div>

          <div class="w-full flex items-center justify-between mb-5 text-sm text-white">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="remember" class="accent-fuchsia-600 w-4 h-4" />
              <span>Remember Me</span>
            </label>

            <a href="forgot-password.php" class="hover:underline">Forgot Your Password?</a>
          </div>

          <button
            type="submit"
            class="bg-fuchsia-600 hover:bg-fuchsia-700 text-white font-bold uppercase tracking-wide px-12 py-4 rounded-xl shadow-lg transition"
          >
            Sign In
          </button>
        </form>
      </div>

      <!-- Desktop Toggle -->
      <div
        id="toggleWrapper"
        class="hidden md:block absolute top-0 left-1/2 w-1/2 h-full overflow-hidden rounded-l-[120px] z-30 transition-all duration-700 ease-in-out"
      >
        <div
          id="togglePanel"
          class="relative -left-full w-[200%] h-full bg-gradient-to-r from-violet-500 to-fuchsia-600 text-white transition-all duration-700 ease-in-out"
        >
          <!-- Left -->
          <div
            id="toggleLeft"
            class="absolute top-0 left-0 w-1/2 h-full flex flex-col items-center justify-center text-center px-10 -translate-x-[200%] transition-all duration-700 ease-in-out"
          >
            <h1 class="text-5xl font-bold mb-4">Welcome Back!</h1>
            <p class="text-lg mb-8 max-w-sm">
              Enter your personal details to use all of site features
            </p>
            <button
              type="button"
              id="login"
              class="border border-white text-white px-10 py-4 rounded-xl uppercase font-bold hover:bg-white hover:text-fuchsia-600 transition"
            >
              Sign In
            </button>
          </div>

          <!-- Right -->
          <div
            id="toggleRight"
            class="absolute top-0 right-0 w-1/2 h-full flex flex-col items-center justify-center text-center px-10 transition-all duration-700 ease-in-out"
          >
            <h1 class="text-5xl font-bold mb-4">Hello, Friend!</h1>
            <p class="text-lg mb-8 max-w-sm">
              Register with your personal details to use all of site features
            </p>
            <button
              type="button"
              id="register"
              class="border border-white text-white px-10 py-4 rounded-xl uppercase font-bold hover:bg-white hover:text-fuchsia-600 transition"
            >
              Sign Up
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Buttons -->
      <div class="md:hidden absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-3 z-40">
        <button
          type="button"
          id="mobileLogin"
          class="bg-fuchsia-600 text-white px-5 py-2 rounded-lg text-sm font-semibold"
        >
          Sign In
        </button>
        <button
          type="button"
          id="mobileRegister"
          class="bg-white text-fuchsia-600 border border-fuchsia-600 px-5 py-2 rounded-lg text-sm font-semibold"
        >
          Sign Up
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