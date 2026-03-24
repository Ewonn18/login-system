<?php
require_once "session.php";
require_once "csrf.php";

$csrfToken = get_csrf_token();
$selector = isset($_GET["selector"]) ? trim($_GET["selector"]) : "";
$validator = isset($_GET["validator"]) ? trim($_GET["validator"]) : "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedToken = $_POST["csrf_token"] ?? "";

    if (!is_valid_csrf_token($postedToken)) {
        header("Location: forgot-password.php?type=error&message=" . urlencode("Invalid request. Please refresh the page and try again."));
        exit();
    }

    $selector = trim($_POST["selector"] ?? "");
    $validator = trim($_POST["validator"] ?? "");
    $newPassword = trim($_POST["new_password"] ?? "");
    $confirmPassword = trim($_POST["confirm_password"] ?? "");

    if (empty($selector) || empty($validator) || empty($newPassword) || empty($confirmPassword)) {
        header("Location: forgot-password.php?type=error&message=" . urlencode("Invalid password reset request."));
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        header("Location: reset-password.php?selector=" . urlencode($selector) . "&validator=" . urlencode($validator) . "&type=error&message=" . urlencode("Passwords do not match."));
        exit();
    }

    if (
        strlen($newPassword) < 8 ||
        !preg_match('/[A-Z]/', $newPassword) ||
        !preg_match('/[a-z]/', $newPassword) ||
        !preg_match('/[0-9]/', $newPassword) ||
        !preg_match('/[^A-Za-z0-9]/', $newPassword)
    ) {
        header("Location: reset-password.php?selector=" . urlencode($selector) . "&validator=" . urlencode($validator) . "&type=error&message=" . urlencode("Password must be at least 8 characters and include uppercase, lowercase, number, and symbol."));
        exit();
    }

    $sql = "SELECT id, user_id, token_hash, expires_at, used_at
            FROM password_resets
            WHERE selector = ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        header("Location: forgot-password.php?type=error&message=" . urlencode("Something went wrong."));
        exit();
    }

    $stmt->bind_param("s", $selector);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows !== 1) {
        $stmt->close();
        $conn->close();
        header("Location: forgot-password.php?type=error&message=" . urlencode("Invalid or expired reset link."));
        exit();
    }

    $resetRow = $result->fetch_assoc();
    $stmt->close();

    $isExpired = strtotime($resetRow["expires_at"]) < time();
    $isUsed = !empty($resetRow["used_at"]);
    $isValidToken = hash_equals($resetRow["token_hash"], hash("sha256", $validator));

    if ($isExpired || $isUsed || !$isValidToken) {
        $conn->close();
        header("Location: forgot-password.php?type=error&message=" . urlencode("Invalid or expired reset link."));
        exit();
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $updateUser = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    if (!$updateUser) {
        $conn->close();
        header("Location: forgot-password.php?type=error&message=" . urlencode("Something went wrong."));
        exit();
    }

    $userId = (int)$resetRow["user_id"];
    $updateUser->bind_param("si", $hashedPassword, $userId);
    $updateUser->execute();
    $updateUser->close();

    $markUsed = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = ?");
    if ($markUsed) {
        $resetId = (int)$resetRow["id"];
        $markUsed->bind_param("i", $resetId);
        $markUsed->execute();
        $markUsed->close();
    }

    $deleteRemember = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    if ($deleteRemember) {
        $deleteRemember->bind_param("i", $userId);
        $deleteRemember->execute();
        $deleteRemember->close();
    }

    $conn->close();
    header("Location: index.php?panel=signin&type=success&message=" . urlencode("Password updated successfully. You can now sign in."));
    exit();
}

if (empty($selector) || empty($validator)) {
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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
  <div class="fixed inset-0 pointer-events-none bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.14),transparent_22%),radial-gradient(circle_at_bottom_right,rgba(16,185,129,0.12),transparent_24%),linear-gradient(to_bottom,rgba(2,6,23,0.16),rgba(2,6,23,0.45))]"></div>

  <div class="relative min-h-screen">
    <main class="max-w-6xl mx-auto px-4 md:px-6 py-6 md:py-10 min-h-screen flex items-center">
      <div class="w-full rounded-[32px] border border-slate-800 bg-slate-900/70 backdrop-blur-xl shadow-[0_0_60px_rgba(15,23,42,0.55)] overflow-hidden">
        <div class="grid grid-cols-1 xl:grid-cols-12">
          <section class="xl:col-span-7 p-6 sm:p-8 lg:p-10 xl:p-12 border-b xl:border-b-0 xl:border-r border-slate-800">
            <div class="max-w-3xl">
              <p class="inline-flex items-center gap-2 rounded-full border border-sky-400/20 bg-sky-500/10 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.25em] text-sky-300">
                Secure Password Reset
              </p>

              <h1 class="mt-5 text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight leading-tight text-white">
                Create a stronger password for your account.
              </h1>

              <p class="mt-5 max-w-2xl text-sm sm:text-base leading-8 text-slate-300">
                Choose a secure new password so you can safely continue your TechTrail Community journey.
              </p>

              <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                  <p class="text-sm font-semibold text-white">Password rules</p>
                  <p class="mt-2 text-sm leading-7 text-slate-400">
                    Use at least 8 characters and include uppercase, lowercase, number, and symbol.
                  </p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                  <p class="text-sm font-semibold text-white">Security action</p>
                  <p class="mt-2 text-sm leading-7 text-slate-400">
                    Existing remember-me tokens are cleared after reset for extra protection.
                  </p>
                </div>
              </div>

              <div class="mt-8 rounded-3xl border border-slate-800 bg-gradient-to-r from-slate-900/80 to-slate-950/70 p-5">
                <p class="text-sm font-semibold text-white">Tip</p>
                <p class="mt-2 text-sm leading-7 text-slate-400">
                  Pick a password you can remember but others cannot easily guess. Avoid simple names, dates, or repeated patterns.
                </p>
              </div>
            </div>
          </section>

          <section class="xl:col-span-5 p-6 sm:p-8 lg:p-10 xl:p-12">
            <div class="max-w-md mx-auto">
              <div class="text-center">
                <p class="text-[11px] uppercase tracking-[0.32em] text-sky-300">TechTrail Community</p>
                <h2 class="mt-3 text-3xl md:text-4xl font-bold tracking-tight text-white">
                  Reset password
                </h2>
                <p class="mt-3 text-sm leading-7 text-slate-400">
                  Set a new secure password to access your account again.
                </p>
              </div>

              <?php if (!empty($message)): ?>
                <div class="mt-6 rounded-2xl border px-4 py-3 text-sm <?php echo $messageType === 'success'
                  ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200'
                  : 'border-rose-500/40 bg-rose-500/10 text-rose-200'; ?>">
                  <?php echo $message; ?>
                </div>
              <?php endif; ?>

              <form method="POST" action="reset-password.php" class="mt-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="selector" value="<?php echo htmlspecialchars($selector); ?>">
                <input type="hidden" name="validator" value="<?php echo htmlspecialchars($validator); ?>">

                <div>
                  <label class="block text-sm text-slate-300 mb-2">New password</label>
                  <div class="relative">
                    <input
                      type="password"
                      name="new_password"
                      id="newPassword"
                      placeholder="Enter a new password"
                      required
                      class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 pr-12 text-sm text-slate-100 placeholder-slate-500 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                      oninput="checkPasswordStrength(this.value, 'resetStrengthText', 'resetStrengthBar')"
                    >
                    <button
                      type="button"
                      onclick="togglePassword('newPassword', 'newEye')"
                      class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white"
                    >
                      <i id="newEye" class="fa-solid fa-eye"></i>
                    </button>
                  </div>
                </div>

                <div>
                  <div class="h-2 w-full overflow-hidden rounded-full bg-slate-800">
                    <div id="resetStrengthBar" class="h-full w-0 transition-all duration-300"></div>
                  </div>
                  <p id="resetStrengthText" class="mt-2 text-sm text-slate-400">Password strength: —</p>
                </div>

                <div>
                  <label class="block text-sm text-slate-300 mb-2">Confirm password</label>
                  <div class="relative">
                    <input
                      type="password"
                      name="confirm_password"
                      id="confirmPassword"
                      placeholder="Confirm your new password"
                      required
                      class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 pr-12 text-sm text-slate-100 placeholder-slate-500 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                    >
                    <button
                      type="button"
                      onclick="togglePassword('confirmPassword', 'confirmEye')"
                      class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white"
                    >
                      <i id="confirmEye" class="fa-solid fa-eye"></i>
                    </button>
                  </div>
                </div>

                <button
                  type="submit"
                  class="w-full rounded-2xl bg-sky-400 hover:bg-sky-300 text-slate-950 font-bold py-3.5 transition shadow-[0_0_25px_rgba(56,189,248,0.22)]"
                >
                  Reset password
                </button>
              </form>

              <div class="mt-6 rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                <p class="text-xs uppercase tracking-[0.15em] text-slate-400">Need another option?</p>
                <div class="mt-3 flex flex-wrap gap-3 text-sm">
                  <a href="index.php" class="text-sky-300 hover:text-sky-200 hover:underline">Back to login</a>
                  <span class="text-slate-600">•</span>
                  <a href="forgot-password.php" class="text-slate-400 hover:text-slate-200">Generate another reset link</a>
                </div>
              </div>

              <div class="mt-6 text-center text-xs text-slate-500 leading-6">
                Stronger passwords help protect your profile, posts, and community access.
              </div>
            </div>
          </section>
        </div>
      </div>
    </main>
  </div>

  <script>
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