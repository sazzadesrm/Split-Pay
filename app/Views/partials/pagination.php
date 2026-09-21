<?php
/** Vars: $meta ['total','page','per_page','last_page'], $baseUrl */
if (($meta['last_page'] ?? 1) <= 1) return;
$qs = $_GET;
?>
<nav class="mt-3" aria-label="Pagination">
  <ul class="pagination pagination-sm">
    <?php for ($p = 1; $p <= $meta['last_page']; $p++): $qs['page'] = $p; ?>
      <li class="page-item <?= $p === (int) $meta['page'] ? 'active' : '' ?>">
        <a class="page-link" href="?<?= http_build_query($qs) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
