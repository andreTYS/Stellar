    </main><!-- /.page-content -->
  </div><!-- /.main-content -->
</div><!-- /.layout -->

<?php /* main.js y ui.js se cargan con defer desde header.php.
        toggleSidebar()/closeSidebar() viven en main.js — no duplicar aquí. */ ?>
<?php if (!empty($extraJs)): foreach ($extraJs as $js): ?>
<script src="<?= BASE_URL ?>/assets/js/<?= $js ?>"></script>
<?php endforeach; endif; ?>
<?php if (!empty($inlineJs)): ?>
<script><?= $inlineJs ?></script>
<?php endif; ?>
</body>
</html>
