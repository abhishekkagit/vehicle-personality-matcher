<footer class="footer">
  <p>© 2026 Vehicle Personality Matcher</p>
</footer>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>
AOS.init();

/* TYPING EFFECT */
new Typed("#typed-text", {
  strings: ["Find Your Perfect Car ", "Find Your Perfect Bike "],
  typeSpeed: 50,
  backSpeed: 30,
  loop: true
});

/* PARTICLES */
particlesJS("particles-js", {
  particles: {
    number: { value: 80 },
    size: { value: 3 },
    move: { speed: 2 },
    line_linked: { enable: true }
  }
});

/* GSAP ENTRY */
gsap.from(".hero-content", {
  y: 50,
  opacity: 0,
  duration: 1.2
});
</script>
</body>
</html>
