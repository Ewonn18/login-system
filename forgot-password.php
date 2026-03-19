<?php
session_start();
require_once "csrf.php";
include "db.php";

$csrfToken = get_csrf_token();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedToken = $_POST["csrf_token"] ?? "";

    if (!is_valid_csrf_token($postedToken)) {
        header("Location: forgot-password.php?type=error&message=" . urlencode("Invalid request. Please refresh the page and try again."));
        exit();
    }

    $email = trim($_POST["email"] ?? "");

    if (empty($email)) {
        header("Location: forgot-password.php?type=error&message=" . urlencode("Email is required."));
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: forgot-password.php?type=error&message=" . urlencode("Please enter a valid email address."));
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
        $stmt->close();
        $conn->close();
        header("Location: reset-password.php?email=" . urlencode($email));
        exit();
    } else {
        $stmt->close();
        $conn->close();
        header("Location: forgot-password.php?type=error&message=" . urlencode("No account found with that email."));
        exit();
    }
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
  <title>TechTrail Community - Forgot Password</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-md bg-slate-900/80 border border-slate-800 rounded-3xl shadow-[0_0_40px_rgba(15,23,42,0.8)] overflow-hidden">
    <header class="bg-slate-900 border-b border-slate-800 px-6 py-5">
      <p class="text-xs uppercase tracking-[0.25em] text-slate-400">TechTrail Community</p>
      <h1 class="mt-2 text-2xl font-semibold text-slate-50">Forgot Password</h1>
      <p class="mt-1 text-sm text-slate-400">Enter your email address to continue resetting your password.</p>
    </header>

    <main class="p-6">
      <?php if (!empty($message)): ?>
        <div class="<?php echo $messageType === 'success' ? 'bg-emerald-500/10 border-emerald-500/60 text-emerald-200' : 'bg-rose-500/10 border-rose-500/60 text-rose-200'; ?> border rounded-2xl px-4 py-3 text-sm mb-5">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="forgot-password.php" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

        <div>
          <label class="block text-sm text-slate-300 mb-2">Email Address</label>
          <input
            type="email"
            name="email"
            placeholder="Enter your email"
            required
            class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
          >
        </div>

        <button
          type="submit"
          class="w-full bg-sky-600/90 hover:bg-sky-500 text-white font-semibold py-3 rounded-xl transition shadow-md shadow-sky-500/30"
        >
          Continue
        </button>
      </form>

      <div class="text-center mt-5">
        <a href="index.php" class="text-sky-400 hover:underline text-sm">Back to Login</a>
      </div>
    </main>
  </div>
</body>
</html>