<?php if (($pages ?? 1) > 1): ?>
<nav class="pagination" aria-label="Pagination">
    <?php for ($number = 1; $number <= $pages; $number++): ?>
        <a class="<?= $number === $page ? 'active' : '' ?>" href="<?= e(url($paginationRoute, array_merge($paginationParams ?? [], ['p' => $number]))) ?>" <?= $number === $page ? 'aria-current="page"' : '' ?>><?= e($number) ?></a>
    <?php endfor; ?>
</nav>
<?php endif; ?>
