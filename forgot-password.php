<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);

    if (empty($email)) {
        header("Location: forgot-password.php?type=error&message=" . urlencode("Email is required."));
        exit();
    }

    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        header("Location: forgot-password.php?type=error&message=" . urlencode("Something went wrong."));
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        header("Location: reset-password.php?email=" . urlencode($email));
        exit();
    } else {
        header("Location: forgot-password.php?type=error&message=" . urlencode("No account found with that email."));
        exit();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
      <h1 class="text-3xl font-bold text-center mb-3">Forgot Password</h1>
      <p class="text-gray-300 text-center mb-6">Enter your email to reset your password.</p>

      <form method="POST" action="forgot-password.php" class="space-y-4">
        <input
          type="email"
          name="email"
          placeholder="Enter your email"
          required
          class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-5 py-4 outline-none border border-white/10"
        />

        <button
          type="submit"
          class="w-full bg-fuchsia-600 hover:bg-fuchsia-700 text-white font-bold py-4 rounded-xl transition"
        >
          Continue
        </button>
      </form>

      <div class="text-center mt-5">
        <a href="index.php" class="text-white hover:underline">Back to Login</a>
      </div>
    </div>
  </div>
</body>
</html>