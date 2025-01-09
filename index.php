<?php include $base_url . "nav/header.php"; ?>

<body>


  <!-- HERO SECTION -->
  <section class="hero-section">
    <div class="hero-container">
      <div class="hero-text">
        <h2 class="hero-header">
          Rev. Elijah <br />
          Oluranti Akinyemi
        </h2>
        <h3 class="hero-date"> 15 Jun 1956 - 29 Nov 2024 </h3>
      </div>

      <div class="hero-image">
        <img src="images/hero-image.png" alt="" />
        <p class="hero-quote">A light that will never fade</p>

        <div class="countdown-container">
  <h2>Time Until Service of Songs</h2>
  <div class="countdown">
    <div class="time-block">
      <span id="days">00</span>
      <span class="label">Days</span>
    </div>
    <div class="time-block">
      <span id="hours">00</span>
      <span class="label">Hours</span>
    </div>
    <div class="time-block">
      <span id="minutes">00</span>
      <span class="label">Minutes</span>
    </div>
    <div class="time-block">
      <span id="seconds">00</span>
      <span class="label">Seconds</span>
    </div>
  </div>
</div>
      </div>

      
<style>
.countdown-container {
  max-width: 600px;
  margin: 20px auto;
  padding: 20px;
  background: rgba(0, 0, 0, 0.8);
  border-radius: 10px;
  color: white;
  text-align: center;
}

.countdown-container h2 {
  margin-bottom: 20px;
  font-size: 1.5rem;
}

.countdown {
  display: flex;
  justify-content: space-around;
  gap: 10px;
}

.time-block {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.time-block span:first-child {
  font-size: 2.5rem;
  font-weight: bold;
  margin-bottom: 5px;
}

.label {
  font-size: 0.9rem;
  text-transform: uppercase;
}
</style>



      <div class="details-container">
        <h5> FUNERAL ARRANGEMENTS </h5>

        <div class="funeral-text">
          <b>SERVICE OF SONGS</b> <br>
          Ciona Suites Banquet Hall <br>
          19/21 Woji Road, Port Harcourt, Rivers State <br>
          Monday 17, February 2025 | <span style="color: #fff">4pm</span>
        </div>

        <div class="funeral-text">
          <b>FUNERAL SERVICE </b> <br>
          Local Christian Assembly, Lagos <br>
          136, Lagos Street, Ebute-Metta, Lagos <br>
          Thursday 20, February 2025 | <span style="color: #fff"> 10am </span>
        </div>


        <div class="cta-section">
          <p class="cta-p">Join us in celebrating the life of our beloved. Please RSVP to confirm your attendance.</p>
          <a href="rsvp.php" class="link-button">RSVP Now</a>
        </div>


        <!-- <div class="funeral-text">
          <b>PRIVATE INTERMENT</b> </br> 
           <br>
        </div> -->

      </div>
    </div>
  </section>
<!--   <div style="height: 30px;"></div> <!-- Adds vertical space -->
 -->

</body>

<script>
function updateCountdown() {
  const targetDate = new Date('2025-02-17T16:00:00').getTime();
  
  function update() {
    const now = new Date().getTime();
    const difference = targetDate - now;

    const days = Math.floor(difference / (1000 * 60 * 60 * 24));
    const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((difference % (1000 * 60)) / 1000);

    document.getElementById('days').textContent = days.toString().padStart(2, '0');
    document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
    document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
    document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');

    if (difference < 0) {
      clearInterval(timer);
      document.querySelector('.countdown-container').innerHTML = '<h2>Service of Songs has begun</h2>';
    }
  }

  // Update immediately and then every second
  update();
  const timer = setInterval(update, 1000);
}

// Start the countdown when the page loads
document.addEventListener('DOMContentLoaded', updateCountdown);
</script>
Improve

<?php include 'nav/footer.php'; ?>

</html>