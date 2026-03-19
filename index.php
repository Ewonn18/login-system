<?php
require_once "session.php";
require_once "csrf.php";

$message = "";
$messageType = "";
$panel = "signin";
$verifyLink = $_GET["verify_link"] ?? "";

if (isset($_GET["message"]) && isset($_GET["type"])) {
    $message = htmlspecialchars($_GET["message"]);
    $messageType = $_GET["type"] === "success" ? "success" : "error";
}

if (isset($_GET["panel"]) && $_GET["panel"] === "signup") {
    $panel = "signup";
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
</head>
<body class="min-h-screen bg-slate-950 flex items-center justify-center px-4 py-10">
  <?php if (!empty($message)): ?>
    <div class="fixed top-5 left-1/2 -translate-x-1/2 z-50 w-full max-w-md px-4">
      <div class="<?php echo $messageType === 'success' ? 'bg-green-500/90 border-green-300' : 'bg-red-500/90 border-red-300'; ?> text-white border rounded-xl px-4 py-3 shadow-lg backdrop-blur-md text-center">
        <?php echo $message; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($verifyLink)): ?>
    <div class="fixed top-24 left-1/2 -translate-x-1/2 z-50 w-full max-w-2xl px-4">
      <div class="bg-sky-500/15 border border-sky-400/50 text-sky-100 rounded-xl px-4 py-3 shadow-lg break-all text-sm">
        <p class="font-semibold mb-1">Local demo verification link:</p>
        <a class="underline" href="<?php echo htmlspecialchars($verifyLink); ?>">
          <?php echo htmlspecialchars($verifyLink); ?>
        </a>
      </div>
    </div>
  <?php endif; ?>

  <div class="relative w-full max-w-5xl min-h-[680px] bg-slate-900/80 backdrop-blur-md border border-slate-800 rounded-3xl shadow-[0_0_40px_rgba(15,23,42,0.9)] overflow-hidden">
    <div id="signUpPanel" class="absolute top-0 left-0 w-full md:w-1/2 h-full flex items-center justify-center px-6 md:px-8 py-10 opacity-0 z-10 transition-all duration-700 ease-in-out pointer-events-none">
      <form action="register.php" method="POST" class="w-full max-w-md flex flex-col items-center text-center text-slate-100">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <h1 class="text-2xl md:text-3xl font-semibold mb-2">Join TechTrail Community</h1>
        <p class="text-xs md:text-sm text-slate-400 mb-4 max-w-xs">Create your student developer profile and start sharing your IT journey.</p>

        <input type="text" name="name" placeholder="Full name" required class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 mb-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm">
        <input type="email" name="email" placeholder="Student email" required class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 mb-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm">

        <div class="relative w-full mb-4">
          <input type="password" name="password" id="signupPassword" placeholder="Password (min 8 characters)" required class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 pr-12 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm">
          <button type="button" onclick="togglePassword('signupPassword', 'signupEye')" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-white">
            <i id="signupEye" class="fa-solid fa-eye"></i>
          </button>
        </div>

        <div class="relative w-full mb-5">
          <input type="password" name="confirm_password" id="signupConfirmPassword" placeholder="Confirm password" required class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 pr-12 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm">
          <button type="button" onclick="togglePassword('signupConfirmPassword', 'signupConfirmEye')" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-white">
            <i id="signupConfirmEye" class="fa-solid fa-eye"></i>
          </button>
        </div>

        <button type="submit" class="mt-2 inline-flex items-center justify-center bg-sky-600/90 hover:bg-sky-500 text-white font-semibold tracking-wide px-8 py-3 rounded-xl shadow-md shadow-sky-500/30 text-sm md:text-base transition">
          Create my TechTrail account
        </button>
      </form>
    </div>

    <div id="signInPanel" class="absolute top-0 left-0 w-full md:w-1/2 h-full flex items-center justify-center px-6 md:px-8 py-10 z-20 transition-all duration-700 ease-in-out">
      <form action="login.php" method="POST" class="w-full max-w-md flex flex-col items-center text-center text-slate-100">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <h1 class="text-2xl md:text-3xl font-semibold mb-2">Sign in to TechTrail</h1>
        <p class="text-xs md:text-sm text-slate-400 mb-4 max-w-xs">Access your dashboard, profile, and IT community feed.</p>

        <input type="email" name="email" placeholder="Student email" required class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 mb-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm">

        <div class="relative w-full mb-4">
          <input type="password" name="password" id="signinPassword" placeholder="Password" required class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 pr-12 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm">
          <button type="button" onclick="togglePassword('signinPassword', 'signinEye')" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-white">
            <i id="signinEye" class="fa-solid fa-eye"></i>
          </button>
        </div>

        <div class="w-full flex items-center justify-between mb-5 text-xs md:text-sm text-slate-100">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="remember" class="accent-sky-600 w-4 h-4">
            <span>Remember me on this device</span>
          </label>

          <a href="forgot-password.php" class="hover:underline text-sky-400">Forgot your password?</a>
        </div>

        <button type="submit" class="inline-flex items-center justify-center bg-emerald-600/90 hover:bg-emerald-500 text-white font-semibold tracking-wide px-8 py-3 rounded-xl shadow-md shadow-emerald-500/30 text-sm md:text-base transition">
          Sign in and continue
        </button>
      </form>
    </div>
  </div>

  <script>
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
  </script>
</body>
</html>