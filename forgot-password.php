<?php
require_once "session.php";
require_once "csrf.php";

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

    $message = "If the account exists, a reset link has been generated.";
    $resetLink = "";

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $userId = (int)$user["id"];

        $deleteOld = $conn->prepare("DELETE FROM password_resets WHERE user_id = ? OR expires_at < NOW() OR used_at IS NOT NULL");
        if ($deleteOld) {
            $deleteOld->bind_param("i", $userId);
            $deleteOld->execute();
            $deleteOld->close();
        }

        $selector = bin2hex(random_bytes(8));
        $validator = bin2hex(random_bytes(32));
        $tokenHash = hash("sha256", $validator);
        $expiresAt = date("Y-m-d H:i:s", time() + 60 * 60);

        $insert = $conn->prepare("INSERT INTO password_resets (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)");
        if ($insert) {
            $insert->bind_param("isss", $userId, $selector, $tokenHash, $expiresAt);
            $insert->execute();
            $insert->close();

            $configPath = __DIR__ . "/config.php";
            $exampleConfigPath = __DIR__ . "/config.example.php";
            $appConfig = file_exists($configPath) ? require $configPath : require $exampleConfigPath;
            $baseUrl = rtrim($appConfig["base_url"] ?? "http://localhost/login-system", "/");

            $resetLink = $baseUrl . "/reset-password.php?selector=" . urlencode($selector) . "&validator=" . urlencode($validator);
        }
    }

    $stmt->close();
    $conn->close();

    header("Location: forgot-password.php?type=success&message=" . urlencode($message) . "&reset_link=" . urlencode($resetLink));
    exit();
}

$message = "";
$messageType = "";
$resetLinkFromQuery = "";

if (isset($_GET["message"]) && isset($_GET["type"])) {
    $message = htmlspecialchars($_GET["message"]);
    $messageType = $_GET["type"] === "success" ? "success" : "error";
}

if (isset($_GET["reset_link"])) {
    $resetLinkFromQuery = $_GET["reset_link"];
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
<body class="min-h-screen bg-slate-950 text-slate-100">
  <div class="fixed inset-0 pointer-events-none bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.14),transparent_22%),radial-gradient(circle_at_bottom_right,rgba(16,185,129,0.12),transparent_24%),linear-gradient(to_bottom,rgba(2,6,23,0.16),rgba(2,6,23,0.45))]"></div>

  <div class="relative min-h-screen">
    <main class="max-w-6xl mx-auto px-4 md:px-6 py-6 md:py-10 min-h-screen flex items-center">
      <div class="w-full rounded-[32px] border border-slate-800 bg-slate-900/70 backdrop-blur-xl shadow-[0_0_60px_rgba(15,23,42,0.55)] overflow-hidden">
        <div class="grid grid-cols-1 xl:grid-cols-12">
          <section class="xl:col-span-7 p-6 sm:p-8 lg:p-10 xl:p-12 border-b xl:border-b-0 xl:border-r border-slate-800">
            <div class="max-w-3xl">
              <p class="inline-flex items-center gap-2 rounded-full border border-sky-400/20 bg-sky-500/10 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.25em] text-sky-300">
                Account Recovery
              </p>

              <h1 class="mt-5 text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight leading-tight text-white">
                Recover your account access with confidence.
              </h1>

              <p class="mt-5 max-w-2xl text-sm sm:text-base leading-8 text-slate-300">
                Enter your email address to generate a secure password reset link and continue your TechTrail Community journey.
              </p>

              <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                  <p class="text-sm font-semibold text-white">Step 1</p>
                  <p class="mt-2 text-sm leading-7 text-slate-400">
                    Enter the email linked to your TechTrail account.
                  </p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                  <p class="text-sm font-semibold text-white">Step 2</p>
                  <p class="mt-2 text-sm leading-7 text-slate-400">
                    Open the generated reset link and verify your request.
                  </p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-5">
                  <p class="text-sm font-semibold text-white">Step 3</p>
                  <p class="mt-2 text-sm leading-7 text-slate-400">
                    Create a stronger password and sign back in safely.
                  </p>
                </div>
              </div>

              <div class="mt-8 rounded-3xl border border-slate-800 bg-gradient-to-r from-slate-900/80 to-slate-950/70 p-5">
                <p class="text-sm font-semibold text-white">Security note</p>
                <p class="mt-2 text-sm leading-7 text-slate-400">
                  For local demo mode, the reset link is shown on screen after submission. In a production setup, this would be sent through email.
                </p>
              </div>
            </div>
          </section>

          <section class="xl:col-span-5 p-6 sm:p-8 lg:p-10 xl:p-12">
            <div class="max-w-md mx-auto">
              <div class="text-center">
                <p class="text-[11px] uppercase tracking-[0.32em] text-sky-300">TechTrail Community</p>
                <h2 class="mt-3 text-3xl md:text-4xl font-bold tracking-tight text-white">
                  Forgot password
                </h2>
                <p class="mt-3 text-sm leading-7 text-slate-400">
                  Generate a reset link and get back to building.
                </p>
              </div>

              <?php if (!empty($message)): ?>
                <div class="mt-6 rounded-2xl border px-4 py-3 text-sm <?php echo $messageType === 'success'
                  ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200'
                  : 'border-rose-500/40 bg-rose-500/10 text-rose-200'; ?>">
                  <?php echo $message; ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($resetLinkFromQuery)): ?>
                <div class="mt-5 rounded-2xl border border-sky-500/40 bg-sky-500/10 px-4 py-4 text-sm text-sky-200 break-all">
                  <p class="font-semibold mb-2">Local demo reset link</p>
                  <a href="<?php echo htmlspecialchars($resetLinkFromQuery); ?>" class="underline break-all">
                    <?php echo htmlspecialchars($resetLinkFromQuery); ?>
                  </a>
                </div>
              <?php endif; ?>

              <form method="POST" action="forgot-password.php" class="mt-6 space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <div>
                  <label class="block text-sm text-slate-300 mb-2">Email address</label>
                  <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                    class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 placeholder-slate-500 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                  >
                </div>

                <button
                  type="submit"
                  class="w-full rounded-2xl bg-sky-400 hover:bg-sky-300 text-slate-950 font-bold py-3.5 transition shadow-[0_0_25px_rgba(56,189,248,0.22)]"
                >
                  Generate reset link
                </button>
              </form>

              <div class="mt-6 rounded-2xl border border-slate-800 bg-slate-950/50 p-4">
                <p class="text-xs uppercase tracking-[0.15em] text-slate-400">Need something else?</p>
                <div class="mt-3 flex flex-wrap gap-3 text-sm">
                  <a href="index.php" class="text-sky-300 hover:text-sky-200 hover:underline">Back to login</a>
                  <span class="text-slate-600">•</span>
                  <a href="index.php?panel=signup" class="text-slate-400 hover:text-slate-200">Create account</a>
                </div>
              </div>

              <div class="mt-6 text-center text-xs text-slate-500 leading-6">
                Keep your account secure with strong passwords and updated credentials.
              </div>
            </div>
          </section>
        </div>
      </div>
    </main>
  </div>
</body>
</html>