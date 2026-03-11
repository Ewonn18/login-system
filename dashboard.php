<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php?panel=signin&type=error&message=" . urlencode("Please sign in first."));
    exit();
}

$userId = $_SESSION["user_id"];

$sql = "SELECT name, email, created_at FROM users WHERE id = ?";
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

$_SESSION["user_name"] = $user["name"];
$_SESSION["user_email"] = $user["email"];

$stmt->close();
$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Profile</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-4xl bg-white/10 backdrop-blur-md border border-white/20 rounded-3xl shadow-2xl overflow-hidden">

    <div class="bg-gradient-to-r from-violet-500 to-fuchsia-600 p-8 text-white">
      <h1 class="text-4xl font-bold">Welcome back, <?php echo htmlspecialchars($user["name"]); ?>!</h1>
<p class="mt-2 text-white/90">Manage your account details and keep your profile up to date.</p>
    </div>

    <div class="p-8 text-white">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <div class="md:col-span-1 bg-white/10 rounded-2xl p-6 border border-white/10 text-center">
          <div class="w-24 h-24 mx-auto rounded-full bg-fuchsia-600 flex items-center justify-center text-3xl font-bold">
            <?php echo strtoupper(substr($user["name"], 0, 1)); ?>
          </div>
          <h2 class="mt-4 text-2xl font-semibold"><?php echo htmlspecialchars($user["name"]); ?></h2>
          <p class="text-gray-300 mt-1"><?php echo htmlspecialchars($user["email"]); ?></p>
        </div>

        <div class="md:col-span-2 bg-white/10 rounded-2xl p-6 border border-white/10">
          <h2 class="text-2xl font-semibold mb-6">Account Information</h2>

          <div class="space-y-4">
            <div>
              <p class="text-gray-300 text-sm">Full Name</p>
              <p class="text-lg font-medium"><?php echo htmlspecialchars($user["name"]); ?></p>
            </div>

            <div>
              <p class="text-gray-300 text-sm">Email Address</p>
              <p class="text-lg font-medium"><?php echo htmlspecialchars($user["email"]); ?></p>
            </div>

            <div>
              <p class="text-gray-300 text-sm">Member Since</p>
              <p class="text-lg font-medium"><?php echo date("F j, Y", strtotime($user["created_at"])); ?></p>
            </div>

            <div>
              <p class="text-gray-300 text-sm">Remember Me</p>
              <p class="text-lg font-medium">
                <?php echo !empty($_SESSION["remember_me"]) ? "Enabled" : "Disabled"; ?>
              </p>
            </div>
          </div>

          <div class="mt-8 flex flex-wrap gap-3">
            <a
              href="edit-profile.php"
              class="px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 transition font-semibold"
            >
              Edit Profile
            </a>

            <a
              href="index.php"
              class="px-6 py-3 rounded-xl border border-white/30 hover:bg-white/10 transition"
            >
              Back
            </a>

            <a
              href="logout.php"
              class="px-6 py-3 rounded-xl bg-fuchsia-600 hover:bg-fuchsia-700 transition font-semibold"
            >
              Logout
            </a>
          </div>
        </div>

      </div>
    </div>
  </div>
</body>
</html>