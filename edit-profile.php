<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php?panel=signin&type=error&message=" . urlencode("Please sign in first."));
    exit();
}

$userId = $_SESSION["user_id"];
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $newPassword = trim($_POST["new_password"]);
    $confirmPassword = trim($_POST["confirm_password"]);

    if (empty($name) || empty($email)) {
        $message = "Name and email are required.";
        $messageType = "error";
    } else {
        $checkSql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $checkStmt = $conn->prepare($checkSql);

        if (!$checkStmt) {
            $message = "Something went wrong.";
            $messageType = "error";
        } else {
            $checkStmt->bind_param("si", $email, $userId);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $message = "Email is already used by another account.";
                $messageType = "error";
                $checkStmt->close();
            } else {
                $checkStmt->close();

                if (!empty($newPassword) || !empty($confirmPassword)) {
                    if ($newPassword !== $confirmPassword) {
                        $message = "Passwords do not match.";
                        $messageType = "error";
                    } elseif (
                        strlen($newPassword) < 8 ||
                        !preg_match('/[A-Z]/', $newPassword) ||
                        !preg_match('/[a-z]/', $newPassword) ||
                        !preg_match('/[0-9]/', $newPassword) ||
                        !preg_match('/[^A-Za-z0-9]/', $newPassword)
                    ) {
                        $message = "Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.";
                        $messageType = "error";
                    } else {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                        $sql = "UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);

                        if (!$stmt) {
                            $message = "Something went wrong.";
                            $messageType = "error";
                        } else {
                            $stmt->bind_param("sssi", $name, $email, $hashedPassword, $userId);

                            if ($stmt->execute()) {
                                $_SESSION["user_name"] = $name;
                                $_SESSION["user_email"] = $email;
                                $message = "Profile updated successfully.";
                                $messageType = "success";
                            } else {
                                $message = "Failed to update profile.";
                                $messageType = "error";
                            }

                            $stmt->close();
                        }
                    }
                } else {
                    $sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);

                    if (!$stmt) {
                        $message = "Something went wrong.";
                        $messageType = "error";
                    } else {
                        $stmt->bind_param("ssi", $name, $email, $userId);

                        if ($stmt->execute()) {
                            $_SESSION["user_name"] = $name;
                            $_SESSION["user_email"] = $email;
                            $message = "Profile updated successfully.";
                            $messageType = "success";
                        } else {
                            $message = "Failed to update profile.";
                            $messageType = "error";
                        }

                        $stmt->close();
                    }
                }
            }
        }
    }
}

$sql = "SELECT name, email FROM users WHERE id = ?";
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

$stmt->close();
$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
  />
</head>
<body class="min-h-screen bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-2xl bg-white/10 backdrop-blur-md border border-white/20 rounded-3xl shadow-2xl p-8 text-white">

    <h1 class="text-3xl font-bold mb-6 text-center">Edit Profile</h1>

    <?php if (!empty($message)): ?>
      <div class="<?php echo $messageType === 'success' ? 'bg-green-500/90 border-green-300' : 'bg-red-500/90 border-red-300'; ?> text-white border rounded-xl px-4 py-3 shadow-lg text-center mb-6">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="edit-profile.php" class="space-y-5">
      <div>
        <label class="block text-sm text-gray-300 mb-2">Full Name</label>
        <input
          type="text"
          name="name"
          value="<?php echo htmlspecialchars($user["name"]); ?>"
          required
          class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-5 py-4 outline-none border border-white/10"
        />
      </div>

      <div>
        <label class="block text-sm text-gray-300 mb-2">Email Address</label>
        <input
          type="email"
          name="email"
          value="<?php echo htmlspecialchars($user["email"]); ?>"
          required
          class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-5 py-4 outline-none border border-white/10"
        />
      </div>

      <div>
        <label class="block text-sm text-gray-300 mb-2">New Password (optional)</label>
        <div class="relative">
          <input
            type="password"
            name="new_password"
            id="editPassword"
            placeholder="Leave blank to keep current password"
            class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-5 py-4 pr-14 outline-none border border-white/10"
            oninput="checkPasswordStrength(this.value, 'editStrengthText', 'editStrengthBar')"
          />
          <button
            type="button"
            onclick="togglePassword('editPassword', 'editEye')"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-white"
          >
            <i id="editEye" class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>

      <div>
        <div class="w-full h-2 bg-white/10 rounded-full overflow-hidden">
          <div id="editStrengthBar" class="h-full w-0 transition-all duration-300"></div>
        </div>
        <p id="editStrengthText" class="text-left text-sm text-gray-300 mt-2">Password strength: —</p>
      </div>

      <div>
        <label class="block text-sm text-gray-300 mb-2">Confirm New Password</label>
        <div class="relative">
          <input
            type="password"
            name="confirm_password"
            id="editConfirmPassword"
            placeholder="Confirm your new password"
            class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-5 py-4 pr-14 outline-none border border-white/10"
          />
          <button
            type="button"
            onclick="togglePassword('editConfirmPassword', 'editConfirmEye')"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-300 hover:text-white"
          >
            <i id="editConfirmEye" class="fa-solid fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="flex flex-wrap gap-3 pt-2">
        <button
          type="submit"
          class="px-6 py-3 rounded-xl bg-fuchsia-600 hover:bg-fuchsia-700 transition font-semibold"
        >
          Save Changes
        </button>

        <a
          href="dashboard.php"
          class="px-6 py-3 rounded-xl border border-white/30 hover:bg-white/10 transition"
        >
          Cancel
        </a>
      </div>
    </form>
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