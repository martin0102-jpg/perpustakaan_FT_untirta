// ===== JavaScript ringan (vanilla, tanpa library) =====
(function () {
    var d = document;
    var kurangGerak = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

    // 1. Menu hamburger (mobile)
    var burger = d.getElementById('burger'), menu = d.getElementById('menu');
    if (burger && menu) {
        burger.addEventListener('click', function () {
            burger.setAttribute('aria-expanded', menu.classList.toggle('buka'));
        });
    }

    // 2. Dropdown akun; tutup saat klik di luar atau tekan Esc
    var ub = d.getElementById('user-btn'), um = d.getElementById('user-menu');
    function tutupUser() { if (um) { um.classList.remove('buka'); ub.setAttribute('aria-expanded', 'false'); } }
    if (ub && um) {
        ub.addEventListener('click', function (e) {
            e.stopPropagation();
            ub.setAttribute('aria-expanded', um.classList.toggle('buka'));
        });
        d.addEventListener('click', function (e) { if (!um.contains(e.target)) tutupUser(); });
        d.addEventListener('keydown', function (e) { if (e.key === 'Escape') tutupUser(); });
    }

    // 3. Konfirmasi aksi berisiko (form dengan data-konfirmasi)
    d.querySelectorAll('form[data-konfirmasi]').forEach(function (f) {
        f.addEventListener('submit', function (ev) { if (!confirm(f.dataset.konfirmasi)) ev.preventDefault(); });
    });

    // 4. Cegah klik ganda: tombol submit dinonaktifkan + spinner setelah form dikirim
    d.querySelectorAll('form').forEach(function (f) {
        f.addEventListener('submit', function (ev) {
            if (ev.defaultPrevented) return;
            var b = f.querySelector('button[type=submit]');
            if (b) setTimeout(function () { b.disabled = true; b.classList.add('loading'); }, 0);
        });
    });
    // Saat user menekan tombol Back, aktifkan lagi tombolnya
    window.addEventListener('pageshow', function () {
        d.querySelectorAll('button.loading').forEach(function (b) { b.disabled = false; b.classList.remove('loading'); });
    });

    // 5. Label otomatis di tabel agar rapi menjadi kartu di layar kecil
    d.querySelectorAll('table').forEach(function (t) {
        var heads = Array.prototype.map.call(t.querySelectorAll('thead th'), function (th) { return th.textContent.trim(); });
        t.querySelectorAll('tbody tr').forEach(function (tr) {
            Array.prototype.forEach.call(tr.children, function (td, i) {
                if (heads[i] && !td.classList.contains('kosong')) td.setAttribute('data-label', heads[i]);
            });
        });
    });

    // 6. Pencarian langsung: mengetik langsung menyaring baris tabel (tanpa reload)
    d.querySelectorAll('.cari input[type=search]').forEach(function (inp) {
        var kartu = inp.closest('.card');
        inp.addEventListener('input', function () {
            var q = inp.value.toLowerCase();
            kartu.querySelectorAll('tbody tr, .buku-card').forEach(function (el) {
                if (el.querySelector('.kosong')) return;
                el.style.display = el.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
            });
        });
    });

    // 6b. Pilihan (select) bertanda data-auto-submit langsung mengirim form saat diganti
    d.querySelectorAll('select[data-auto-submit]').forEach(function (sel) {
        sel.addEventListener('change', function () { sel.form.submit(); });
    });

    // 7. Pesan notifikasi: tombol tutup + hilang otomatis (khusus pesan sukses)
    d.querySelectorAll('.alert').forEach(function (a) {
        var x = d.createElement('button');
        x.type = 'button'; x.className = 'alert-x'; x.setAttribute('aria-label', 'Tutup'); x.innerHTML = '&times;';
        x.onclick = function () { a.remove(); };
        a.appendChild(x);
        if (a.classList.contains('alert-sukses')) setTimeout(function () { a.remove(); }, 6000);
    });

    // 8. Tombol "Lihat/Sembunyi" pada kolom password
    d.querySelectorAll('input[type=password]').forEach(function (inp) {
        var w = d.createElement('div'); w.className = 'pw';
        inp.parentNode.insertBefore(w, inp); w.appendChild(inp);
        var b = d.createElement('button'); b.type = 'button'; b.className = 'pw-btn'; b.textContent = 'Lihat';
        b.onclick = function () {
            var tampil = inp.type === 'password';
            inp.type = tampil ? 'text' : 'password'; b.textContent = tampil ? 'Sembunyi' : 'Lihat';
        };
        w.appendChild(b);
    });

    // 9. Angka dashboard menghitung naik
    if (!kurangGerak) {
        d.querySelectorAll('.stat .angka').forEach(function (el) {
            var n = parseInt(el.textContent, 10);
            if (isNaN(n) || n === 0) return;
            var mulai = null;
            (function langkah(t) {
                if (mulai === null) mulai = t;
                var p = Math.min((t - mulai) / 700, 1);
                el.textContent = Math.round(n * p);
                if (p < 1) requestAnimationFrame(langkah);
            })(performance.now());
        });
    }

    // 10. Efek muncul saat di-scroll
    var rv = d.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && rv.length) {
        var io = new IntersectionObserver(function (es) {
            es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('tampil'); io.unobserve(e.target); } });
        }, { threshold: .12 });
        rv.forEach(function (el) { io.observe(el); });
    } else { rv.forEach(function (el) { el.classList.add('tampil'); }); }
})();
