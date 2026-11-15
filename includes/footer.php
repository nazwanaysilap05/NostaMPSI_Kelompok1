<div class="print-foot">Dicetak pada <?= e(tanggal_panjang()) ?>, <?= e(date('H:i')) ?> WIB oleh <?= e(user()['nama'] ?? '-') ?></div>
</main>
</div>
</div>
<script>
(function () {
    var b = document.getElementById('burger'), o = document.getElementById('overlay');
    function set(open) {
        document.body.classList.toggle('nav-open', open);
        if (b) { b.setAttribute('aria-expanded', open ? 'true' : 'false'); }
    }
    if (b) { b.addEventListener('click', function () { set(!document.body.classList.contains('nav-open')); }); }
    if (o) { o.addEventListener('click', function () { set(false); }); }
    document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') { set(false); } });
    var links = document.querySelectorAll('.sidebar .menu a');
    for (var i = 0; i < links.length; i++) { links[i].addEventListener('click', function () { set(false); }); }
})();
</script>
</body>
</html>
