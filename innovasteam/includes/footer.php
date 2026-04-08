  </div><!-- /.main-content -->
</div><!-- /.layout -->

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<?php if (isset($extraJs)): foreach ($extraJs as $js): ?>
<script src="<?= BASE_URL . '/assets/js/' . $js ?>"></script>
<?php endforeach; endif; ?>

<?php if (isset($inlineJs)): ?>
<script><?= $inlineJs ?></script>
<?php endif; ?>

<script>
  // Init Lucide icons
  if (typeof lucide !== 'undefined') lucide.createIcons();
</script>
</body>
</html>
