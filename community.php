<?php
session_start();
include "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php?panel=signin&type=error&message=" . urlencode("Please sign in first."));
    exit();
}

$userId = $_SESSION["user_id"];
$userName = $_SESSION["user_name"] ?? "Unknown User";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $content = trim($_POST["content"] ?? "");

    if ($title === "" || $category === "" || $content === "") {
        $message = "All fields are required.";
        $messageType = "error";
    } else {
        $sql = "INSERT INTO posts (user_id, title, category, content, author_name) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $message = "Something went wrong. Please try again.";
            $messageType = "error";
        } else {
            $stmt->bind_param("issss", $userId, $title, $category, $content, $userName);

            if ($stmt->execute()) {
                $message = "Your post has been shared.";
                $messageType = "success";
            } else {
                $message = "Could not save your post. Please try again.";
                $messageType = "error";
            }

            $stmt->close();
        }
    }
}

$posts = [];

$postsSql = "SELECT title, category, content, author_name, created_at FROM posts ORDER BY created_at DESC";
$postsResult = $conn->query($postsSql);

if ($postsResult && $postsResult->num_rows > 0) {
    while ($row = $postsResult->fetch_assoc()) {
        $posts[] = $row;
    }
}

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Community - IT Journeys & Tips</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-5xl bg-white/10 backdrop-blur-md border border-white/20 rounded-3xl shadow-2xl overflow-hidden">
    <div class="bg-gradient-to-r from-violet-500 to-fuchsia-600 p-8 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-3xl md:text-4xl font-bold">Community Space</h1>
        <p class="mt-2 text-white/90">Share your IT journey, knowledge, and tips with others.</p>
      </div>
      <div class="flex gap-3">
        <a
          href="dashboard.php"
          class="px-4 py-2 rounded-xl bg-white/10 border border-white/30 hover:bg-white/20 transition text-sm font-semibold"
        >
          Back to Dashboard
        </a>
        <a
          href="logout.php"
          class="px-4 py-2 rounded-xl bg-fuchsia-700 hover:bg-fuchsia-800 transition text-sm font-semibold"
        >
          Logout
        </a>
      </div>
    </div>

    <div class="p-6 md:p-8 text-white space-y-8">
      <?php if (!empty($message)): ?>
        <div class="w-full">
          <div class="<?php echo $messageType === 'success' ? 'bg-green-500/90 border-green-300' : 'bg-red-500/90 border-red-300'; ?> border rounded-xl px-4 py-3 text-sm">
            <?php echo htmlspecialchars($message); ?>
          </div>
        </div>
      <?php endif; ?>

      <section class="bg-white/10 rounded-2xl p-6 border border-white/10">
        <h2 class="text-2xl font-semibold mb-4">Create a Post</h2>
        <form method="POST" class="space-y-4">
          <div>
            <label class="block text-sm text-gray-200 mb-1" for="title">Title</label>
            <input
              type="text"
              id="title"
              name="title"
              required
              class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-4 py-3 outline-none border border-white/10"
              placeholder="Share something about your IT journey..."
            />
          </div>

          <div>
            <label class="block text-sm text-gray-200 mb-1" for="category">Category</label>
            <input
              type="text"
              id="category"
              name="category"
              required
              class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-4 py-3 outline-none border border-white/10"
              placeholder="e.g. Web Development, Networking, Career Tips"
            />
          </div>

          <div>
            <label class="block text-sm text-gray-200 mb-1" for="content">Content</label>
            <textarea
              id="content"
              name="content"
              rows="5"
              required
              class="w-full bg-white/10 text-white placeholder-gray-300 rounded-xl px-4 py-3 outline-none border border-white/10"
              placeholder="Write about your experience, a lesson you learned, or a useful tip..."
            ></textarea>
          </div>

          <button
            type="submit"
            class="mt-2 bg-fuchsia-600 hover:bg-fuchsia-700 text-white font-bold uppercase tracking-wide px-8 py-3 rounded-xl shadow-lg transition text-sm"
          >
            Share Post
          </button>
        </form>
      </section>

      <section>
        <h2 class="text-2xl font-semibold mb-4">Community Posts</h2>

        <?php if (empty($posts)): ?>
          <p class="text-gray-300 text-sm">No posts yet. Be the first to share something!</p>
        <?php else: ?>
          <div class="space-y-4 max-h-[500px] overflow-y-auto pr-1">
            <?php foreach ($posts as $post): ?>
              <article class="bg-white/10 rounded-2xl p-5 border border-white/10">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                  <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($post["title"]); ?></h3>
                  <span class="px-3 py-1 rounded-full bg-fuchsia-600/80 text-xs font-semibold">
                    <?php echo htmlspecialchars($post["category"]); ?>
                  </span>
                </div>

                <p class="text-gray-100 text-sm whitespace-pre-line mb-4">
                  <?php echo nl2br(htmlspecialchars($post["content"])); ?>
                </p>

                <div class="flex flex-wrap items-center justify-between text-xs text-gray-300">
                  <span>By <?php echo htmlspecialchars($post["author_name"]); ?></span>
                  <span>
                    <?php
                      $date = strtotime($post["created_at"]);
                      echo date("F j, Y \\a\\t g:i A", $date);
                    ?>
                  </span>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </div>
  </div>
</body>
</html>

