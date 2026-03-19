<?php
session_start();
include "db.php";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];
$email = isset($_GET["email"]) ? trim($_GET["email"]) : "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedToken = $_POST["csrf_token"] ?? "";

    if (empty($postedToken) || !hash_equals($_SESSION["csrf_token"], $postedToken)) {
        $emailRedirect = trim($_POST["email"] ?? "");
        header("Location: reset-password.php?email=" . urlencode($emailRedirect) . "&type=error&message=" . urlencode("Invalid request. Please refresh the page and try again."));
        exit();
    }

    $email = trim($_POST["email"] ?? "");
    $newPassword = trim($_POST["new_password"] ?? "");
    $confirmPassword = trim($_POST["confirm_password"] ?? "");

    if (empty($email) || empty($newPassword) || empty($confirmPassword)) {
        header("Location: reset-password.php?email=" . urlencode($email) . "&type=error&message=" . urlencode("All fields are required."));
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        header("Location: reset-password.php?email=" . urlencode($email) . "&type=error&message=" . urlencode("Passwords do not match."));
        exit();
    }

    if (
        strlen($newPassword) < 8 ||
        !preg_match('/[A-Z]/', $newPassword) ||
        !preg_match('/[a-z]/', $newPassword) ||
        !preg_match('/[0-9]/', $newPassword) ||
        !preg_match('/[^A-Za-z0-9]/', $newPassword)
    ) {
        header("Location: reset-password.php?email=" . urlencode($email) . "&type=error&message=" . urlencode("Password must be at least 8 characters and include uppercase, lowercase, number, and symbol."));
        exit();
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $sql = "UPDATE users SET password = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        header("Location: reset-password.php?email=" . urlencode($email) . "&type=error&message=" . urlencode("Something went wrong."));
        exit();
    }

    $stmt->bind_param("ss", $hashedPassword, $email);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: index.php?panel=signin&type=success&message=" . urlencode("Password updated successfully. You can now sign in."));
        exit();
    } else {
        $stmt->close();
        $conn->close();
        header("Location: reset-password.php?email=" . urlencode($email) . "&type=error&message=" . urlencode("Failed to reset password."));
        exit();
    }
}

if (empty($email)) {
    header("Location: forgot-password.php?type=error&message=" . urlencode("Invalid password reset request."));
    exit();
}

$message = "";
$messageType = "";

if (isset($_GET["message"]) && isset($_GET["type"])) {
    $message = htmlspecialchars($_GET["message"]);
    $messageType = $_GET["type"] === "success" ? "success" : "error";
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechTrail Community - Reset Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
  >
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-md bg-slate-900/80 border border-slate-800 rounded-3xl shadow-[0_0_40px_rgba(15,23,42,0.8)] overflow-hidden">
    <header class="bg-slate-900 border-b border-slate-800 px-6 py-5">
      <p class="text-xs uppercase tracking-[0.25em] text-slate-400">TechTrail Community</p>
      <h1 class="mt-2 text-2xl font-semibold text-slate-50">Reset Password</h1>
      <p class="mt-1 text-sm text-slate-400">Set a strong new password for your account.</p>
    </header>

    <main class="p-6">
      <?php if (!empty($message)): ?>
        <div class="<?php echo $messageType === 'success' ? 'bg-emerald-500/10 border-emerald-500/60 text-emerald-200' : 'bg-rose-500/10 border-rose-500/60 text-rose-200'; ?> border rounded-2xl px-4 py-3 text-sm mb-5">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="reset-password.php" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

        <div class="relative">
          <label class="block text-sm text-slate-300 mb-2">New Password</label>
          <input
            type="password"
            name="new_password"
            id="newPassword"
            placeholder="New Password"
            required
            class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 pr-12 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
            oninput="checkPasswordStrength(this.value, 'resetStrengthText', 'resetStrengthBar')"
          >
          <button
            type="button"
            onclick="togglePassword('newPassword', 'newEye')"
            class="absolute right-4 top-[42px] text-slate-400 hover:text-white"
          >
            <i id="newEye" class="fa-solid fa-eye"></i>
          </button>
        </div>

        <div>
          <div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden">
            <div id="resetStrengthBar" class="h-full w-0 transition-all duration-300"></div>
          </div>
          <p id="resetStrengthText" class="text-left text-sm text-slate-400 mt-2">Password strength: —</p>
        </div>

        <div class="relative">
          <label class="block text-sm text-slate-300 mb-2">Confirm Password</label>
          <input
            type="password"
            name="confirm_password"
            id="confirmPassword"
            placeholder="Confirm Password"
            required
            class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 pr-12 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
          >
          <button
            type="button"
            onclick="togglePassword('confirmPassword', 'confirmEye')"
            class="absolute right-4 top-[42px] text-slate-400 hover:text-white"
          >
            <i id="confirmEye" class="fa-solid fa-eye"></i>
          </button>
        </div>

        <button
          type="submit"
          class="w-full bg-sky-600/90 hover:bg-sky-500 text-white font-semibold py-3 rounded-xl transition shadow-md shadow-sky-500/30"
        >
          Reset Password
        </button>
      </form>

      <div class="text-center mt-5">
        <a href="index.php" class="text-sky-400 hover:underline text-sm">Back to Login</a>
      </div>
    </main>
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
  </script>
</body>
</html>