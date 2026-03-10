<?php
include "db.php";

$email = isset($_GET["email"]) ? trim($_GET["email"]) : "";

if (empty($email)) {
    header("Location: forgot-password.php?type=error&message=" . urlencode("Invalid password reset request."));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $newPassword = trim($_POST["new_password"]);
    $confirmPassword = trim($_POST["confirm_password"]);

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
        header("Location: index.php?panel=signin&type=success&message=" . urlencode("Password updated successfully. You can now sign in."));
        exit();
    } else {
        header("Location: reset-password.php?email=" . urlencode($email) . "&type=error&message=" . urlencode("Failed to reset password."));
        exit();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
  />
</head>
<body class="min-h-screen bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center px-4">
  <?php
    $message = "";
    $messageType = "";

    if (isset($_GET["message"]) && isset($_GET["type"])) {
        $message = htmlspecialchars($_GET["message"]);
        $messageType = $_GET["type"] === "success" ? "success" : "error";
    }
  ?>

  <div class="w-full max-w-md">
    <?php if (!empty($message)): ?>
      <div class="<?php echo $messageType === 'success' ? 'bg-green-500/90 border-green-300' : 'bg-red-500/90 border-red-300'; ?> text-white border rounded-xl px-4 py-3 shadow-lg text-center mb-4">
        <?php echo $message; ?>
      </div>
    <?php endif; ?>

    <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-3xl shadow-2xl p-8 text-white">
      <h1 class="text-3xl font-bold text-center mb-3">Reset Password</h1>
      <p class="text-gray-300 text-center mb-6">Set your new password.</p>

      <form method="POST" action="reset-password.php" class="space-y-4">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

        <div class="relative">
          <input
            type="password"
            name="new_password"
            id="newPassword"
            placeholder="New Password"
            required
            class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-5 py-4 pr-14 outline-none border border-white/10"
            oninput="checkPasswordStrength(this.value, 'resetStrengthText', 'resetStrengthBar')"
          />
          <button
            type="button"
            onclick="togglePassword('newPassword', 'newEye')"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-white"
          >
            <i id="newEye" class="fa-solid fa-eye"></i>
          </button>
        </div>

        <div>
          <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
            <div id="resetStrengthBar" class="h-full w-0 transition-all duration-300"></div>
          </div>
          <p id="resetStrengthText" class="text-left text-sm text-gray-300 mt-2">Password strength: —</p>
        </div>

        <div class="relative">
          <input
            type="password"
            name="confirm_password"
            id="confirmPassword"
            placeholder="Confirm Password"
            required
            class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-5 py-4 pr-14 outline-none border border-white/10"
          />
          <button
            type="button"
            onclick="togglePassword('confirmPassword', 'confirmEye')"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-white"
          >
            <i id="confirmEye" class="fa-solid fa-eye"></i>
          </button>
        </div>

        <button
          type="submit"
          class="w-full bg-fuchsia-600 hover:bg-fuchsia-700 text-white font-bold py-4 rounded-xl transition"
        >
          Reset Password
        </button>
      </form>

      <div class="text-center mt-5">
        <a href="index.php" class="text-white hover:underline">Back to Login</a>
      </div>
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