  </main>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toasts"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/api.js"></script>
<?php foreach (($pageScripts ?? []) as $s): ?>
<script src="assets/js/<?= $s ?>"></script>
<?php endforeach; ?>
</body>
</html>
