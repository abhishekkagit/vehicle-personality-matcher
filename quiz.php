<?php
include 'includes/header.php';

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? '';

if (!$type || !$id) {
    die("Invalid vehicle selection.");
}
?>

<div class="quiz-wrapper">

  <h2 class="quiz-title">Compatibility Quiz</h2>
  <p class="quiz-sub">Answer a few quick questions about your driving style, priorities, and everyday needs</p>

  <div class="progress">
    <span id="progressText">Question 1 of 10</span>
    <div class="progress-bar">
      <div id="progressFill"></div>
    </div>
  </div>

  <form method="POST" action="result.php" id="quizForm">

    <input type="hidden" name="vehicle_type" value="<?= htmlspecialchars($type) ?>">
    <input type="hidden" name="vehicle_id" value="<?= htmlspecialchars($id) ?>">

    <div class="quiz-step active">
      <h3>Where will you use this vehicle most often?</h3>

      <label><input type="radio" name="usage" value="85"> Daily city commuting</label>
      <label><input type="radio" name="usage" value="60"> Mostly highways and long trips</label>
      <label><input type="radio" name="usage" value="72"> A balanced mix of city and highway</label>
      <label><input type="radio" name="usage" value="35"> Occasional weekend or leisure use</label>
    </div>

    <div class="quiz-step">
      <h3>Which priority matters most in day-to-day use?</h3>

      <label><input type="radio" name="mileage" value="92"> Low running cost and fuel efficiency</label>
      <label><input type="radio" name="mileage" value="72"> A good balance of efficiency and performance</label>
      <label><input type="radio" name="mileage" value="30"> Strong performance matters more than economy</label>
      <label><input type="radio" name="mileage" value="55"> I am fairly flexible on this</label>
    </div>

    <div class="quiz-step">
      <h3>How important are ride comfort and ease over longer journeys?</h3>

      <label><input type="radio" name="comfort" value="95"> Extremely important</label>
      <label><input type="radio" name="comfort" value="80"> Quite important</label>
      <label><input type="radio" name="comfort" value="60"> Nice to have, but not everything</label>
      <label><input type="radio" name="comfort" value="35"> I can trade comfort for other benefits</label>
    </div>

    <div class="quiz-step">
      <h3>How much maintenance cost and effort can you comfortably accept?</h3>

      <label><input type="radio" name="maintenance" value="92"> I want it low and predictable</label>
      <label><input type="radio" name="maintenance" value="72"> Moderate upkeep is fine</label>
      <label><input type="radio" name="maintenance" value="45"> Higher upkeep is okay if the experience is worth it</label>
      <label><input type="radio" name="maintenance" value="20"> Maintenance cost is not a major concern</label>
    </div>

    <div class="quiz-step">
      <h3>Which driving or riding feel suits you best?</h3>

      <label><input type="radio" name="performance" value="35"> Relaxed and smooth</label>
      <label><input type="radio" name="performance" value="65"> Balanced and confident</label>
      <label><input type="radio" name="performance" value="82"> Quick and engaging</label>
      <label><input type="radio" name="performance" value="95"> Sharp, thrilling, and aggressive</label>
    </div>

    <div class="quiz-step">
      <h3>How much everyday practicality do you need?</h3>

      <label><input type="radio" name="practicality" value="92"> A lot, I want easy everyday usability</label>
      <label><input type="radio" name="practicality" value="72"> A healthy balance of utility and style</label>
      <label><input type="radio" name="practicality" value="52"> Some practicality is enough</label>
      <label><input type="radio" name="practicality" value="28"> Practicality is not a priority</label>
    </div>

    <div class="quiz-step">
<?php if ($type === 'bike') { ?>
      <h3>How often do you expect to carry a pillion rider?</h3>

      <label><input type="radio" name="passengers" value="30"> Almost always solo</label>
      <label><input type="radio" name="passengers" value="58"> Occasionally with a pillion</label>
      <label><input type="radio" name="passengers" value="78"> Quite often with a pillion</label>
      <label><input type="radio" name="passengers" value="90"> Regularly with a pillion rider</label>
<?php } else { ?>
      <h3>How many people usually travel with you?</h3>

      <label><input type="radio" name="passengers" value="40"> Usually just me</label>
      <label><input type="radio" name="passengers" value="60"> Mostly two people</label>
      <label><input type="radio" name="passengers" value="80"> Often 3-4 people</label>
      <label><input type="radio" name="passengers" value="92"> Usually family or a full group</label>
<?php } ?>
    </div>

    <div class="quiz-step">
      <h3>How long do you expect to keep this vehicle?</h3>

      <label><input type="radio" name="ownership" value="20"> Less than 2 years</label>
      <label><input type="radio" name="ownership" value="42"> Around 2-5 years</label>
      <label><input type="radio" name="ownership" value="70"> Around 5-8 years</label>
      <label><input type="radio" name="ownership" value="90"> 8+ years</label>
    </div>

    <div class="quiz-step">
      <h3>How would unexpected ownership costs affect you?</h3>

      <label><input type="radio" name="cost_sensitivity" value="92"> They would bother me a lot</label>
      <label><input type="radio" name="cost_sensitivity" value="72"> I would be concerned</label>
      <label><input type="radio" name="cost_sensitivity" value="42"> I could handle them if needed</label>
      <label><input type="radio" name="cost_sensitivity" value="20"> I already expect some surprises</label>
    </div>

    <div class="quiz-step">
      <h3>What kind of roads will you mostly use it on?</h3>

      <label><input type="radio" name="road_type" value="1"> Smooth city roads</label>
      <label><input type="radio" name="road_type" value="2"> Highways</label>
      <label><input type="radio" name="road_type" value="3"> Mixed roads</label>
      <label><input type="radio" name="road_type" value="4"> Rough or uneven roads</label>
    </div>

    <div class="quiz-nav">
      <button type="button" id="backBtn" disabled><- Back</button>
      <button type="button" id="nextBtn" disabled>Next -></button>
    </div>

  </form>

</div>

<?php include 'includes/footer.php'; ?>

<script>
let current = 0;
const steps = document.querySelectorAll('.quiz-step');
const nextBtn = document.getElementById('nextBtn');
const backBtn = document.getElementById('backBtn');
const progressFill = document.getElementById('progressFill');
const progressText = document.getElementById('progressText');

function updateUI() {
  steps.forEach(step => step.classList.remove('active'));
  steps[current].classList.add('active');

  backBtn.disabled = current === 0;
  nextBtn.innerText = current === steps.length - 1 ? 'Finish' : 'Next ->';
  progressText.innerText = `Question ${current + 1} of ${steps.length}`;
  progressFill.style.width = ((current + 1) / steps.length * 100) + '%';
  nextBtn.disabled = !steps[current].querySelector('input[type=radio]:checked');
}

document.querySelectorAll('input[type=radio]').forEach(input => {
  input.addEventListener('change', () => {
    nextBtn.disabled = false;
  });
});

nextBtn.onclick = () => {
  if (current < steps.length - 1) {
    current++;
    updateUI();
  } else {
    document.getElementById('quizForm').submit();
  }
};

backBtn.onclick = () => {
  current--;
  updateUI();
};

updateUI();
</script>
