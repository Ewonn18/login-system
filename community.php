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
    $formType = $_POST["form_type"] ?? "";

    if ($formType === "post") {
        $title = trim($_POST["title"] ?? "");
        $category = trim($_POST["category"] ?? "");
        $content = trim($_POST["content"] ?? "");

        if ($title === "" || $category === "" || $content === "") {
            $message = "All post fields are required.";
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
    } elseif ($formType === "comment") {
        $postId = (int)($_POST["post_id"] ?? 0);
        $commentContent = trim($_POST["comment_content"] ?? "");

        if ($postId <= 0 || $commentContent === "") {
            $message = "Please enter a comment before submitting.";
            $messageType = "error";
        } else {
            $sql = "INSERT INTO comments (post_id, user_id, author_name, content) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if (!$stmt) {
                $message = "Something went wrong while saving your comment.";
                $messageType = "error";
            } else {
                $stmt->bind_param("iiss", $postId, $userId, $userName, $commentContent);

                if ($stmt->execute()) {
                    $message = "Your comment has been added.";
                    $messageType = "success";
                } else {
                    $message = "Could not save your comment. Please try again.";
                    $messageType = "error";
                }

                $stmt->close();
            }
        }
    }
}

$posts = [];

$postsSql = "SELECT id, title, category, content, author_name, created_at FROM posts ORDER BY created_at DESC";
$postsResult = $conn->query($postsSql);

if ($postsResult && $postsResult->num_rows > 0) {
    while ($row = $postsResult->fetch_assoc()) {
        $row["comments"] = [];
        $posts[$row["id"]] = $row;
    }
}

if (!empty($posts)) {
    $postIds = array_keys($posts);
    $placeholders = implode(",", array_fill(0, count($postIds), "?"));

    $types = str_repeat("i", count($postIds));
    $sqlComments = "SELECT post_id, author_name, content, created_at FROM comments WHERE post_id IN ($placeholders) ORDER BY created_at ASC";
    $stmtComments = $conn->prepare($sqlComments);

    if ($stmtComments) {
        $stmtComments->bind_param($types, ...$postIds);
        $stmtComments->execute();
        $resultComments = $stmtComments->get_result();

        if ($resultComments && $resultComments->num_rows > 0) {
            while ($commentRow = $resultComments->fetch_assoc()) {
                $postId = (int)$commentRow["post_id"];
                if (isset($posts[$postId])) {
                    $posts[$postId]["comments"][] = $commentRow;
                }
            }
        }

        $stmtComments->close();
    }
}

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Community Hub</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-5xl bg-slate-900/80 border border-slate-800 rounded-3xl shadow-[0_0_40px_rgba(15,23,42,0.8)] overflow-hidden">

    <!-- Top bar -->
    <header class="bg-gradient-to-r from-slate-900 via-slate-900 to-slate-900 border-b border-slate-800 px-6 md:px-8 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">IT Community Hub</p>
        <h1 class="mt-2 text-2xl md:text-3xl font-semibold text-slate-50">
          Share your journey, learn from others.
        </h1>
        <p class="mt-1 text-sm text-slate-400">
          Post your experiences, tips, and questions with fellow developers.
        </p>
      </div>
      <div class="flex gap-3">
        <a
          href="dashboard.php"
          class="px-4 py-2 rounded-xl border border-slate-600/80 bg-slate-900/60 hover:bg-slate-800/80 text-xs md:text-sm font-medium text-slate-100 transition"
        >
          Back to dashboard
        </a>
        <a
          href="logout.php"
          class="px-4 py-2 rounded-xl bg-rose-600/90 hover:bg-rose-500 text-xs md:text-sm font-medium text-white shadow-md shadow-rose-500/30 transition"
        >
          Sign out
        </a>
      </div>
    </header>

    <main class="p-6 md:p-8 space-y-8">
      <?php if (!empty($message)): ?>
        <div class="<?php echo $messageType === 'success' ? 'bg-emerald-500/10 border-emerald-500/60 text-emerald-200' : 'bg-rose-500/10 border-rose-500/60 text-rose-200'; ?> border rounded-2xl px-4 py-3 text-xs md:text-sm">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <!-- Top: Create post panel -->
      <section class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 md:p-6">
        <h2 class="text-base md:text-lg font-semibold text-slate-100">Create a new post</h2>
        <p class="mt-1 text-xs md:text-sm text-slate-400">
          Share an IT story, concept, or tip with the community.
        </p>

        <form method="POST" class="mt-4 space-y-4">
          <input type="hidden" name="form_type" value="post" />

          <div>
            <label class="block text-xs md:text-sm text-slate-300 mb-1" for="title">Title</label>
            <input
              type="text"
              id="title"
              name="title"
              required
              class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
              placeholder="e.g. How I learned PHP and MySQL"
            />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs md:text-sm text-slate-300 mb-1" for="category">Category</label>
              <input
                type="text"
                id="category"
                name="category"
                required
                class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
                placeholder="e.g. Web Development, Networking, Career Tips"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs md:text-sm text-slate-300 mb-1" for="content">Content</label>
            <textarea
              id="content"
              name="content"
              rows="4"
              required
              class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-4 py-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"
              placeholder="Write about your experience, a lesson you learned, or a useful tip..."
            ></textarea>
          </div>

          <button
            type="submit"
            class="inline-flex items-center gap-2 mt-1 rounded-xl bg-sky-600/90 hover:bg-sky-500 text-xs md:text-sm font-medium text-white px-5 py-2.5 shadow-md shadow-sky-500/30 transition"
          >
            Share post
          </button>
        </form>
      </section>

      <!-- Bottom: Community feed -->
      <section class="space-y-4">
        <div class="flex items-center justify-between gap-2">
          <h2 class="text-base md:text-lg font-semibold text-slate-100">Community posts</h2>
          <p class="text-xs text-slate-400">Latest posts from members of the IT community.</p>
        </div>

        <?php if (empty($posts)): ?>
          <p class="text-slate-400 text-xs md:text-sm">No posts yet. Be the first to share something!</p>
        <?php else: ?>
          <div class="space-y-4 max-h-[520px] overflow-y-auto pr-1">
            <?php foreach ($posts as $post): ?>
              <article class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 md:p-5 space-y-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                  <div>
                    <h3 class="text-sm md:text-base font-semibold text-slate-50">
                      <?php echo htmlspecialchars($post["title"]); ?>
                    </h3>
                    <p class="mt-1 text-xs text-slate-400">
                      By <?php echo htmlspecialchars($post["author_name"]); ?>
                    </p>
                  </div>
                  <div class="flex flex-col items-end gap-1">
                    <span class="inline-flex items-center rounded-full bg-sky-600/80 text-[11px] md:text-xs font-semibold px-3 py-1 text-white">
                      <?php echo htmlspecialchars($post["category"]); ?>
                    </span>
                    <span class="text-[11px] md:text-xs text-slate-400">
                      <?php
                        $date = strtotime($post["created_at"]);
                        echo date("M j, Y · g:i A", $date);
                      ?>
                    </span>
                  </div>
                </div>

                <p class="text-slate-100 text-xs md:text-sm whitespace-pre-line">
                  <?php echo nl2br(htmlspecialchars($post["content"])); ?>
                </p>

                <!-- Comments -->
                <div class="border-t border-slate-800 pt-4 space-y-3">
                  <h4 class="text-xs md:text-sm font-semibold text-slate-200">Comments</h4>

                  <?php if (empty($post["comments"])): ?>
                    <p class="text-[11px] md:text-xs text-slate-500">
                      No comments yet. Start the conversation.
                    </p>
                  <?php else: ?>
                    <div class="space-y-2">
                      <?php foreach ($post["comments"] as $comment): ?>
                        <div class="rounded-xl bg-slate-900/80 border border-slate-800 px-3 py-2">
                          <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] md:text-xs font-medium text-slate-200">
                              <?php echo htmlspecialchars($comment["author_name"]); ?>
                            </span>
                            <span class="text-[10px] md:text-[11px] text-slate-500">
                              <?php
                                $cDate = strtotime($comment["created_at"]);
                                echo date("M j, Y · g:i A", $cDate);
                              ?>
                            </span>
                          </div>
                          <p class="mt-1 text-[11px] md:text-xs text-slate-200 whitespace-pre-line">
                            <?php echo nl2br(htmlspecialchars($comment["content"])); ?>
                          </p>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>

                  <!-- Add comment form -->
                  <form method="POST" class="mt-2 space-y-2">
                    <input type="hidden" name="form_type" value="comment" />
                    <input type="hidden" name="post_id" value="<?php echo (int)$post["id"]; ?>" />
                    <label class="block text-[11px] md:text-xs text-slate-300 mb-1" for="comment_<?php echo (int)$post["id"]; ?>">
                      Add your insight
                    </label>
                    <textarea
                      id="comment_<?php echo (int)$post["id"]; ?>"
                      name="comment_content"
                      rows="2"
                      class="w-full bg-slate-900/80 text-slate-100 placeholder-slate-500 rounded-xl px-3 py-2 outline-none border border-slate-700 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-[11px] md:text-xs"
                      placeholder="Share a short comment, tip, or question..."
                    ></textarea>
                    <button
                      type="submit"
                      class="inline-flex items-center gap-1 rounded-xl bg-emerald-600/90 hover:bg-emerald-500 text-[11px] md:text-xs font-medium text-white px-3 py-1.5 shadow-md shadow-emerald-500/30 transition"
                    >
                      Post comment
                    </button>
                  </form>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>

