<?php
require_once "session.php";
require_once "auth.php";
require_once "csrf.php";
require_auth();

$csrfToken = get_csrf_token();
$userId = $_SESSION["user_id"];
$userName = $_SESSION["user_name"] ?? "Unknown User";
$isAdmin = is_admin();

$allowedCategories = [
    "Web Development",
    "Programming",
    "Database",
    "Networking",
    "Cybersecurity",
    "Career Tips",
    "UI/UX",
    "Cloud Computing",
];

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedToken = $_POST["csrf_token"] ?? "";

    if (!is_valid_csrf_token($postedToken)) {
        $message = "Invalid request. Please refresh the page and try again.";
        $messageType = "error";
    } else {
        $formType = $_POST["form_type"] ?? "";

        if ($formType === "post") {
            $title = trim($_POST["title"] ?? "");
            $category = trim($_POST["category"] ?? "");
            $content = trim($_POST["content"] ?? "");

            if ($title === "" || $category === "" || $content === "") {
                $message = "All post fields are required.";
                $messageType = "error";
            } elseif (!in_array($category, $allowedCategories, true)) {
                $message = "Invalid category selected.";
                $messageType = "error";
            } else {
                $stmt = $conn->prepare("INSERT INTO posts (user_id, title, category, content, author_name) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("issss", $userId, $title, $category, $content, $userName);
                    if ($stmt->execute()) {
                        $message = "Your post has been shared.";
                        $messageType = "success";
                    } else {
                        $message = "Could not save your post.";
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
                $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, author_name, content) VALUES (?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param("iiss", $postId, $userId, $userName, $commentContent);
                    if ($stmt->execute()) {
                        $message = "Your comment has been added.";
                        $messageType = "success";
                    } else {
                        $message = "Could not save your comment.";
                        $messageType = "error";
                    }
                    $stmt->close();
                }
            }
        } elseif ($formType === "toggle_like") {
            $postId = (int)($_POST["post_id"] ?? 0);
            if ($postId > 0) {
                $check = $conn->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
                if ($check) {
                    $check->bind_param("ii", $postId, $userId);
                    $check->execute();
                    $likeResult = $check->get_result();

                    if ($likeResult && $likeResult->num_rows > 0) {
                        $delete = $conn->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
                        if ($delete) {
                            $delete->bind_param("ii", $postId, $userId);
                            $delete->execute();
                            $delete->close();
                        }
                    } else {
                        $insert = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
                        if ($insert) {
                            $insert->bind_param("ii", $postId, $userId);
                            $insert->execute();
                            $insert->close();
                        }
                    }

                    $check->close();
                }
            }
        } elseif ($formType === "delete_post") {
            $postId = (int)($_POST["post_id"] ?? 0);

            if ($postId > 0) {
                if ($isAdmin) {
                    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $postId);
                        $stmt->execute();
                        $stmt->close();
                        $message = "Post deleted.";
                        $messageType = "success";
                    }
                } else {
                    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("ii", $postId, $userId);
                        $stmt->execute();
                        $stmt->close();
                        $message = "Your post has been deleted.";
                        $messageType = "success";
                    }
                }
            }
        } elseif ($formType === "delete_comment") {
            $commentId = (int)($_POST["comment_id"] ?? 0);

            if ($commentId > 0) {
                if ($isAdmin) {
                    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $commentId);
                        $stmt->execute();
                        $stmt->close();
                        $message = "Comment deleted.";
                        $messageType = "success";
                    }
                } else {
                    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
                    if ($stmt) {
                        $stmt->bind_param("ii", $commentId, $userId);
                        $stmt->execute();
                        $stmt->close();
                        $message = "Your comment has been deleted.";
                        $messageType = "success";
                    }
                }
            }
        }
    }
}

$search = trim($_GET["search"] ?? "");
$categoryFilter = trim($_GET["category"] ?? "");
$page = max(1, (int)($_GET["page"] ?? 1));
$postsPerPage = 5;
$offset = ($page - 1) * $postsPerPage;

$whereParts = [];
$params = [];
$types = "";

if ($search !== "") {
    $whereParts[] = "(title LIKE ? OR content LIKE ? OR author_name LIKE ?)";
    $searchLike = "%" . $search . "%";
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= "sss";
}

if ($categoryFilter !== "" && in_array($categoryFilter, $allowedCategories, true)) {
    $whereParts[] = "category = ?";
    $params[] = $categoryFilter;
    $types .= "s";
}

$whereSql = !empty($whereParts) ? " WHERE " . implode(" AND ", $whereParts) : "";

$countSql = "SELECT COUNT(*) AS total FROM posts" . $whereSql;
$countStmt = $conn->prepare($countSql);
$totalPosts = 0;

if ($countStmt) {
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $countRow = $countResult ? $countResult->fetch_assoc() : ["total" => 0];
    $totalPosts = (int)($countRow["total"] ?? 0);
    $countStmt->close();
}

$totalPages = max(1, (int)ceil($totalPosts / $postsPerPage));

$postSql = "SELECT p.id, p.user_id, p.title, p.category, p.content, p.author_name, p.created_at,
                   COALESCE(l.like_count, 0) AS like_count,
                   CASE WHEN ul.user_id IS NULL THEN 0 ELSE 1 END AS user_liked
            FROM posts p
            LEFT JOIN (
                SELECT post_id, COUNT(*) AS like_count
                FROM post_likes
                GROUP BY post_id
            ) l ON l.post_id = p.id
            LEFT JOIN post_likes ul ON ul.post_id = p.id AND ul.user_id = ?
            $whereSql
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?";

$postStmt = $conn->prepare($postSql);
$posts = [];

if ($postStmt) {
    $postTypes = "i" . $types . "ii";
    $postParams = array_merge([$userId], $params, [$postsPerPage, $offset]);
    $postStmt->bind_param($postTypes, ...$postParams);
    $postStmt->execute();
    $postResult = $postStmt->get_result();

    while ($row = $postResult->fetch_assoc()) {
        $row["comments"] = [];
        $posts[$row["id"]] = $row;
    }
    $postStmt->close();
}

if (!empty($posts)) {
    $postIds = array_keys($posts);
    $placeholders = implode(",", array_fill(0, count($postIds), "?"));
    $commentTypes = str_repeat("i", count($postIds));

    $commentSql = "SELECT id, post_id, user_id, author_name, content, created_at FROM comments WHERE post_id IN ($placeholders) ORDER BY created_at ASC";
    $commentStmt = $conn->prepare($commentSql);
    if ($commentStmt) {
        $commentStmt->bind_param($commentTypes, ...$postIds);
        $commentStmt->execute();
        $commentResult = $commentStmt->get_result();

        while ($commentRow = $commentResult->fetch_assoc()) {
            $posts[(int)$commentRow["post_id"]]["comments"][] = $commentRow;
        }

        $commentStmt->close();
    }
}

$conn->close();

function buildPageUrl(int $targetPage, string $search, string $category): string
{
    $query = ["page" => $targetPage];
    if ($search !== "") {
        $query["search"] = $search;
    }
    if ($category !== "") {
        $query["category"] = $category;
    }
    return "community.php?" . http_build_query($query);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechTrail Community - Community Hub</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-5xl bg-slate-900/80 border border-slate-800 rounded-3xl shadow-[0_0_40px_rgba(15,23,42,0.8)] overflow-hidden">
    <header class="bg-slate-900 border-b border-slate-800 px-6 md:px-8 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">TechTrail Community</p>
        <h1 class="mt-2 text-2xl md:text-3xl font-semibold text-slate-50">Community Hub</h1>
        <p class="mt-1 text-sm text-slate-400">Share your journey, ask questions, and learn with fellow student developers.</p>
      </div>
      <div class="flex gap-3">
        <a href="dashboard.php" class="px-4 py-2 rounded-xl border border-slate-600/80 bg-slate-900/60 hover:bg-slate-800/80 text-xs md:text-sm font-medium text-slate-100 transition">Back to dashboard</a>
        <a href="logout.php" class="px-4 py-2 rounded-xl bg-rose-600/90 hover:bg-rose-500 text-xs md:text-sm font-medium text-white transition">Sign out</a>
      </div>
    </header>

    <main class="p-6 md:p-8 space-y-8">
      <?php if (!empty($message)): ?>
        <div class="<?php echo $messageType === 'success' ? 'bg-emerald-500/10 border-emerald-500/60 text-emerald-200' : 'bg-rose-500/10 border-rose-500/60 text-rose-200'; ?> border rounded-2xl px-4 py-3 text-xs md:text-sm">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <section class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 md:p-6">
        <h2 class="text-base md:text-lg font-semibold text-slate-100">Create a new post</h2>
        <form method="POST" class="mt-4 space-y-4">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="form_type" value="post">

          <div>
            <label class="block text-xs md:text-sm text-slate-300 mb-1">Title</label>
            <input type="text" name="title" required class="w-full bg-slate-900/80 text-slate-100 rounded-xl px-4 py-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm">
          </div>

          <div>
            <label class="block text-xs md:text-sm text-slate-300 mb-1">Category</label>
            <select name="category" required class="w-full bg-slate-900/80 text-slate-100 rounded-xl px-4 py-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm">
              <option value="">Select a category</option>
              <?php foreach ($allowedCategories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-xs md:text-sm text-slate-300 mb-1">Content</label>
            <textarea name="content" rows="4" required class="w-full bg-slate-900/80 text-slate-100 rounded-xl px-4 py-3 outline-none border border-slate-700 focus:border-sky-500 focus:ring-1 focus:ring-sky-500 text-sm"></textarea>
          </div>

          <button type="submit" class="inline-flex items-center rounded-xl bg-sky-600/90 hover:bg-sky-500 text-sm font-medium text-white px-5 py-2.5 transition">
            Share post
          </button>
        </form>
      </section>

      <section class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 md:p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
          <div class="md:col-span-2">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search title, content, or author..." class="w-full bg-slate-900/80 text-slate-100 rounded-xl px-4 py-3 outline-none border border-slate-700 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm">
          </div>
          <div>
            <select name="category" class="w-full bg-slate-900/80 text-slate-100 rounded-xl px-4 py-3 outline-none border border-slate-700 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm">
              <option value="">All categories</option>
              <?php foreach ($allowedCategories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($cat); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="flex gap-2">
            <button type="submit" class="flex-1 rounded-xl bg-emerald-600/90 hover:bg-emerald-500 text-sm font-medium text-white px-4 py-3 transition">Apply</button>
            <a href="community.php" class="flex-1 rounded-xl border border-slate-600/80 bg-slate-900/60 hover:bg-slate-800/80 text-sm font-medium text-slate-100 px-4 py-3 text-center transition">Reset</a>
          </div>
        </form>
      </section>

      <section class="space-y-4">
        <?php if (empty($posts)): ?>
          <p class="text-slate-400 text-sm">No posts found.</p>
        <?php else: ?>
          <?php foreach ($posts as $post): ?>
            <article class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 md:p-5 space-y-4">
              <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                  <h3 class="text-sm md:text-base font-semibold text-slate-50"><?php echo htmlspecialchars($post["title"]); ?></h3>
                  <p class="mt-1 text-xs text-slate-400">
                    By
                    <a href="profile.php?id=<?php echo (int)$post["user_id"]; ?>" class="text-sky-400 hover:underline">
                      <?php echo htmlspecialchars($post["author_name"]); ?>
                    </a>
                  </p>
                </div>

                <div class="flex flex-col items-end gap-1">
                  <span class="inline-flex items-center rounded-full bg-sky-600/80 text-xs font-semibold px-3 py-1 text-white">
                    <?php echo htmlspecialchars($post["category"]); ?>
                  </span>
                  <span class="text-xs text-slate-400"><?php echo date("M j, Y · g:i A", strtotime($post["created_at"])); ?></span>
                </div>
              </div>

              <p class="text-slate-100 text-sm whitespace-pre-line"><?php echo nl2br(htmlspecialchars($post["content"])); ?></p>

              <div class="flex flex-wrap items-center gap-2">
                <form method="POST">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="form_type" value="toggle_like">
                  <input type="hidden" name="post_id" value="<?php echo (int)$post["id"]; ?>">
                  <button type="submit" class="rounded-xl px-3 py-1.5 text-xs font-medium <?php echo (int)$post["user_liked"] === 1 ? 'bg-rose-600/90 text-white' : 'border border-slate-600/80 bg-slate-900/60 text-slate-100'; ?>">
                    <?php echo (int)$post["user_liked"] === 1 ? "Unlike" : "Like"; ?>
                  </button>
                </form>
                <span class="text-xs text-slate-400"><?php echo (int)$post["like_count"]; ?> like(s)</span>

                <?php if ((int)$post["user_id"] === (int)$userId || $isAdmin): ?>
                  <form method="POST" onsubmit="return confirm('Delete this post?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="form_type" value="delete_post">
                    <input type="hidden" name="post_id" value="<?php echo (int)$post["id"]; ?>">
                    <button type="submit" class="rounded-xl bg-rose-600/90 hover:bg-rose-500 text-xs font-medium text-white px-3 py-1.5 transition">
                      <?php echo $isAdmin && (int)$post["user_id"] !== (int)$userId ? "Admin delete" : "Delete"; ?>
                    </button>
                  </form>
                <?php endif; ?>
              </div>

              <div class="border-t border-slate-800 pt-4 space-y-3">
                <h4 class="text-sm font-semibold text-slate-200">Comments</h4>

                <?php if (empty($post["comments"])): ?>
                  <p class="text-xs text-slate-500">No comments yet.</p>
                <?php else: ?>
                  <div class="space-y-2">
                    <?php foreach ($post["comments"] as $comment): ?>
                      <div class="rounded-xl bg-slate-900/80 border border-slate-800 px-3 py-2">
                        <div class="flex items-center justify-between gap-2">
                          <span class="text-xs font-medium text-slate-200"><?php echo htmlspecialchars($comment["author_name"]); ?></span>
                          <span class="text-xs text-slate-500"><?php echo date("M j, Y · g:i A", strtotime($comment["created_at"])); ?></span>
                        </div>
                        <p class="mt-1 text-xs text-slate-200 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($comment["content"])); ?></p>

                        <?php if ((int)$comment["user_id"] === (int)$userId || $isAdmin): ?>
                          <form method="POST" class="mt-2" onsubmit="return confirm('Delete this comment?');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="form_type" value="delete_comment">
                            <input type="hidden" name="comment_id" value="<?php echo (int)$comment["id"]; ?>">
                            <button type="submit" class="rounded-xl bg-rose-600/90 hover:bg-rose-500 text-[11px] font-medium text-white px-3 py-1.5 transition">
                              <?php echo $isAdmin && (int)$comment["user_id"] !== (int)$userId ? "Admin delete" : "Delete"; ?>
                            </button>
                          </form>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <form method="POST" class="mt-2 space-y-2">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                  <input type="hidden" name="form_type" value="comment">
                  <input type="hidden" name="post_id" value="<?php echo (int)$post["id"]; ?>">
                  <textarea name="comment_content" rows="2" class="w-full bg-slate-900/80 text-slate-100 rounded-xl px-3 py-2 outline-none border border-slate-700 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-xs" placeholder="Share a short comment..."></textarea>
                  <button type="submit" class="inline-flex items-center rounded-xl bg-emerald-600/90 hover:bg-emerald-500 text-xs font-medium text-white px-3 py-1.5 transition">
                    Post comment
                  </button>
                </form>
              </div>
            </article>
          <?php endforeach; ?>

          <?php if ($totalPages > 1): ?>
            <div class="flex items-center justify-center gap-3 pt-4">
              <?php if ($page > 1): ?>
                <a href="<?php echo htmlspecialchars(buildPageUrl($page - 1, $search, $categoryFilter)); ?>" class="px-4 py-2 rounded-xl border border-slate-600/80 bg-slate-900/60 text-sm">Previous</a>
              <?php endif; ?>

              <span class="text-sm text-slate-400">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>

              <?php if ($page < $totalPages): ?>
                <a href="<?php echo htmlspecialchars(buildPageUrl($page + 1, $search, $categoryFilter)); ?>" class="px-4 py-2 rounded-xl border border-slate-600/80 bg-slate-900/60 text-sm">Next</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </section>
    </main>
  </div>
</body>
</html>