<?php
require_once "session.php";
require_once "auth.php";
require_once "csrf.php";
require_once "notification-helper.php";
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

$allowedPostTypes = [
    "discussion" => "Discussion",
    "mentor_help" => "Mentor Corner",
];

$allowedStatusFilters = [
    "" => "All mentor posts",
    "open" => "Open only",
    "solved" => "Solved only",
];

$categoryStyles = [
    "Web Development" => "bg-sky-500/15 text-sky-300 border border-sky-400/20",
    "Programming" => "bg-violet-500/15 text-violet-300 border border-violet-400/20",
    "Database" => "bg-emerald-500/15 text-emerald-300 border border-emerald-400/20",
    "Networking" => "bg-amber-500/15 text-amber-300 border border-amber-400/20",
    "Cybersecurity" => "bg-rose-500/15 text-rose-300 border border-rose-400/20",
    "Career Tips" => "bg-indigo-500/15 text-indigo-300 border border-indigo-400/20",
    "UI/UX" => "bg-pink-500/15 text-pink-300 border border-pink-400/20",
    "Cloud Computing" => "bg-cyan-500/15 text-cyan-300 border border-cyan-400/20",
];

$postTypeStyles = [
    "discussion" => "bg-slate-800 text-slate-200 border border-slate-700",
    "mentor_help" => "bg-amber-500/15 text-amber-300 border border-amber-400/20",
];

function buildBaseCommunityUrl(array $overrides = []): string
{
    $query = [];
    $allowedKeys = ["search", "category", "page", "post_type_filter", "mentor_status", "edit_post", "edit_comment"];

    foreach ($allowedKeys as $key) {
        if (isset($_GET[$key]) && $_GET[$key] !== "") {
            $query[$key] = $_GET[$key];
        }
    }

    foreach ($overrides as $key => $value) {
        if ($value === null || $value === "") {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }

    return "community.php" . (!empty($query) ? "?" . http_build_query($query) : "");
}

function redirectWithFlash(string $type, string $message, array $extra = []): void
{
    $query = array_merge([
        "type" => $type,
        "message" => $message,
    ], $extra);

    header("Location: " . buildBaseCommunityUrl($query));
    exit();
}

function buildPageUrl(
    int $targetPage,
    string $search,
    string $category,
    string $postTypeFilter,
    string $mentorStatus
): string {
    $query = ["page" => $targetPage];

    if ($search !== "") $query["search"] = $search;
    if ($category !== "") $query["category"] = $category;
    if ($postTypeFilter !== "") $query["post_type_filter"] = $postTypeFilter;
    if ($mentorStatus !== "") $query["mentor_status"] = $mentorStatus;

    return "community.php?" . http_build_query($query);
}

function categoryClass(string $category, array $categoryStyles): string
{
    return $categoryStyles[$category] ?? "bg-slate-800 text-slate-200 border border-slate-700";
}

function postTypeClass(string $postType, array $postTypeStyles): string
{
    return $postTypeStyles[$postType] ?? "bg-slate-800 text-slate-200 border border-slate-700";
}

function userInitial(string $name): string
{
    $trimmed = trim($name);
    return $trimmed !== "" ? strtoupper(substr($trimmed, 0, 1)) : "U";
}

function helpfulBadgeFromCount(int $count): string
{
    if ($count >= 10) return "Mentor Guide";
    if ($count >= 5) return "Helpful Mentor";
    if ($count >= 1) return "Community Helper";
    return "Growing Member";
}

function canManagePost(array $postRow, int $userId, bool $isAdmin): bool
{
    return $isAdmin || (int)$postRow["user_id"] === $userId;
}

function isSolvedMentorPost(array $postRow): bool
{
    return ($postRow["post_type"] ?? "") === "mentor_help" && (int)($postRow["best_answer_count"] ?? 0) > 0;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postedToken = $_POST["csrf_token"] ?? "";

    if (!is_valid_csrf_token($postedToken)) {
        redirectWithFlash("error", "Invalid request. Please refresh the page and try again.");
    }

    $formType = $_POST["form_type"] ?? "";

    if ($formType === "post") {
        $postType = trim($_POST["post_type"] ?? "discussion");
        $title = trim($_POST["title"] ?? "");
        $category = trim($_POST["category"] ?? "");
        $content = trim($_POST["content"] ?? "");

        if (!array_key_exists($postType, $allowedPostTypes)) {
            redirectWithFlash("error", "Invalid post type selected.");
        }

        if ($title === "" || $category === "" || $content === "") {
            redirectWithFlash("error", "All post fields are required.");
        }

        if (!in_array($category, $allowedCategories, true)) {
            redirectWithFlash("error", "Invalid category selected.");
        }

        $stmt = $conn->prepare("INSERT INTO posts (user_id, post_type, title, category, content, author_name) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            redirectWithFlash("error", "Could not prepare your post.");
        }

        $stmt->bind_param("isssss", $userId, $postType, $title, $category, $content, $userName);

        if ($stmt->execute()) {
            $stmt->close();
            redirectWithFlash("success", "Your post has been shared.", ["page" => 1]);
        }

        $stmt->close();
        redirectWithFlash("error", "Could not save your post.");
    }

    if ($formType === "edit_post") {
        $postId = (int)($_POST["post_id"] ?? 0);
        $postType = trim($_POST["post_type"] ?? "discussion");
        $title = trim($_POST["title"] ?? "");
        $category = trim($_POST["category"] ?? "");
        $content = trim($_POST["content"] ?? "");

        if ($postId <= 0) {
            redirectWithFlash("error", "Invalid post selected.");
        }

        if (!array_key_exists($postType, $allowedPostTypes)) {
            redirectWithFlash("error", "Invalid post type selected.");
        }

        if ($title === "" || $category === "" || $content === "") {
            redirectWithFlash("error", "All post fields are required.", ["edit_post" => $postId]);
        }

        if (!in_array($category, $allowedCategories, true)) {
            redirectWithFlash("error", "Invalid category selected.", ["edit_post" => $postId]);
        }

        $postStmt = $conn->prepare("
            SELECT p.id, p.user_id, p.post_type,
                   COALESCE(best.best_answer_count, 0) AS best_answer_count
            FROM posts p
            LEFT JOIN (
                SELECT post_id, COUNT(*) AS best_answer_count
                FROM comments
                WHERE is_best_answer = 1
                GROUP BY post_id
            ) best ON best.post_id = p.id
            WHERE p.id = ?
            LIMIT 1
        ");
        if (!$postStmt) {
            redirectWithFlash("error", "Could not verify the post.");
        }

        $postStmt->bind_param("i", $postId);
        $postStmt->execute();
        $postResult = $postStmt->get_result();
        $postRow = $postResult ? $postResult->fetch_assoc() : null;
        $postStmt->close();

        if (!$postRow) {
            redirectWithFlash("error", "Post not found.");
        }

        if (!canManagePost($postRow, $userId, $isAdmin)) {
            redirectWithFlash("error", "You do not have permission to edit this post.");
        }

        if (isSolvedMentorPost($postRow)) {
            redirectWithFlash("error", "Solved mentor questions cannot be edited until reopened.");
        }

        $stmt = $conn->prepare("
            UPDATE posts
            SET post_type = ?, title = ?, category = ?, content = ?
            WHERE id = ?
        ");
        if (!$stmt) {
            redirectWithFlash("error", "Could not update the post.");
        }

        $stmt->bind_param("ssssi", $postType, $title, $category, $content, $postId);

        if ($stmt->execute()) {
            $stmt->close();
            redirectWithFlash("success", "Post updated successfully.");
        }

        $stmt->close();
        redirectWithFlash("error", "Could not update the post.", ["edit_post" => $postId]);
    }

    if ($formType === "comment") {
        $postId = (int)($_POST["post_id"] ?? 0);
        $commentContent = trim($_POST["comment_content"] ?? "");

        if ($postId <= 0 || $commentContent === "") {
            redirectWithFlash("error", "Please enter a reply before submitting.");
        }

        $postOwnerStmt = $conn->prepare("SELECT user_id, title, post_type FROM posts WHERE id = ?");
        if (!$postOwnerStmt) {
            redirectWithFlash("error", "Could not verify the post.");
        }

        $postOwnerStmt->bind_param("i", $postId);
        $postOwnerStmt->execute();
        $postOwnerResult = $postOwnerStmt->get_result();
        $postRow = $postOwnerResult ? $postOwnerResult->fetch_assoc() : null;
        $postOwnerStmt->close();

        if (!$postRow) {
            redirectWithFlash("error", "Post not found.");
        }

        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, author_name, content) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            redirectWithFlash("error", "Could not prepare your reply.");
        }

        $stmt->bind_param("iiss", $postId, $userId, $userName, $commentContent);

        if ($stmt->execute()) {
            $stmt->close();

            $postOwnerId = (int)$postRow["user_id"];
            if ($postOwnerId !== $userId) {
                create_notification(
                    $conn,
                    $postOwnerId,
                    $userId,
                    "post_reply",
                    "New reply on your post",
                    $userName . " replied to your post: " . $postRow["title"],
                    "community.php?search=" . urlencode($postRow["title"])
                );
            }

            redirectWithFlash("success", "Your reply has been added.");
        }

        $stmt->close();
        redirectWithFlash("error", "Could not save your reply.");
    }

    if ($formType === "edit_comment") {
        $commentId = (int)($_POST["comment_id"] ?? 0);
        $commentContent = trim($_POST["comment_content"] ?? "");

        if ($commentId <= 0 || $commentContent === "") {
            redirectWithFlash("error", "Comment content is required.", ["edit_comment" => $commentId]);
        }

        $commentStmt = $conn->prepare("
            SELECT c.id, c.user_id, c.post_id, c.is_best_answer,
                   p.post_type,
                   COALESCE(best.best_answer_count, 0) AS best_answer_count
            FROM comments c
            INNER JOIN posts p ON p.id = c.post_id
            LEFT JOIN (
                SELECT post_id, COUNT(*) AS best_answer_count
                FROM comments
                WHERE is_best_answer = 1
                GROUP BY post_id
            ) best ON best.post_id = p.id
            WHERE c.id = ?
            LIMIT 1
        ");
        if (!$commentStmt) {
            redirectWithFlash("error", "Could not verify the comment.");
        }

        $commentStmt->bind_param("i", $commentId);
        $commentStmt->execute();
        $commentResult = $commentStmt->get_result();
        $commentRow = $commentResult ? $commentResult->fetch_assoc() : null;
        $commentStmt->close();

        if (!$commentRow) {
            redirectWithFlash("error", "Comment not found.");
        }

        $canManageComment = $isAdmin || (int)$commentRow["user_id"] === $userId;
        if (!$canManageComment) {
            redirectWithFlash("error", "You do not have permission to edit this comment.");
        }

        $isSolvedMentor = ($commentRow["post_type"] === "mentor_help" && (int)$commentRow["best_answer_count"] > 0);
        if ($isSolvedMentor && (int)$commentRow["is_best_answer"] === 1) {
            redirectWithFlash("error", "The accepted best answer cannot be edited while the mentor question is solved.");
        }

        $stmt = $conn->prepare("UPDATE comments SET content = ? WHERE id = ?");
        if (!$stmt) {
            redirectWithFlash("error", "Could not update the comment.");
        }

        $stmt->bind_param("si", $commentContent, $commentId);

        if ($stmt->execute()) {
            $stmt->close();
            redirectWithFlash("success", "Comment updated successfully.");
        }

        $stmt->close();
        redirectWithFlash("error", "Could not update the comment.", ["edit_comment" => $commentId]);
    }

    if ($formType === "toggle_like") {
        $postId = (int)($_POST["post_id"] ?? 0);

        if ($postId <= 0) {
            redirectWithFlash("error", "Invalid post selected.");
        }

        $check = $conn->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
        if (!$check) {
            redirectWithFlash("error", "Could not process your request.");
        }

        $check->bind_param("ii", $postId, $userId);
        $check->execute();
        $likeResult = $check->get_result();

        if ($likeResult && $likeResult->num_rows > 0) {
            $delete = $conn->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
            if (!$delete) {
                $check->close();
                redirectWithFlash("error", "Could not update your like.");
            }

            $delete->bind_param("ii", $postId, $userId);
            $delete->execute();
            $delete->close();
            $check->close();

            redirectWithFlash("success", "Post unliked.");
        }

        $insert = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
        if (!$insert) {
            $check->close();
            redirectWithFlash("error", "Could not update your like.");
        }

        $insert->bind_param("ii", $postId, $userId);
        $insert->execute();
        $insert->close();
        $check->close();

        redirectWithFlash("success", "Post liked.");
    }

    if ($formType === "mark_best_answer") {
        $postId = (int)($_POST["post_id"] ?? 0);
        $commentId = (int)($_POST["comment_id"] ?? 0);

        if ($postId <= 0 || $commentId <= 0) {
            redirectWithFlash("error", "Invalid best answer request.");
        }

        $ownerStmt = $conn->prepare("SELECT user_id, post_type, title FROM posts WHERE id = ?");
        if (!$ownerStmt) {
            redirectWithFlash("error", "Could not verify post ownership.");
        }

        $ownerStmt->bind_param("i", $postId);
        $ownerStmt->execute();
        $ownerResult = $ownerStmt->get_result();
        $postRow = $ownerResult ? $ownerResult->fetch_assoc() : null;
        $ownerStmt->close();

        if (!$postRow) {
            redirectWithFlash("error", "Post not found.");
        }

        if ($postRow["post_type"] !== "mentor_help") {
            redirectWithFlash("error", "Best answer can only be marked on Mentor Corner posts.");
        }

        $isOwner = (int)$postRow["user_id"] === (int)$userId;
        if (!$isOwner && !$isAdmin) {
            redirectWithFlash("error", "Only the post owner or admin can mark the best answer.");
        }

        $alreadySolvedStmt = $conn->prepare("SELECT COUNT(*) AS total FROM comments WHERE post_id = ? AND is_best_answer = 1");
        $alreadySolved = 0;
        if ($alreadySolvedStmt) {
            $alreadySolvedStmt->bind_param("i", $postId);
            $alreadySolvedStmt->execute();
            $alreadySolvedResult = $alreadySolvedStmt->get_result();
            $alreadySolvedRow = $alreadySolvedResult ? $alreadySolvedResult->fetch_assoc() : ["total" => 0];
            $alreadySolved = (int)($alreadySolvedRow["total"] ?? 0);
            $alreadySolvedStmt->close();
        }

        if ($alreadySolved > 0) {
            redirectWithFlash("error", "This mentor question is already solved. Reopen it first to choose another best answer.");
        }

        $checkCommentStmt = $conn->prepare("SELECT user_id, author_name FROM comments WHERE id = ? AND post_id = ?");
        if (!$checkCommentStmt) {
            redirectWithFlash("error", "Could not verify the answer.");
        }

        $checkCommentStmt->bind_param("ii", $commentId, $postId);
        $checkCommentStmt->execute();
        $checkCommentResult = $checkCommentStmt->get_result();
        $commentRow = $checkCommentResult ? $checkCommentResult->fetch_assoc() : null;
        $checkCommentStmt->close();

        if (!$commentRow) {
            redirectWithFlash("error", "Answer not found for this post.");
        }

        $markStmt = $conn->prepare("UPDATE comments SET is_best_answer = 1 WHERE id = ? AND post_id = ?");
        if (!$markStmt) {
            redirectWithFlash("error", "Could not mark best answer.");
        }

        $markStmt->bind_param("ii", $commentId, $postId);
        $markStmt->execute();
        $markStmt->close();

        $answerUserId = (int)$commentRow["user_id"];
        if ($answerUserId !== $userId) {
            create_notification(
                $conn,
                $answerUserId,
                $userId,
                "best_answer",
                "Your reply was marked as Best Answer",
                $userName . " marked your reply as the best answer on: " . $postRow["title"],
                "community.php?search=" . urlencode($postRow["title"])
            );
        }

        redirectWithFlash("success", "Best answer marked successfully.");
    }

    if ($formType === "reopen_mentor_question") {
        $postId = (int)($_POST["post_id"] ?? 0);

        if ($postId <= 0) {
            redirectWithFlash("error", "Invalid mentor question selected.");
        }

        $ownerStmt = $conn->prepare("SELECT user_id, post_type FROM posts WHERE id = ?");
        if (!$ownerStmt) {
            redirectWithFlash("error", "Could not verify post ownership.");
        }

        $ownerStmt->bind_param("i", $postId);
        $ownerStmt->execute();
        $ownerResult = $ownerStmt->get_result();
        $postRow = $ownerResult ? $ownerResult->fetch_assoc() : null;
        $ownerStmt->close();

        if (!$postRow) {
            redirectWithFlash("error", "Post not found.");
        }

        if ($postRow["post_type"] !== "mentor_help") {
            redirectWithFlash("error", "Only Mentor Corner posts can be reopened.");
        }

        $isOwner = (int)$postRow["user_id"] === (int)$userId;
        if (!$isOwner && !$isAdmin) {
            redirectWithFlash("error", "Only the post owner or admin can reopen this mentor question.");
        }

        $resetStmt = $conn->prepare("UPDATE comments SET is_best_answer = 0 WHERE post_id = ?");
        if (!$resetStmt) {
            redirectWithFlash("error", "Could not reopen mentor question.");
        }

        $resetStmt->bind_param("i", $postId);
        $resetStmt->execute();
        $resetStmt->close();

        redirectWithFlash("success", "Mentor question reopened.");
    }

    if ($formType === "delete_post") {
        $postId = (int)($_POST["post_id"] ?? 0);

        if ($postId <= 0) {
            redirectWithFlash("error", "Invalid post selected.");
        }

        if ($isAdmin) {
            $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
            if (!$stmt) {
                redirectWithFlash("error", "Could not delete post.");
            }

            $stmt->bind_param("i", $postId);
            $stmt->execute();
            $stmt->close();

            redirectWithFlash("success", "Post deleted.");
        }

        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        if (!$stmt) {
            redirectWithFlash("error", "Could not delete post.");
        }

        $stmt->bind_param("ii", $postId, $userId);
        $stmt->execute();
        $stmt->close();

        redirectWithFlash("success", "Your post has been deleted.");
    }

    if ($formType === "delete_comment") {
        $commentId = (int)($_POST["comment_id"] ?? 0);

        if ($commentId <= 0) {
            redirectWithFlash("error", "Invalid reply selected.");
        }

        if ($isAdmin) {
            $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
            if (!$stmt) {
                redirectWithFlash("error", "Could not delete reply.");
            }

            $stmt->bind_param("i", $commentId);
            $stmt->execute();
            $stmt->close();

            redirectWithFlash("success", "Reply deleted.");
        }

        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? AND user_id = ?");
        if (!$stmt) {
            redirectWithFlash("error", "Could not delete reply.");
        }

        $stmt->bind_param("ii", $commentId, $userId);
        $stmt->execute();
        $stmt->close();

        redirectWithFlash("success", "Your reply has been deleted.");
    }

    redirectWithFlash("error", "Unknown action.");
}

$message = "";
$messageType = "";

if (isset($_GET["message"], $_GET["type"])) {
    $message = trim((string)$_GET["message"]);
    $messageType = $_GET["type"] === "success" ? "success" : "error";
}

$search = trim($_GET["search"] ?? "");
$categoryFilter = trim($_GET["category"] ?? "");
$postTypeFilter = trim($_GET["post_type_filter"] ?? "");
$mentorStatus = trim($_GET["mentor_status"] ?? "");
$editPostId = (int)($_GET["edit_post"] ?? 0);
$editCommentId = (int)($_GET["edit_comment"] ?? 0);
$page = max(1, (int)($_GET["page"] ?? 1));
$postsPerPage = 5;
$offset = ($page - 1) * $postsPerPage;

$whereParts = [];
$params = [];
$types = "";

if ($search !== "") {
    $whereParts[] = "(p.title LIKE ? OR p.content LIKE ? OR p.author_name LIKE ?)";
    $searchLike = "%" . $search . "%";
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= "sss";
}

if ($categoryFilter !== "" && in_array($categoryFilter, $allowedCategories, true)) {
    $whereParts[] = "p.category = ?";
    $params[] = $categoryFilter;
    $types .= "s";
}

if ($postTypeFilter !== "" && array_key_exists($postTypeFilter, $allowedPostTypes)) {
    $whereParts[] = "p.post_type = ?";
    $params[] = $postTypeFilter;
    $types .= "s";
}

if ($mentorStatus === "open") {
    $whereParts[] = "p.post_type = 'mentor_help' AND COALESCE(best.best_answer_count, 0) = 0";
} elseif ($mentorStatus === "solved") {
    $whereParts[] = "p.post_type = 'mentor_help' AND COALESCE(best.best_answer_count, 0) > 0";
}

$whereSql = !empty($whereParts) ? " WHERE " . implode(" AND ", $whereParts) : "";

$countSql = "SELECT COUNT(*) AS total
             FROM posts p
             LEFT JOIN (
                 SELECT post_id, COUNT(*) AS best_answer_count
                 FROM comments
                 WHERE is_best_answer = 1
                 GROUP BY post_id
             ) best ON best.post_id = p.id
             $whereSql";
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
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $postsPerPage;
}

$postSql = "SELECT p.id, p.user_id, p.post_type, p.title, p.category, p.content, p.author_name, p.created_at,
                   COALESCE(l.like_count, 0) AS like_count,
                   CASE WHEN ul.user_id IS NULL THEN 0 ELSE 1 END AS user_liked,
                   COALESCE(best.best_answer_count, 0) AS best_answer_count,
                   COALESCE(reply_counts.reply_count, 0) AS reply_count
            FROM posts p
            LEFT JOIN (
                SELECT post_id, COUNT(*) AS like_count
                FROM post_likes
                GROUP BY post_id
            ) l ON l.post_id = p.id
            LEFT JOIN post_likes ul ON ul.post_id = p.id AND ul.user_id = ?
            LEFT JOIN (
                SELECT post_id, COUNT(*) AS best_answer_count
                FROM comments
                WHERE is_best_answer = 1
                GROUP BY post_id
            ) best ON best.post_id = p.id
            LEFT JOIN (
                SELECT post_id, COUNT(*) AS reply_count
                FROM comments
                GROUP BY post_id
            ) reply_counts ON reply_counts.post_id = p.id
            $whereSql
            ORDER BY
                CASE WHEN p.post_type = 'mentor_help' THEN 0 ELSE 1 END ASC,
                CASE
                    WHEN p.post_type = 'mentor_help' AND COALESCE(best.best_answer_count, 0) = 0 THEN 0
                    WHEN p.post_type = 'mentor_help' AND COALESCE(best.best_answer_count, 0) > 0 THEN 1
                    ELSE 2
                END ASC,
                p.created_at DESC
            LIMIT ? OFFSET ?";

$postStmt = $conn->prepare($postSql);
$posts = [];

if ($postStmt) {
    $postTypesSql = "i" . $types . "ii";
    $postParams = array_merge([$userId], $params, [$postsPerPage, $offset]);
    $postStmt->bind_param($postTypesSql, ...$postParams);
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

    $commentSql = "SELECT c.id, c.post_id, c.user_id, c.author_name, c.content, c.is_best_answer, c.created_at,
                          COALESCE(helper_counts.helpful_count, 0) AS helpful_count
                   FROM comments c
                   LEFT JOIN (
                       SELECT user_id, COUNT(*) AS helpful_count
                       FROM comments
                       WHERE is_best_answer = 1
                       GROUP BY user_id
                   ) helper_counts ON helper_counts.user_id = c.user_id
                   WHERE c.post_id IN ($placeholders)
                   ORDER BY c.is_best_answer DESC, c.created_at ASC";
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

$topHelpers = [];
$topHelperStmt = $conn->prepare(
    "SELECT u.id, u.name, COUNT(c.id) AS helpful_count
     FROM users u
     INNER JOIN comments c ON c.user_id = u.id
     WHERE c.is_best_answer = 1
     GROUP BY u.id, u.name
     ORDER BY helpful_count DESC, u.name ASC
     LIMIT 5"
);
if ($topHelperStmt) {
    $topHelperStmt->execute();
    $topHelperResult = $topHelperStmt->get_result();
    while ($row = $topHelperResult->fetch_assoc()) {
        $topHelpers[] = $row;
    }
    $topHelperStmt->close();
}

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechTrail Community - Mentor Corner</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
  <div class="fixed inset-0 pointer-events-none bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.14),transparent_22%),radial-gradient(circle_at_bottom_right,rgba(16,185,129,0.12),transparent_24%),linear-gradient(to_bottom,rgba(2,6,23,0.15),rgba(2,6,23,0.45))]"></div>

  <div class="relative">
    <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-xl">
      <div class="max-w-7xl mx-auto px-4 md:px-6">
        <div class="flex items-center justify-between gap-4 py-4">
          <div class="min-w-0">
            <p class="text-[11px] uppercase tracking-[0.32em] text-sky-300">TechTrail Community</p>
            <h1 class="mt-1 text-lg md:text-2xl font-semibold text-white">Mentor Corner + Community Feed</h1>
          </div>

          <div class="flex flex-wrap items-center gap-2 md:gap-3">
            <a href="dashboard.php" class="rounded-xl border border-slate-700 bg-slate-900/70 hover:bg-slate-800/90 px-4 py-2 text-sm text-slate-100 transition">Dashboard</a>
            <a href="notifications.php" class="rounded-xl border border-sky-500/40 bg-sky-500/10 hover:bg-sky-500/20 px-4 py-2 text-sm text-sky-200 transition">Notifications</a>
            <a href="mentor-leaderboard.php" class="rounded-xl border border-slate-700 bg-slate-900/70 hover:bg-slate-800/90 px-4 py-2 text-sm text-slate-100 transition">Mentor Leaderboard</a>
            <a href="profile.php?id=<?php echo (int)$userId; ?>" class="rounded-xl border border-slate-700 bg-slate-900/70 hover:bg-slate-800/90 px-4 py-2 text-sm text-slate-100 transition">My Profile</a>
            <a href="logout.php" class="rounded-xl bg-rose-600/90 hover:bg-rose-500 px-4 py-2 text-sm font-medium text-white transition">Sign out</a>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
      <?php if ($message !== ""): ?>
        <div class="mb-6 rounded-2xl border px-4 py-3 text-sm <?php echo $messageType === 'success'
            ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-200'
            : 'border-rose-500/40 bg-rose-500/10 text-rose-200'; ?>">
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <section class="rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-900/95 via-slate-900/90 to-slate-950/95 overflow-hidden shadow-[0_0_50px_rgba(15,23,42,0.55)]">
        <div class="grid grid-cols-1 lg:grid-cols-3">
          <div class="lg:col-span-2 p-6 md:p-8 border-b lg:border-b-0 lg:border-r border-slate-800">
            <p class="inline-flex items-center rounded-full border border-amber-400/20 bg-amber-500/10 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.25em] text-amber-300">
              Mentor Corner
            </p>
            <h2 class="mt-4 text-3xl md:text-4xl font-bold tracking-tight text-white">Ask questions. Share answers. Grow together.</h2>
            <p class="mt-4 text-sm md:text-base leading-7 text-slate-300">
              Open mentor questions are prioritized first so learners can get help faster and helpful members can find unanswered questions easily.
            </p>
          </div>

          <div class="p-6 md:p-8 bg-slate-950/40">
            <h3 class="text-lg font-semibold text-white">Editing now supported</h3>
            <div class="mt-5 space-y-4">
              <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                <p class="text-sm font-semibold text-slate-100">Edit your posts</p>
                <p class="mt-1 text-xs leading-6 text-slate-400">You can now update your own posts inline in the feed.</p>
              </div>
              <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                <p class="text-sm font-semibold text-slate-100">Edit your comments</p>
                <p class="mt-1 text-xs leading-6 text-slate-400">You can fix mistakes without deleting and reposting.</p>
              </div>
              <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                <p class="text-sm font-semibold text-slate-100">Mentor safety rules</p>
                <p class="mt-1 text-xs leading-6 text-slate-400">Solved mentor posts stay protected until reopened.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="mt-8 grid grid-cols-1 xl:grid-cols-12 gap-6">
        <aside class="xl:col-span-3 space-y-6">
          <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5">
            <h3 class="text-base font-semibold text-white">Search and filter</h3>

            <form method="GET" class="mt-4 space-y-4">
              <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Search posts..."
                class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
              >

              <select
                name="category"
                class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
              >
                <option value="">All categories</option>
                <?php foreach ($allowedCategories as $cat): ?>
                  <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat); ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <select
                name="post_type_filter"
                class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
              >
                <option value="">All post types</option>
                <?php foreach ($allowedPostTypes as $key => $label): ?>
                  <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $postTypeFilter === $key ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <select
                name="mentor_status"
                class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500"
              >
                <?php foreach ($allowedStatusFilters as $key => $label): ?>
                  <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $mentorStatus === $key ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <div class="flex gap-2">
                <button type="submit" class="flex-1 rounded-2xl bg-emerald-600/90 hover:bg-emerald-500 px-4 py-3 text-sm font-medium text-white transition">Apply</button>
                <a href="community.php" class="flex-1 rounded-2xl border border-slate-700 bg-slate-950/60 hover:bg-slate-800 px-4 py-3 text-center text-sm text-slate-100 transition">Reset</a>
              </div>
            </form>
          </div>

          <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5">
            <div class="flex items-center justify-between gap-3">
              <div>
                <h3 class="text-base font-semibold text-white">Top helpers</h3>
                <p class="mt-1 text-sm text-slate-400">Members with the most best answers.</p>
              </div>
              <a href="mentor-leaderboard.php" class="text-sm text-sky-400 hover:text-sky-300">View all →</a>
            </div>

            <div class="mt-4 space-y-3">
              <?php if (empty($topHelpers)): ?>
                <p class="text-sm text-slate-500">No helpers ranked yet.</p>
              <?php else: ?>
                <?php foreach ($topHelpers as $helper): ?>
                  <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                    <div class="flex items-center justify-between gap-3">
                      <a href="profile.php?id=<?php echo (int)$helper["id"]; ?>" class="text-sm font-semibold text-white hover:text-sky-300">
                        <?php echo htmlspecialchars($helper["name"]); ?>
                      </a>
                      <span class="text-xs text-emerald-300"><?php echo (int)$helper["helpful_count"]; ?> best</span>
                    </div>
                    <p class="mt-2 text-xs text-slate-400"><?php echo htmlspecialchars(helpfulBadgeFromCount((int)$helper["helpful_count"])); ?></p>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </aside>

        <section class="xl:col-span-6 space-y-6">
          <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5 md:p-6">
            <div class="flex items-start gap-4">
              <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500/80 via-cyan-500/70 to-emerald-400/70 text-lg font-bold text-white">
                <?php echo userInitial($userName); ?>
              </div>

              <div class="flex-1 min-w-0">
                <h3 class="text-lg font-semibold text-white">Create a new post</h3>
                <p class="mt-1 text-sm text-slate-400">Start a discussion or ask for help in Mentor Corner.</p>
              </div>
            </div>

            <form method="POST" class="mt-6 space-y-4">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
              <input type="hidden" name="form_type" value="post">

              <div>
                <label class="block text-xs text-slate-400 mb-2">Post type</label>
                <select
                  name="post_type"
                  required
                  class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                >
                  <?php foreach ($allowedPostTypes as $key => $label): ?>
                    <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="block text-xs text-slate-400 mb-2">Title</label>
                <input
                  type="text"
                  name="title"
                  required
                  placeholder="Write a clear title..."
                  class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                >
              </div>

              <div>
                <label class="block text-xs text-slate-400 mb-2">Category</label>
                <select
                  name="category"
                  required
                  class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                >
                  <option value="">Select a category</option>
                  <?php foreach ($allowedCategories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div>
                <label class="block text-xs text-slate-400 mb-2">Content</label>
                <textarea
                  name="content"
                  rows="5"
                  required
                  placeholder="Describe your question, lesson, or topic..."
                  class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500"
                ></textarea>
              </div>

              <div class="flex justify-end">
                <button type="submit" class="rounded-2xl bg-sky-600/90 hover:bg-sky-500 px-5 py-3 text-sm font-medium text-white transition">Share post</button>
              </div>
            </form>
          </div>

          <?php if (empty($posts)): ?>
            <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-8 text-center">
              <h3 class="text-xl font-semibold text-white">No posts found</h3>
              <p class="mt-2 text-sm text-slate-400">Try changing your filters or create a new discussion.</p>
            </div>
          <?php else: ?>
            <?php foreach ($posts as $post): ?>
              <?php
                $isSolved = $post["post_type"] === "mentor_help" && (int)$post["best_answer_count"] > 0;
                $canReopen = $isSolved && (((int)$post["user_id"] === (int)$userId) || $isAdmin);
                $canEditPost = canManagePost($post, $userId, $isAdmin) && !$isSolved;
                $isEditingPost = $editPostId === (int)$post["id"];
              ?>
              <article class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5 md:p-6">
                <?php if ($isSolved): ?>
                  <div class="mb-5 rounded-2xl border border-emerald-400/25 bg-emerald-500/10 px-4 py-4">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                      <div>
                        <p class="text-sm font-semibold text-emerald-200">Solved Mentor Question</p>
                        <p class="mt-1 text-xs leading-6 text-emerald-100/80">
                          This question is locked with one accepted best answer. Reopen it if you want to choose a different answer.
                        </p>
                      </div>

                      <?php if ($canReopen): ?>
                        <form method="POST">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                          <input type="hidden" name="form_type" value="reopen_mentor_question">
                          <input type="hidden" name="post_id" value="<?php echo (int)$post["id"]; ?>">
                          <button type="submit" class="rounded-xl bg-amber-600/90 hover:bg-amber-500 px-4 py-2 text-xs font-medium text-white transition">
                            Reopen Question
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endif; ?>

                <div class="flex items-start gap-4">
                  <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500/80 via-cyan-500/70 to-emerald-400/70 text-base font-bold text-white">
                    <?php echo userInitial($post["author_name"]); ?>
                  </div>

                  <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                      <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                          <a href="profile.php?id=<?php echo (int)$post["user_id"]; ?>" class="text-sm font-semibold text-white hover:text-sky-300 transition">
                            <?php echo htmlspecialchars($post["author_name"]); ?>
                          </a>
                          <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-medium <?php echo postTypeClass($post["post_type"], $postTypeStyles); ?>">
                            <?php echo htmlspecialchars($allowedPostTypes[$post["post_type"]] ?? "Discussion"); ?>
                          </span>
                          <?php if ($isSolved): ?>
                            <span class="inline-flex items-center rounded-full bg-emerald-500/15 text-emerald-300 border border-emerald-400/20 px-3 py-1 text-[11px] font-medium">
                              Solved
                            </span>
                          <?php elseif ($post["post_type"] === "mentor_help"): ?>
                            <span class="inline-flex items-center rounded-full bg-amber-500/15 text-amber-300 border border-amber-400/20 px-3 py-1 text-[11px] font-medium">
                              Open
                            </span>
                          <?php endif; ?>
                        </div>

                        <p class="mt-1 text-xs text-slate-500">
                          <?php echo date("M j, Y · g:i A", strtotime($post["created_at"])); ?>
                        </p>
                      </div>

                      <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium <?php echo categoryClass($post["category"], $categoryStyles); ?>">
                        <?php echo htmlspecialchars($post["category"]); ?>
                      </span>
                    </div>

                    <?php if ($isEditingPost && $canEditPost): ?>
                      <form method="POST" class="mt-4 space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="form_type" value="edit_post">
                        <input type="hidden" name="post_id" value="<?php echo (int)$post["id"]; ?>">

                        <div>
                          <label class="block text-xs text-slate-400 mb-2">Post type</label>
                          <select name="post_type" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
                            <?php foreach ($allowedPostTypes as $key => $label): ?>
                              <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $post["post_type"] === $key ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <div>
                          <label class="block text-xs text-slate-400 mb-2">Title</label>
                          <input type="text" name="title" value="<?php echo htmlspecialchars($post["title"]); ?>" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
                        </div>

                        <div>
                          <label class="block text-xs text-slate-400 mb-2">Category</label>
                          <select name="category" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100">
                            <?php foreach ($allowedCategories as $cat): ?>
                              <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $post["category"] === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <div>
                          <label class="block text-xs text-slate-400 mb-2">Content</label>
                          <textarea name="content" rows="5" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100"><?php echo htmlspecialchars($post["content"]); ?></textarea>
                        </div>

                        <div class="flex flex-wrap gap-2">
                          <button type="submit" class="rounded-xl bg-sky-600/90 hover:bg-sky-500 px-4 py-2 text-xs font-medium text-white transition">
                            Save post
                          </button>
                          <a href="<?php echo htmlspecialchars(buildBaseCommunityUrl(['edit_post' => null])); ?>" class="rounded-xl border border-slate-700 bg-slate-900/70 px-4 py-2 text-xs text-slate-100 hover:bg-slate-800 transition">
                            Cancel
                          </a>
                        </div>
                      </form>
                    <?php else: ?>
                      <h3 class="mt-4 text-xl font-semibold leading-tight text-white">
                        <?php echo htmlspecialchars($post["title"]); ?>
                      </h3>

                      <div class="mt-4 text-sm leading-7 text-slate-300 whitespace-pre-line">
                        <?php echo nl2br(htmlspecialchars($post["content"])); ?>
                      </div>
                    <?php endif; ?>

                    <div class="mt-5 flex flex-wrap items-center gap-2">
                      <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="form_type" value="toggle_like">
                        <input type="hidden" name="post_id" value="<?php echo (int)$post["id"]; ?>">
                        <button
                          type="submit"
                          class="rounded-2xl px-4 py-2 text-xs font-medium transition <?php echo (int)$post["user_liked"] === 1
                            ? 'bg-rose-600/90 text-white hover:bg-rose-500'
                            : 'border border-slate-700 bg-slate-950/60 text-slate-200 hover:bg-slate-800'; ?>"
                        >
                          <?php echo (int)$post["user_liked"] === 1 ? "♥ Liked" : "♡ Like"; ?>
                        </button>
                      </form>

                      <span class="rounded-2xl border border-slate-800 bg-slate-950/50 px-3 py-2 text-xs text-slate-400">
                        <?php echo (int)$post["like_count"]; ?> like(s)
                      </span>

                      <span class="rounded-2xl border border-slate-800 bg-slate-950/50 px-3 py-2 text-xs text-slate-400">
                        <?php echo (int)$post["reply_count"]; ?> reply(s)
                      </span>

                      <?php if ($canEditPost): ?>
                        <a href="<?php echo htmlspecialchars(buildBaseCommunityUrl(['edit_post' => (int)$post['id'], 'edit_comment' => null])); ?>" class="rounded-2xl border border-sky-500/30 bg-sky-500/10 px-4 py-2 text-xs font-medium text-sky-200 hover:bg-sky-500/20 transition">
                          Edit
                        </a>
                      <?php endif; ?>

                      <?php if ((int)$post["user_id"] === (int)$userId || $isAdmin): ?>
                        <form method="POST" onsubmit="return confirm('Delete this post?');">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                          <input type="hidden" name="form_type" value="delete_post">
                          <input type="hidden" name="post_id" value="<?php echo (int)$post["id"]; ?>">
                          <button type="submit" class="rounded-2xl bg-rose-600/90 hover:bg-rose-500 px-4 py-2 text-xs font-medium text-white transition">Delete</button>
                        </form>
                      <?php endif; ?>
                    </div>

                    <div class="mt-6 border-t border-slate-800 pt-5">
                      <h4 class="text-sm font-semibold text-slate-200">
                        <?php echo $post["post_type"] === "mentor_help" ? "Answers" : "Discussion"; ?>
                      </h4>

                      <?php if (empty($post["comments"])): ?>
                        <div class="mt-3 rounded-2xl border border-dashed border-slate-800 bg-slate-950/40 px-4 py-4 text-xs text-slate-500">
                          No replies yet.
                        </div>
                      <?php else: ?>
                        <div class="mt-4 space-y-3">
                          <?php foreach ($post["comments"] as $comment): ?>
                            <?php
                              $bestAnswer = (int)$comment["is_best_answer"] === 1;
                              $helperBadge = helpfulBadgeFromCount((int)$comment["helpful_count"]);
                              $canMarkBestAnswer = $post["post_type"] === "mentor_help"
                                  && !$isSolved
                                  && (((int)$post["user_id"] === (int)$userId) || $isAdmin);
                              $canEditComment = ($isAdmin || (int)$comment["user_id"] === $userId) && !($isSolved && $bestAnswer);
                              $isEditingComment = $editCommentId === (int)$comment["id"];
                            ?>
                            <div class="rounded-2xl border <?php echo $bestAnswer ? 'border-emerald-500/30 bg-emerald-500/10' : 'border-slate-800 bg-slate-950/55'; ?> p-4">
                              <div class="flex items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-800 text-xs font-semibold text-slate-200">
                                  <?php echo userInitial($comment["author_name"]); ?>
                                </div>

                                <div class="flex-1 min-w-0">
                                  <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                      <span class="text-xs font-semibold text-slate-200">
                                        <?php echo htmlspecialchars($comment["author_name"]); ?>
                                      </span>

                                      <span class="inline-flex items-center rounded-full bg-sky-500/10 text-sky-200 border border-sky-400/20 px-3 py-1 text-[10px] font-medium uppercase tracking-[0.12em]">
                                        <?php echo htmlspecialchars($helperBadge); ?>
                                      </span>

                                      <?php if ($bestAnswer): ?>
                                        <span class="inline-flex items-center rounded-full bg-emerald-500/15 text-emerald-300 border border-emerald-400/20 px-3 py-1 text-[10px] font-medium uppercase tracking-[0.15em]">
                                          Best Answer
                                        </span>
                                      <?php endif; ?>
                                    </div>

                                    <span class="text-[11px] text-slate-500">
                                      <?php echo date("M j, Y · g:i A", strtotime($comment["created_at"])); ?>
                                    </span>
                                  </div>

                                  <?php if ($isEditingComment && $canEditComment): ?>
                                    <form method="POST" class="mt-3 space-y-3">
                                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                      <input type="hidden" name="form_type" value="edit_comment">
                                      <input type="hidden" name="comment_id" value="<?php echo (int)$comment["id"]; ?>">

                                      <textarea name="comment_content" rows="3" class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100"><?php echo htmlspecialchars($comment["content"]); ?></textarea>

                                      <div class="flex flex-wrap gap-2">
                                        <button type="submit" class="rounded-xl bg-sky-600/90 hover:bg-sky-500 px-3 py-2 text-[11px] font-medium text-white transition">
                                          Save comment
                                        </button>
                                        <a href="<?php echo htmlspecialchars(buildBaseCommunityUrl(['edit_comment' => null])); ?>" class="rounded-xl border border-slate-700 bg-slate-900/70 px-3 py-2 text-[11px] text-slate-100 hover:bg-slate-800 transition">
                                          Cancel
                                        </a>
                                      </div>
                                    </form>
                                  <?php else: ?>
                                    <div class="mt-2 text-xs leading-6 text-slate-300 whitespace-pre-line">
                                      <?php echo nl2br(htmlspecialchars($comment["content"])); ?>
                                    </div>
                                  <?php endif; ?>

                                  <div class="mt-3 flex flex-wrap gap-2">
                                    <?php if ($canMarkBestAnswer && !$bestAnswer): ?>
                                      <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="form_type" value="mark_best_answer">
                                        <input type="hidden" name="post_id" value="<?php echo (int)$post["id"]; ?>">
                                        <input type="hidden" name="comment_id" value="<?php echo (int)$comment["id"]; ?>">
                                        <button type="submit" class="rounded-xl bg-emerald-600/90 hover:bg-emerald-500 px-3 py-1.5 text-[11px] font-medium text-white transition">Mark best answer</button>
                                      </form>
                                    <?php endif; ?>

                                    <?php if ($canEditComment): ?>
                                      <a href="<?php echo htmlspecialchars(buildBaseCommunityUrl(['edit_comment' => (int)$comment['id'], 'edit_post' => null])); ?>" class="rounded-xl border border-sky-500/30 bg-sky-500/10 px-3 py-1.5 text-[11px] font-medium text-sky-200 hover:bg-sky-500/20 transition">
                                        Edit
                                      </a>
                                    <?php endif; ?>

                                    <?php if ((int)$comment["user_id"] === (int)$userId || $isAdmin): ?>
                                      <form method="POST" onsubmit="return confirm('Delete this reply?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="form_type" value="delete_comment">
                                        <input type="hidden" name="comment_id" value="<?php echo (int)$comment["id"]; ?>">
                                        <button type="submit" class="rounded-xl bg-rose-600/90 hover:bg-rose-500 px-3 py-1.5 text-[11px] font-medium text-white transition">Delete</button>
                                      </form>
                                    <?php endif; ?>
                                  </div>
                                </div>
                              </div>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      <?php endif; ?>

                      <form method="POST" class="mt-4 space-y-3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="form_type" value="comment">
                        <input type="hidden" name="post_id" value="<?php echo (int)$post["id"]; ?>">

                        <textarea
                          name="comment_content"
                          rows="2"
                          placeholder="<?php echo $post["post_type"] === "mentor_help" ? 'Write an answer or helpful guidance...' : 'Write a helpful comment...'; ?>"
                          class="w-full rounded-2xl border border-slate-700 bg-slate-950/80 px-4 py-3 text-sm text-slate-100 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
                        ></textarea>

                        <div class="flex justify-end">
                          <button type="submit" class="rounded-2xl bg-emerald-600/90 hover:bg-emerald-500 px-4 py-2.5 text-xs font-medium text-white transition">
                            <?php echo $post["post_type"] === "mentor_help" ? 'Post answer' : 'Post reply'; ?>
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>

            <?php if ($totalPages > 1): ?>
              <div class="flex items-center justify-center gap-3 pt-2">
                <?php if ($page > 1): ?>
                  <a href="<?php echo htmlspecialchars(buildPageUrl($page - 1, $search, $categoryFilter, $postTypeFilter, $mentorStatus)); ?>" class="rounded-2xl border border-slate-700 bg-slate-900/70 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800 transition">Previous</a>
                <?php endif; ?>

                <span class="rounded-2xl border border-slate-800 bg-slate-950/50 px-4 py-2 text-sm text-slate-400">
                  Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                </span>

                <?php if ($page < $totalPages): ?>
                  <a href="<?php echo htmlspecialchars(buildPageUrl($page + 1, $search, $categoryFilter, $postTypeFilter, $mentorStatus)); ?>" class="rounded-2xl border border-slate-700 bg-slate-900/70 px-4 py-2 text-sm text-slate-200 hover:bg-slate-800 transition">Next</a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </section>

        <aside class="xl:col-span-3 space-y-6">
          <div class="rounded-3xl border border-slate-800 bg-slate-900/80 p-5">
            <h3 class="text-base font-semibold text-white">Quick mentor filters</h3>
            <div class="mt-4 flex flex-col gap-3">
              <a href="community.php?post_type_filter=mentor_help&mentor_status=open" class="rounded-2xl border border-amber-400/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-200 hover:bg-amber-500/15 transition">Open Mentor Questions</a>
              <a href="community.php?post_type_filter=mentor_help&mentor_status=solved" class="rounded-2xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200 hover:bg-emerald-500/15 transition">Solved Mentor Questions</a>
              <a href="community.php?post_type_filter=mentor_help" class="rounded-2xl border border-slate-700 bg-slate-950/60 px-4 py-3 text-sm text-slate-200 hover:bg-slate-800 transition">All Mentor Posts</a>
            </div>
          </div>
        </aside>
      </section>
    </main>
  </div>
</body>
</html>