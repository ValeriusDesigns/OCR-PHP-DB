</div>

<footer>
  <!-- Hier kannst du den Inhalt des Footers definieren, z.B. Copyright-Informationen, Links, etc. -->
  <p><?php echo $appName; ?> Admin panel &copy; <?php echo date('Y'); ?> <a
      href="https://valerius.app"><?php echo $appCopyright; ?></a></p>
</footer>
</div>

<script src="assets/main.js"></script>

<script>
  
  if ("serviceWorker" in navigator) {
    window.addEventListener("load", function () {
      navigator.serviceWorker.register("assets/service-worker.js").then(
        function (success) {
          console.log("ServiceWorker wurde erfolgreich registriert.", success);
        }
      ).catch(
        function (error) {
          console.log("ServiceWorker konnte leider nicht registriert werden.", error);
        }
      );
    });
  }
</script>
</body>

</html>