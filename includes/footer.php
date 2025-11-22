<footer class="bg-dark text-light py-5">
  <div class="container">
    <div class="row">

      <!-- Giới thiệu thương hiệu -->
      <div class="col-md-4 mb-4">
        <h5 class="fw-bold">MED<span style="color:#FBAE3C">XTORE</span></h5>
        <p>Sức khỏe thể chất và tinh thần của bạn là ưu tiên hàng đầu tại MedXtore. Chúng tôi luôn đồng hành và giúp bạn dễ dàng tiếp cận các loại vitamin cần thiết.</p>
        <div class="d-flex gap-2 fs-5">
          <a href="#" class="text-light" title="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="#" class="text-light" title="TikTok"><i class="bi bi-tiktok"></i></a>
          <a href="#" class="text-light" title="YouTube"><i class="bi bi-youtube"></i></a>
          <a href="#" class="text-light" title="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="#" class="text-light" title="Zalo"><i class="bi bi-chat-dots-fill"></i></a>
        </div>
      </div>

      <!-- Liên hệ -->
      <div class="col-md-4 mb-4">
        <h6 class="fw-bold" style="color:aliceblue;">Liên hệ với chúng tôi</h6>
        <p>
          📞 0123 456 789<br>
          📧 medxtore@gmail.com<br>
          📍 123 Đường Sức Khỏe, Quận Dược, TP. HCM
        </p>
      </div>

      <!-- Form liên hệ -->
      <div class="col-md-4 mb-4">
        <h6 class="fw-bold" style="color:aliceblue;">Gửi thông tin liên hệ</h6>
        <form>
          <input type="text" class="form-control mb-2" placeholder="Họ và tên" required>
          <input type="email" class="form-control mb-2" placeholder="Email" required>
          <input type="text" class="form-control mb-2" placeholder="Số điện thoại">
          <button type="submit" class="btn btn-primary w-100">Gửi ngay</button>
        </form>
      </div>

    </div>
  </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.querySelectorAll('.faq-item').forEach(item => {
    item.addEventListener('click', () => {
      item.classList.toggle('active');
    });
  });
</script>
</body>
</html>
