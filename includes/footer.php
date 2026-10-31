</main>
<footer class="footer">
    &copy; <?= date('Y') ?> Perpustakaan Kampus &mdash; Project Mata Kuliah Sistem Basis Data
</footer>
<script>
// JavaScript minimal: konfirmasi sebelum aksi yang berisiko (mis. hapus).
document.querySelectorAll('form[data-konfirmasi]').forEach(function (f) {
    f.addEventListener('submit', function (ev) {
        if (!confirm(f.dataset.konfirmasi)) ev.preventDefault();
    });
});
</script>
</body>
</html>
