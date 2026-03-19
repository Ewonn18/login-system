<?php
require_once "session.php";
require_once "auth.php";
require_once "csrf.php";
require_auth();

$csrfToken = get_csrf_token();
$userId = $_SESSION["user_id"];
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedToken = $_POST["csrf_token"] ?? "";

    if (!is_valid_csrf_token($postedToken)) {
        $message = "Invalid request. Please refresh the page and try again.";
        $messageType = "error";
    } else {
        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $bio = trim($_POST["bio"] ?? "");
        $school = trim($_POST["school"] ?? "");
        $specialization = trim($_POST["specialization"] ?? "");
        $skills = trim($_POST["skills"] ?? "");
        $newPassword = trim($_POST["new_password"] ?? "");
        $confirmPassword = trim($_POST["confirm_password"] ?? "");
        $profilePicturePath = null;
        $uploadOk = true;

        if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES["profile_picture"]["error"] !== UPLOAD_ERR_OK) {
                $message = "There was a problem uploading your profile picture.";
                $messageType = "error";
                $uploadOk = false;
            } else {
                $maxSize = 2 * 1024 * 1024;

                if ($_FILES["profile_picture"]["size"] > $maxSize) {
                    $message = "Profile picture must be smaller than 2MB.";
                    $messageType = "error";
                    $uploadOk = false;
                } else {
                    $imageInfo = @getimagesize($_FILES["profile_picture"]["tmp_name"]);

                    if ($imageInfo === false) {
                        $message = "Uploaded file is not a valid image.";
                        $messageType = "error";
                        $uploadOk = false;
                    } else {
                        $mimeType = $imageInfo["mime"];
                        $width = $imageInfo[0];
                        $height = $imageInfo[1];

                        $allowedTypes = [
                            "image/jpeg" => ".jpg",
                            "image/png" => ".png",
                            "image/webp" => ".webp",
                        ];

                        if (!array_key_exists($mimeType, $allowedTypes)) {
                            $message = "Only JPG, PNG, and WEBP images are allowed.";
                            $messageType = "error";
                            $uploadOk = false;
                        } elseif ($width < 100 || $height < 100) {
                            $message = "Image must be at least 100x100 pixels.";
                            $messageType = "error";
                            $uploadOk = false;
                        } elseif ($width > 3000 || $height > 3000) {
                            $message = "Image dimensions are too large.";
                            $messageType = "error";
                            $uploadOk = false;
                        } else {
                            $extension = $allowedTypes[$mimeType];
                            $uploadDir = __DIR__ . "/uploads/profile_pictures";

                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0755, true);
                            }

                            $fileName = "user_" . $userId . "_" . bin2hex(random_bytes(8)) . $extension;
                            $destination = $uploadDir . "/" . $fileName;

                            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $destination)) {
                                $profilePicturePath = "uploads/profile_pictures/" . $fileName;

                                $oldStmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
                                if ($oldStmt) {
                                    $oldStmt->bind_param("i", $userId);
                                    $oldStmt->execute();
                                    $oldResult = $oldStmt->get_result();

                                    if ($oldResult && $oldResult->num_rows === 1) {
                                        $oldRow = $oldResult->fetch_assoc();
                                        $oldPath = $oldRow["profile_picture"] ?? "";

                                        if (!empty($oldPath)) {
                                            $fullOldPath = __DIR__ . "/" . $oldPath;
                                            if (is_file($fullOldPath)) {
                                                @unlink($fullOldPath);
                                            }
                                        }
                                    }

                                    $oldStmt->close();
                                }
                            } else {
                                $message = "Failed to save profile picture.";
                                $messageType = "error";
                                $uploadOk = false;
                            }
                        }
                    }
                }
            }
        }

        if (empty($name) || empty($email)) {
            $message = "Name and email are required.";
            $messageType = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $messageType = "error";
        } elseif ($uploadOk) {
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

                            $sql = "UPDATE users
                                    SET name = ?, email = ?, password = ?, bio = ?, school = ?, specialization = ?, skills = ?, profile_picture = COALESCE(?, profile_picture)
                                    WHERE id = ?";
                            $stmt = $conn->prepare($sql);

                            if (!$stmt) {
                                $message = "Something went wrong.";
                                $messageType = "error";
                            } else {
                                $stmt->bind_param(
                                    "ssssssssi",
                                    $name,
                                    $email,
                                    $hashedPassword,
                                    $bio,
                                    $school,
                                    $specialization,
                                    $skills,
                                    $profilePicturePath,
                                    $userId
                                );

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
                        $sql = "UPDATE users
                                SET name = ?, email = ?, bio = ?, school = ?, specialization = ?, skills = ?, profile_picture = COALESCE(?, profile_picture)
                                WHERE id = ?";
                        $stmt = $conn->prepare($sql);

                        if (!$stmt) {
                            $message = "Something went wrong.";
                            $messageType = "error";
                        } else {
                            $stmt->bind_param(
                                "sssssssi",
                                $name,
                                $email,
                                $bio,
                                $school,
                                $specialization,
                                $skills,
                                $profilePicturePath,
                                $userId
                            );

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
}

$sql = "SELECT name, email, bio, school, specialization, skills, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $conn->close();
    die("Something went wrong.");
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows !== 1) {
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
  <title>TechTrail Community - Edit Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
  >
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-3xl bg-slate-900/80 border border-slate-800 rounded-3xl shadow-[0_0_40px_rgba(15,23,42,0.8)] overflow-hidden">
    <header class="bg-slate-900 border-b border-slate-800 px-6 md:px-8 py-5">
      <p class="text-xs uppercase tracking-[0.25em] text-slate-400">TechTrail Community</p>
      <h1 class="mt-2 text-2xl md:text-3xl font-semibold text-slate-50">Edit Profile</h1>
      <p class="mt-1 text-sm text-slate-400">
        Update your profile details, badge, skills, and password.
      </p>
    </header>

    <main class="p-6 md:p-8">
      <?php if (!empty($message)): ?>
        <div class="<?php echo $messageType === 'success' ? 'bg-emerald-500/10 border-emerald-500/60 text-emerald-200' : 'bg-rose-500/10 border-rose-500/60 text-rose-200'; ?> border rounded-2xl px-4 py-3 text-xs md:text-sm mb-6">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="edit-profile.php" enctype="multipart/form-data" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm text-slate-300 mb-2">Full Name</label>
            <input
              type="text"
              name="name"
              value="<?php echo htmlspecialchars($user["name"]); ?>"
              required
              class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
            >
          </div>

          <div>
            <label class="block text-sm text-slate-300 mb-2">Email Address</label>
            <input
              type="email"
              name="email"
              value="<?php echo htmlspecialchars($user["email"]); ?>"
              required
              class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
            >
          </div>
        </div>

        <div>
          <label class="block text-sm text-slate-300 mb-2">Profile Picture (optional)</label>
          <input
            type="file"
            name="profile_picture"
            accept="image/jpeg,image/png,image/webp"
            class="w-full text-sm text-slate-300 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-sky-600 file:text-white hover:file:bg-sky-500"
          >
          <p class="mt-2 text-xs text-slate-400">JPG, PNG, or WEBP. Max size 2MB. Minimum 100x100 pixels.</p>

          <?php if (!empty($user["profile_picture"])): ?>
            <div class="mt-4">
              <p class="text-xs text-slate-400 mb-2">Current profile picture</p>
              <div class="w-20 h-20 rounded-2xl overflow-hidden border border-slate-700 bg-slate-950">
                <img
                  src="<?php echo htmlspecialchars($user["profile_picture"]); ?>"
                  alt="Current profile picture"
                  class="w-full h-full object-cover"
                >
              </div>
            </div>
          <?php endif; ?>
        </div>

        <div>
          <label class="block text-sm text-slate-300 mb-2">Bio</label>
          <textarea
            name="bio"
            rows="4"
            placeholder="Share a short introduction about your IT journey."
            class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
          ><?php echo htmlspecialchars($user["bio"] ?? ""); ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <div>
            <label class="block text-sm text-slate-300 mb-2">School</label>
            <input
              type="text"
              name="school"
              value="<?php echo htmlspecialchars($user["school"] ?? ""); ?>"
              placeholder="e.g. IT University"
              class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
            >
          </div>

          <div>
            <label class="block text-sm text-slate-300 mb-2">Specialization</label>
            <select
              name="specialization"
              class="w-full bg-slate-900/80 text-slate-100 rounded-xl px-4 py-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
            >
              <?php
                $currentSpec = trim($user["specialization"] ?? "");
                $options = [
                    "Frontend Learner",
                    "Backend Explorer",
                    "Full Stack Starter",
                    "Networking Enthusiast",
                    "UI/UX Beginner",
                    "Database Builder",
                    "Security Curious",
                    "Career Builder",
                    "Cloud Rookie",
                ];
              ?>
              <option value="" <?php echo $currentSpec === '' ? 'selected' : ''; ?>>Select a specialization badge</option>
              <?php foreach ($options as $opt): ?>
                <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo $currentSpec === $opt ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($opt); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <p class="mt-2 text-xs text-slate-400">
              Choose a simple badge that best matches your current IT focus.
            </p>
          </div>

          <div>
            <label class="block text-sm text-slate-300 mb-2">Skills</label>
            <input
              type="text"
              name="skills"
              value="<?php echo htmlspecialchars($user["skills"] ?? ""); ?>"
              placeholder="e.g. PHP, MySQL, Tailwind"
              class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
            >
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm text-slate-300 mb-2">New Password (optional)</label>
            <div class="relative">
              <input
                type="password"
                name="new_password"
                id="editPassword"
                placeholder="Leave blank to keep current password"
                class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 pr-12 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
                oninput="checkPasswordStrength(this.value, 'editStrengthText', 'editStrengthBar')"
              >
              <button
                type="button"
                onclick="togglePassword('editPassword', 'editEye')"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white"
              >
                <i id="editEye" class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>

          <div>
            <label class="block text-sm text-slate-300 mb-2">Confirm New Password</label>
            <div class="relative">
              <input
                type="password"
                name="confirm_password"
                id="editConfirmPassword"
                placeholder="Confirm your new password"
                class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 pr-12 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
              >
              <button
                type="button"
                onclick="togglePassword('editConfirmPassword', 'editConfirmEye')"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white"
              >
                <i id="editConfirmEye" class="fa-solid fa-eye"></i>
              </button>
            </div>
          </div>
        </div>

        <div>
          <div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden">
            <div id="editStrengthBar" class="h-full w-0 transition-all duration-300"></div>
          </div>
          <p id="editStrengthText" class="text-left text-sm text-slate-400 mt-2">Password strength: —</p>
        </div>

        <div class="flex flex-wrap gap-3 pt-2">
          <button
            type="submit"
            class="px-6 py-3 rounded-xl bg-sky-600/90 hover:bg-sky-500 transition font-semibold text-white shadow-md shadow-sky-500/30"
          >
            Save Changes
          </button>

          <a
            href="dashboard.php"
            class="px-6 py-3 rounded-xl border border-slate-600/80 bg-slate-900/60 hover:bg-slate-800/80 transition text-slate-100"
          >
            Cancel
          </a>
        </div>
      </form>
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