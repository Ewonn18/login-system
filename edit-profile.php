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
        $headline = trim($_POST["headline"] ?? "");
        $skills = trim($_POST["skills"] ?? "");
        $githubUrl = trim($_POST["github_url"] ?? "");
        $portfolioUrl = trim($_POST["portfolio_url"] ?? "");
        $linkedinUrl = trim($_POST["linkedin_url"] ?? "");
        $newPassword = trim($_POST["new_password"] ?? "");
        $confirmPassword = trim($_POST["confirm_password"] ?? "");
        $profilePicturePath = null;
        $uploadOk = true;

        $urlFields = [
            "GitHub URL" => $githubUrl,
            "Portfolio URL" => $portfolioUrl,
            "LinkedIn URL" => $linkedinUrl,
        ];

        foreach ($urlFields as $label => $value) {
            if ($value !== "" && !filter_var($value, FILTER_VALIDATE_URL)) {
                $message = $label . " must be a valid URL.";
                $messageType = "error";
                $uploadOk = false;
                break;
            }
        }

        if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] !== UPLOAD_ERR_NO_FILE && $uploadOk) {
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
                                    SET name = ?, email = ?, password = ?, bio = ?, school = ?, specialization = ?, headline = ?, skills = ?,
                                        github_url = ?, portfolio_url = ?, linkedin_url = ?, profile_picture = COALESCE(?, profile_picture)
                                    WHERE id = ?";
                            $stmt = $conn->prepare($sql);

                            if (!$stmt) {
                                $message = "Something went wrong.";
                                $messageType = "error";
                            } else {
                                $stmt->bind_param(
                                    "ssssssssssssi",
                                    $name,
                                    $email,
                                    $hashedPassword,
                                    $bio,
                                    $school,
                                    $specialization,
                                    $headline,
                                    $skills,
                                    $githubUrl,
                                    $portfolioUrl,
                                    $linkedinUrl,
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
                                SET name = ?, email = ?, bio = ?, school = ?, specialization = ?, headline = ?, skills = ?,
                                    github_url = ?, portfolio_url = ?, linkedin_url = ?, profile_picture = COALESCE(?, profile_picture)
                                WHERE id = ?";
                        $stmt = $conn->prepare($sql);

                        if (!$stmt) {
                            $message = "Something went wrong.";
                            $messageType = "error";
                        } else {
                            $stmt->bind_param(
                                "sssssssssssi",
                                $name,
                                $email,
                                $bio,
                                $school,
                                $specialization,
                                $headline,
                                $skills,
                                $githubUrl,
                                $portfolioUrl,
                                $linkedinUrl,
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

$sql = "SELECT name, email, bio, school, specialization, headline, skills, github_url, portfolio_url, linkedin_url, profile_picture
        FROM users WHERE id = ?";
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
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
  <div class="fixed inset-0 pointer-events-none bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.14),transparent_22%),radial-gradient(circle_at_bottom_right,rgba(16,185,129,0.12),transparent_24%),linear-gradient(to_bottom,rgba(2,6,23,0.16),rgba(2,6,23,0.45))]"></div>

  <div class="relative max-w-6xl mx-auto px-4 py-8 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <p class="text-xs uppercase tracking-[0.25em] text-sky-300">TechTrail Community</p>
        <h1 class="mt-2 text-3xl font-semibold text-white">Edit Profile</h1>
      </div>

      <div class="flex flex-wrap gap-3">
        <a href="dashboard.php" class="rounded-xl border border-slate-700 bg-slate-900/70 px-4 py-2 text-sm text-slate-100 hover:bg-slate-800 transition">Dashboard</a>
        <a href="profile.php?id=<?php echo (int)$userId; ?>" class="rounded-xl border border-sky-500/40 bg-sky-500/10 px-4 py-2 text-sm text-sky-200 hover:bg-sky-500/20 transition">Public Profile</a>
      </div>
    </div>

    <?php if (!empty($message)): ?>
      <div class="<?php echo $messageType === 'success' ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200' : 'border-rose-500/40 bg-rose-500/10 text-rose-200'; ?> rounded-2xl border px-4 py-3 text-sm">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

      <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6">
        <h2 class="text-lg font-semibold text-white">Profile Preview</h2>

        <div class="mt-6 flex flex-col items-center text-center">
          <?php if (!empty($user["profile_picture"])): ?>
            <img src="<?php echo htmlspecialchars($user["profile_picture"]); ?>" alt="Profile picture" class="w-28 h-28 rounded-3xl object-cover border border-slate-700">
          <?php else: ?>
            <div class="w-28 h-28 rounded-3xl bg-gradient-to-br from-sky-500/80 via-cyan-500/70 to-emerald-400/70 flex items-center justify-center text-4xl font-bold text-white border border-slate-700">
              <?php echo strtoupper(substr($user["name"], 0, 1)); ?>
            </div>
          <?php endif; ?>

          <h3 class="mt-4 text-xl font-semibold text-white"><?php echo htmlspecialchars($user["name"]); ?></h3>
          <p class="mt-1 text-sm text-slate-400"><?php echo htmlspecialchars($user["headline"] ?: "Add a short headline"); ?></p>
        </div>

        <div class="mt-6">
          <label class="block text-sm text-slate-300 mb-2">Profile Picture</label>
          <input type="file" name="profile_picture" accept="image/jpeg,image/png,image/webp" class="w-full text-sm text-slate-300">
          <p class="mt-2 text-xs text-slate-500">JPG, PNG, WEBP. Max 2MB.</p>
        </div>
      </div>

      <div class="lg:col-span-2 space-y-6">
        <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6">
          <h2 class="text-lg font-semibold text-white">Basic Information</h2>

          <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-sm text-slate-300 mb-2">Full Name</label>
              <input type="text" name="name" value="<?php echo htmlspecialchars($user["name"]); ?>" required class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
            </div>

            <div>
              <label class="block text-sm text-slate-300 mb-2">Email Address</label>
              <input type="email" name="email" value="<?php echo htmlspecialchars($user["email"]); ?>" required class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
            </div>
          </div>

          <div class="mt-5">
            <label class="block text-sm text-slate-300 mb-2">Headline</label>
            <input type="text" name="headline" value="<?php echo htmlspecialchars($user["headline"] ?? ""); ?>" placeholder="Example: Student Backend Developer | PHP & MySQL Learner" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
          </div>

          <div class="mt-5">
            <label class="block text-sm text-slate-300 mb-2">Bio</label>
            <textarea name="bio" rows="4" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100"><?php echo htmlspecialchars($user["bio"] ?? ""); ?></textarea>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6">
          <h2 class="text-lg font-semibold text-white">Developer Details</h2>

          <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-sm text-slate-300 mb-2">School</label>
              <input type="text" name="school" value="<?php echo htmlspecialchars($user["school"] ?? ""); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
            </div>

            <div>
              <label class="block text-sm text-slate-300 mb-2">Specialization</label>
              <input type="text" name="specialization" value="<?php echo htmlspecialchars($user["specialization"] ?? ""); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
            </div>
          </div>

          <div class="mt-5">
            <label class="block text-sm text-slate-300 mb-2">Skills</label>
            <input type="text" name="skills" value="<?php echo htmlspecialchars($user["skills"] ?? ""); ?>" placeholder="PHP, MySQL, Tailwind, JavaScript" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
          </div>
        </div>

        <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6">
          <h2 class="text-lg font-semibold text-white">Links</h2>

          <div class="mt-5 grid grid-cols-1 gap-5">
            <div>
              <label class="block text-sm text-slate-300 mb-2">GitHub URL</label>
              <input type="url" name="github_url" value="<?php echo htmlspecialchars($user["github_url"] ?? ""); ?>" placeholder="https://github.com/yourname" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
            </div>

            <div>
              <label class="block text-sm text-slate-300 mb-2">Portfolio URL</label>
              <input type="url" name="portfolio_url" value="<?php echo htmlspecialchars($user["portfolio_url"] ?? ""); ?>" placeholder="https://yourportfolio.com" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
            </div>

            <div>
              <label class="block text-sm text-slate-300 mb-2">LinkedIn URL</label>
              <input type="url" name="linkedin_url" value="<?php echo htmlspecialchars($user["linkedin_url"] ?? ""); ?>" placeholder="https://linkedin.com/in/yourname" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
            </div>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-6">
          <h2 class="text-lg font-semibold text-white">Change Password</h2>

          <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label class="block text-sm text-slate-300 mb-2">New Password</label>
              <input type="password" name="new_password" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
            </div>

            <div>
              <label class="block text-sm text-slate-300 mb-2">Confirm Password</label>
              <input type="password" name="confirm_password" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
            </div>
          </div>
        </div>

        <div class="flex flex-wrap gap-3">
          <button type="submit" class="rounded-2xl bg-sky-600/90 hover:bg-sky-500 px-6 py-3 text-sm font-semibold text-white transition">
            Save Changes
          </button>
          <a href="dashboard.php" class="rounded-2xl border border-slate-700 bg-slate-900/70 px-6 py-3 text-sm text-slate-100 hover:bg-slate-800 transition">
            Back
          </a>
        </div>
      </div>
    </form>
  </div>
</body>
</html>