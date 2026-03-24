<?php
if (!isset($pageTitle)) {
    $pageTitle = "TechTrail Community";
}

if (!isset($pageHeading)) {
    $pageHeading = "Dashboard";
}

if (!isset($pageActions) || !is_array($pageActions)) {
    $pageActions = [];
}
?>
<header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-xl">
  <div class="max-w-7xl mx-auto px-4 md:px-6">
    <div class="flex items-center justify-between gap-4 py-4">
      <div class="min-w-0">
        <p class="text-[11px] uppercase tracking-[0.32em] text-sky-300"><?php echo htmlspecialchars($pageTitle); ?></p>
        <h1 class="mt-1 text-lg md:text-2xl font-semibold text-white"><?php echo htmlspecialchars($pageHeading); ?></h1>
      </div>

      <?php if (!empty($pageActions)): ?>
        <div class="flex flex-wrap items-center gap-2 md:gap-3">
          <?php foreach ($pageActions as $action): ?>
            <?php
              $href = $action["href"] ?? "#";
              $label = $action["label"] ?? "Link";
              $variant = $action["variant"] ?? "secondary";

              $classes = "rounded-xl px-4 py-2 text-sm transition ";

              if ($variant === "primary") {
                  $classes .= "border border-sky-500/40 bg-sky-500/10 hover:bg-sky-500/20 text-sky-200";
              } elseif ($variant === "danger") {
                  $classes .= "bg-rose-600/90 hover:bg-rose-500 font-medium text-white";
              } else {
                  $classes .= "border border-slate-700 bg-slate-900/70 hover:bg-slate-800/90 text-slate-100";
              }
            ?>
            <a href="<?php echo htmlspecialchars($href); ?>" class="<?php echo $classes; ?>">
              <?php echo htmlspecialchars($label); ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</header>